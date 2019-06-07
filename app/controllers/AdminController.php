<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 07/06/2019
 * Time: 18:04
 */
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
        $debug = '';

        $log_web = shell_exec('git log -1');
        $log_bot = shell_exec('cd "' . $bot_folder . '" && git log -1');

        $db_files = [];
        $dir = new DirectoryIterator(__DIR__ . '/../../db');
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $db_files[] = $fileinfo->getFilename();
            }
        }
        rsort($db_files);

        return [
            'debug' => print_r($debug, true),
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
        $ok = file_put_contents($update_pid, $target);

        $debug = "";
        return [
            'debug' => print_r($debug, true),
            'target' => $target,
            'ok' => $ok,
        ];
    }
}
