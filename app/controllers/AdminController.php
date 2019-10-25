<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 07/06/2019
 * Time: 18:04
 */

require_once __DIR__ . '/../models/DBpatch.php';


class AdminController
{
    static protected function checkAccess()
    {
        //-- Check rights: the connected user can see the list of users only if he is a Conseiller, a Bureau member or an Admin
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !Role::hasAnyRole($cur_user->id, [Role::kAdmin])) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }

    static public function index($bot_folder)
    {
        $cur_user = self::checkAccess();

        $debug = '';

        $log_web = shell_exec('git log -1');
        $log_bot = shell_exec('cd "' . $bot_folder . '" && git log -1');

        $db_files = [];
        $dir = new DirectoryIterator(__DIR__ . '/../../db');
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isFile()) {
                $file_name = $fileinfo->getFilename();
                if (preg_match('/.+\.sql/i', $file_name)) {
                    $d = new DBpatch;
                    $db_files[$file_name] = [
                        'name' => $file_name,
                        'mtime' => $fileinfo->getMTime(),
                        'patch' => $d->select('lsd_dbpatches.*', 'lsd_users.discord_username as username')->equal('filename', $file_name)->join('lsd_users', 'lsd_users.id=applied_by')->find(),
                        'content' => file_get_contents($fileinfo->getPathname()),
                        'tag' => preg_replace('/\W/', '', $file_name),
                    ];
                }
            }
        }
        rsort($db_files);

        return [
            'debug' => print_r($debug, true),
            'cur_user' => $cur_user,
            'log_web' => $log_web,
            'log_bot' => $log_bot,
            'db_files' => $db_files,
        ];
    }

    /**
     * Trigger an update the website
     * This is done by sending a SIGUSR1 signal to the background script in charge of performing updates
     * @param $update_pid
     * @param string $target 'web' or 'bot'
     */
    static public function update($update_pid, $target)
    {
        $cur_user = self::checkAccess();
        $ok = file_put_contents($update_pid, $target);

        $debug = "";
        return [
            'debug' => print_r($debug, true),
            'cur_user' => $cur_user,
            'target' => $target,
            'ok' => $ok,
        ];
    }

    /**
     * Apply (or skip) a database migration script
     * @param array $params
     * @return array
     */
    static public function updatedb($params = [])
    {
        $cur_user = self::checkAccess();
        $file_name = preg_replace('/[^\w\.]/', '', $params['filename']);
        $error = '';
        $pathname = __DIR__ . '/../../db/' . $file_name;
        $sql = '';
        $action = '';

        try {
            //-- Verify if the file was not already applied
            $dp = new DBpatch();
            if ($dp->equal('filename', $file_name)->find()) {
                throw new Exception("Le script $file_name a déjà été traité");
            }
            //--
            if (isset($params['apply'])) {
                //-- Read file and execute SQL command
                $sql = file_get_contents($pathname);
                ActiveRecord::execute($sql);
                $action = 'appliqué';
            } else {
                $action = 'ignoré';
            }

            //-- Mark the file as processed in the database
            $d = new DBpatch();
            $d->filename = $file_name;
            $d->applied_at = time();
            $d->applied_by = $cur_user->id;
            $d->insert();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $debug = '';
        return [
            'debug' => print_r($debug, true),
            'cur_user' => $cur_user,
            'file_name' => $file_name,
            'sql' => $sql,
            'ok' => ($error == ''),
            'action' => $action,
            'error' => $error,
        ];
    }
}
