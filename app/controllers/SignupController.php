<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 29/12/2018
 * Time: 18:39
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Section.php';
require_once __DIR__ . '/../models/Log.php';

class SignupController
{
    static protected function checkAccess($allow_scorpions = false)
    {
        //-- Check rights
        $cur_user = User::getConnectedUser();
        if (!$cur_user) {
            redirectTo('/login/expired');
        }

        //-- If the user is already a Scorpion, we have nothing to do here: go to the main page
        if (!$allow_scorpions && $cur_user->isScorpion()) {
            redirectTo('/');
        }

        return $cur_user;
    }

    static public function signup($app, $params = [])
    {
        global $discord_channel_review; // Notification channel

        $cur_user = self::checkAccess();
        $errors = [];

        if (count($params)) {
            //-- Check values
            $params = array_merge(['testimony' => '', 'age' => 0, 'charte' => '', 'email' => ''], $params);
            if (strlen($params['testimony']) < 16) {
                $errors['testimony'] = 'Ta description est un peu courte, grand timide va !';
            }
            if (empty($params['section'])) {
                $errors['section'] = 'Choisis une Section ou "Jeux du moment"';
            }
            if (intval($params['age']) < 6) {
                $errors['age'] = 'Moins de 6 ans, vraiment ?';
            }
            if (strpos($params['charte'], 'lu') === false || strpos($params['charte'], 'accepte') === false || strpos($params['charte'], 'charte') === false) {
                $errors['charte'] = 'Tu as mal rédigé ton acceptation, essaye encore (c\'est un jeu) !';
            }
            $params['pseudo'] = trim($params['pseudo']);

            if (count($errors) == 0) {
                // Accepted form, update the database
                $cur_user->testimony = $params['testimony'];
                $cur_user->minor = (intval($params['age']) < 18) ? 1 : 0;
                $cur_user->email = $params['email'];
                $cur_user->submited_on = time();
                $cur_user->save();
                // If a Section was selected, attach it to the user
                // TODO: some Sections will want to control their candidates, so this will have to be adapted
                $section_message = '';
                if ($params['section'] && $params['section'] != 'JDM') {
                    $r = new Role;
                    $r->user_id = $cur_user->id;
                    $r->role = Role::kMembre;
                    $r->extra = $params['section'];
                    if ($params['pseudo']) {
                        $r->extra2 = $params['pseudo'];
                    }
                    $r->insert();
                    $section_message = ' pour la Section ' . $params['section'];
                }
                // Send a message to Discord in the "Conseil des Jeux" channel with a link
                Discord::sendChannelMessage($discord_channel_review, "Le joueur `" . $cur_user->discord_username . "` a posté sa candidature" . $section_message . ", merci d'aller l'examiner rapidement !");
                // Done, thank you and bye
                $app->flash->addMessage('success', 'Ta candidature a bien été enregistrée, merci !');
                redirectTo('/signup/pending');
            }
        }

        return [
            'cur_user' => $cur_user,
            'sections' => Section::getActiveSections(),
            'params' => $params,
            'errors' => $errors,
        ];
    }

    static public function pending()
    {
        $cur_user = self::checkAccess();

        return [
            'cur_user' => $cur_user,
        ];
    }

    static public function refused()
    {
        $cur_user = self::checkAccess();

        return [
            'cur_user' => $cur_user,
        ];
    }

    static public function signupVB()
    {
        $cur_user = self::checkAccess(true);

        return [
            'cur_user' => $cur_user,
            'errors' => [],
        ];
    }

    static public function signupVBPost($app, $params)
    {
        $cur_user = self::checkAccess(true);
        $errors = [];

        $params['vb_login'] = trim($params['vb_login']);
        if (empty($params['vb_login'])) {
            $errors['vb_login'] = 'Pseudo manquant';
        }
        if (empty($params['vb_password'])) {
            $errors['vb_password'] = 'Mot de passe manquant';
        }

        if ($params['vb_login'] && $params['vb_password']) {
            $vbUser = self::checkVBcredentials($params['vb_login'], $params['vb_password']);
            if ($vbUser) {
                // Success
                self::convertUserFromVB($vbUser, $cur_user);
                // Synch-up to Discord
                UsersController::synchToDiscord($cur_user, $cur_user);
                //
                $app->flash->addMessage('success', 'Informations forum importées avec succès !');
                redirectTo('/users/' . $cur_user->id);
            } else {
                $errors[] = 'Pseudo ou Mot de passe incorrect';
            }
        }

        $debug = $debug ?? '';
        return [
            'debug' => print_r($debug, true),
            'cur_user' => $cur_user,
            'vb_login' => $params['vb_login'],
            'errors' => $errors,
        ];
    }

    /**
     * Check the VB forum credentials
     *
     * @param string $vb_username VB username or e-mail
     * @param string $vb_pwd VB password
     * @return false|VBUser return the VB record in case of success, otherwise false
     */
    static protected function checkVBcredentials($vb_username, $vb_pwd)
    {
        //-- Check user
        $vb_users = new VBUser;
        $vb_user = $vb_users->equal('username', $vb_username)->find();
        if (!$vb_user) {
            // Try with the e-mail
            $vb_user = $vb_users->equal('email', $vb_username)->find();
            if (!$vb_user) {
                return false;
            }
        }

        //-- Check password
        $pwd_md5 = md5(trim($vb_pwd));
        if (password_verify($pwd_md5, $vb_user->token)) {
            return $vb_user;
        } else {
            return false;
        }
    }

    /**
     * Retreive as much information as possible from a VBulletin user to fill in the LSD User
     * Include roles like officers etc.
     *
     * @param VBUser $vbUser
     * @param User $target_user
     * @return true if record was successfully saved
     *
     * SELECT userid, username, usergroupid, membergroupids FROM vb_user WHERE userid=53;
     *
     */
    static protected function convertUserFromVB($vbUser, $target_user)
    {
        //-- VB ID
        $target_user->vb_id = $vbUser->userid;
        //-- Log it
        Log::logVBImport($target_user);
        //-- Account creation
        $target_user->created_on = min($target_user->created_on, $vbUser->joindate);
        //-- E-mail
        if (empty($target_user->email) && $vbUser->options & 16) { // This indicates that the user accepts e-mailings
            $target_user->email = $vbUser->email;
        }
        //-- Import groups
        $vb_groups = [$vbUser->usergroupid];
        $vb_groups = array_merge($vb_groups, explode(',', $vbUser->membergroupids));
        Role::importVBRoles($vb_groups, $target_user->id);
        //-- Done
        return $target_user->save();
    }

    /**
     * A static page explaining how to sign-up
     */
    static public function inscription()
    {
        $cur_user = User::getConnectedUser();

        return [
            'cur_user' => $cur_user,
        ];

    }
}
