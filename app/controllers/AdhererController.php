<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 08/05/2019
 * Time: 14:45
 *
 * Become a paying member (adherent)
 */
require_once __DIR__ . '/../models/Adhesion.php';
require_once __DIR__ . '/../models/Transaction.php';


class AdhererController
{
    static protected function checkAccess()
    {
        //-- Check rights: the connected user can pay only if he/she is a Scorpion
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !$cur_user->isScorpion()) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }


    /**
     * Form
     * @return array
     */
    static public function adherer()
    {
        $cur_user = self::checkAccess();
        $adhesion = new Adhesion;

        //--
        $debug = $cur_user->isAdmin() ? $_SERVER : '';
        return [
            'cur_user' => $cur_user,
            'adhesion' => $adhesion,
            'errors' => [],
            'debug' => print_r($debug, true),
        ];
    }

    /**
     * Form processing
     *
     * @doc PayPal HTML variables: https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx-websitestandard-htmlvariables/#technical-variables
     * @return array
     */
    static public function post($params)
    {
        global $paypal_url;
        global $paypal_btn_standard;
        global $paypal_btn_custom;


        $cur_user = self::checkAccess();

        $adhesion = new Adhesion;
        $adhesion->user_id = $cur_user->id;
        $adhesion->name = trim($params['name']);
        $adhesion->firstname = trim($params['firstname']);
        $adhesion->dob = trim($params['dob']);
        $adhesion->address = trim($params['address']);
        $adhesion->telephone = trim($params['telephone']);
        $adhesion->cotisation = ($params['cotisation'] == Adhesion::kCotisationStandard) ? Adhesion::kCotisationStandard : Adhesion::kCotisationCustom;
        $adhesion->created_on = time();

        //-- Validate params
        $errors = $adhesion->validate();
        if (count($errors) == 0) {
            $ok = $adhesion->insert();
            if ($ok) {
                $pp_url = $paypal_url . '/cgi-bin/webscr?cmd=_s-xclick';
                $pp_url .= '&hosted_button_id=' . (($adhesion->cotisation != Adhesion::kCotisationStandard) ? $paypal_btn_custom : $paypal_btn_standard);
                $pp_url .= '&custom=' . $adhesion->id;  // We send the adhesion id in the hope of getting it back when we receive the payment confirmation
                $pp_url .= '&return=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . '/adherer/merci?aid=' . $adhesion->id);
                $pp_url .= '&cancel_return=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . '/adherer/annuler?aid=' . $adhesion->id);
                $pp_url .= '&rm=1'; // Super important: go back to the return URLs with a POST and all the payment variables
                $pp_url .= '&image_url=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . '/img/LSD_Blason_bleu-50px.png');
                \Slim\Slim::getInstance()->redirect($pp_url);
            } else {
                $errors['Base de données'] = 'Désolé, un problème est survenu lors de la mise à jour de la base de données. Recommencez pour voir ?';
            }
        }

        //--
        $debug = $cur_user->isAdmin() ? $adhesion : '';
        return [
            'cur_user' => $cur_user,
            'adhesion' => $adhesion,
            'errors' => $errors,
            'debug' => print_r($debug, true),
        ];
    }

    static public function merci($params)
    {
        $cur_user = self::checkAccess();
        $debug = $cur_user->isAdmin() ? $params : '';
        return [
            'cur_user' => $cur_user,
            'debug' => print_r($debug, true),
        ];
    }

    static public function annuler($params)
    {
        $cur_user = self::checkAccess();
        $debug = $cur_user->isAdmin() ? $params : '';
        return [
            'cur_user' => $cur_user,
            'debug' => print_r($debug, true),
        ];
    }

    static public function ipn($params, $request)
    {
        file_put_contents('/tmp/ipn.log', $request->getBody() . "\n" . print_r($params, true) . "\n", FILE_APPEND);
        $t = new Transaction;
        $t->adhesion_id = int($params['custom']);

        $ipn_fields = ['txn_id', 'mc_gross', 'mc_currency', 'payer_id', 'payment_date', 'payment_status',
            'first_name', 'last_name', 'payer_email', 'receiver_email', 'verify_sign', 'item_name', 'residence_country',
            'ipn_track_id'];
        foreach ($ipn_fields as $field) {
            $t->{$field} = $params[$field] ?? '';
        }
        $t->insert();

        exit('');
    }
}
