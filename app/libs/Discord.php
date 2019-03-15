<?php

/**
 * Library to talk to the Discord API in a server-to-server manner
 * @doc https://discordapp.com/developers/docs/topics/oauth2
 *
 * Created by PhpStorm.
 * User: yann
 * Date: 15/03/2019
 * Time: 17:41
 */
class Discord
{
    protected static $api = '';
    protected static $client_id = '';       // OAuth2 Client ID
    protected static $client_secret = '';   // OAuth2 Client secret
    public static $status = '';             // Status of the last query
    protected static $token = null;         // Discord token
    protected static $token_ts = 0;         // Token timestamp

    public static function init($api, $client_id, $client_secret)
    {
        self::$api = $api;
        self::$client_id = $client_id;
        self::$client_secret = $client_secret;
    }

    /**
     * Lowest-level multi-purpose HTTP query
     * @param $url
     * @param string $method
     * @param array $params
     * @param string $body
     * @param string $content_type
     * @param array $headers
     * @return mixed
     */
    protected static function query($url, $method = 'GET', $params = [], $body = '', $content_type = 'multipart/form-data', $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $query_string = http_build_query($params);
        switch ($method) {
            case 'GET';
                if ($query_string) {
                    if (strpos($url, '?') === FALSE) {
                        $url .= '?';
                    }
                    $url .= $query_string;
                }
                break;
            case 'PUT':
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, TRUE);
                if ($content_type == 'application/x-www-form-urlencoded') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
                } elseif ($content_type == 'application/json') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                }
                break;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers [] = 'Content-Type: ' . $content_type;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        self::$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $output;
    }

    /**
     * Lower-level HTTP GET query
     * @param $url
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    public static function get($url, $params = [], $headers = [])
    {
        return self::query($url, $method = 'GET', $params, '', 'plain/text', $headers);
    }

    /**
     * Lower-level HTTP POST query
     * @param $url
     * @param $params
     * @param array $headers
     * @return mixed
     */
    public static function post($url, $params, $headers = [])
    {
        return self::query($url, $method = 'POST', $params, '', 'application/x-www-form-urlencoded', $headers);
    }

    /**
     * Get the Discord access token
     * There is a small caching system to avoid calling Discord if we already have a valid token
     *
     * @return mixed|null
     */
    public static function oauth2_get_access_token()
    {
        if (!self::$token || self::$token_ts + self::$token['access_token'] > time()) {
            self::$token = null;
            $params = [
                'grant_type' => 'client_credentials',
                'scope' => 'identify connections guilds',
                'client_id' => self::$client_id,
                'client_secret' => self::$client_secret,
            ];
            $result = self::post(self::$api . '/oauth2/token', $params, []);
            if ($result) {
                self::$token = json_decode($result, FALSE, 512, JSON_NUMERIC_CHECK + JSON_BIGINT_AS_STRING);
                self::$token_ts = time();
            }
        }
        return self::$token;
    }

    /**
     * General-purpose JSON query
     *
     * @param $url
     * @param string $method
     * @param array $params
     * @param $headers
     * @return bool|mixed       False if failed, or the object
     */
    public static function query_json($url, $method = 'GET', $params = [], $headers = [])
    {
        $headers[] = 'Accept: application/json';
        $res = self::query($url, $method, [], json_encode($params, JSON_NUMERIC_CHECK), 'application/json', $headers);
        if ($res) {
            return json_decode($res, FALSE, 512, JSON_NUMERIC_CHECK + JSON_BIGINT_AS_STRING);
        } else {
            return false;
        }
    }

    /**
     * Hi-level API end-point query
     * Note that the access token will be automatically queried if we don't have one
     *
     * @param $end_point    End-point, like '/users/@me'. Should start by '/'.
     * @param array $params Query parameters, if any
     * @return bool|mixed   False if failed, or the object
     */
    public static function api_get($end_point, $params = [])
    {
        $token = self::oauth2_get_access_token();
        if ($token) {
            $headers = [
                'Authorization:Bearer ' . $token->access_token,
            ];
        }
        return self::query_json(self::$api . $end_point, 'GET', $params, $headers);
    }

}
