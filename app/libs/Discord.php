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
    protected static $bot_token = '';       // No need to use OAuth2: we'll pass directly the Bot token. Otherwise, we will be too limited
    public static $status = '';             // Status of the last query
    protected static $guild_id = '';        // Our Guild ID (=Discord server ID)

    public static function init($api, $bot_token, $guild_id)
    {
        self::$api = $api;
        self::$bot_token = $bot_token;
        self::$guild_id = $guild_id;
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
        return self::query_json(self::$api . $end_point, 'GET', $params, ['Authorization: Bot ' . self::$bot_token]);
    }

    //------------------------------------------------------------------------------------------------------------------
    // Discord API end-points wrappers
    // See @doc https://discordapp.com/developers/docs/reference
    //------------------------------------------------------------------------------------------------------------------
    /**
     * Get our server info
     */
    public static function discord_get_guild()
    {
        return self::api_get('/guilds/' . self::$guild_id);
    }

    protected static function sort_roles($a, $b)
    {
        if ($a->position == $b->position) {
            return 0;
        }
        return ($a->position < $b->position) ? -1 : 1;
    }

    /**
     * Get the Roles of our server
     * Make a well-sorted list, using the Roles IDs as entries keys
     */
    public static function discord_get_roles()
    {
        $roles = self::api_get('/guilds/' . self::$guild_id . '/roles');
        uasort($roles, ['self', 'sort_roles']);
        $result = [];
        foreach ($roles as $role) {
            $result[$role->id] = $role;
        }
        return $result;
    }

    /**
     * Get the Roles of a user
     */
    public static function discord_get_user_roles($discord_id, $roles = null)
    {
        if (!$roles) {
            $roles = self::discord_get_roles();
        }
        $user_info = self::api_get('/guilds/' . self::$guild_id . '/members/' . $discord_id);
        if ($user_info) {
            $user_roles = [];
            foreach ($user_info->roles as $role_id) {
                $user_roles[$role_id] = $roles[$role_id];
            }
            $user_info->roles = $user_roles;
        }
        return $user_info;
    }
}
