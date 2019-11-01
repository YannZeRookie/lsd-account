<?php

/**
 * Library to talk to the Discord API in a server-to-server manner
 * @doc https://discordapp.com/developers/docs/topics/oauth2
 * @doc https://discordapp.com/developers/docs/resources/user
 * @doc https://discordapp.com/developers/docs/resources/guild
 *
 * Created by PhpStorm.
 * User: yann
 * Date: 15/03/2019
 * Time: 17:41
 */
class Discord
{
    static protected $api = '';
    static protected $bot_token = '';       // No need to use OAuth2: we'll pass directly the Bot token. Otherwise, we will be too limited
    static public $status = '';             // Status of the last query
    static protected $guild_id = '';        // Our Guild ID (=Discord server ID)

    static public function init($api, $bot_token, $guild_id)
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
    static protected function query($url, $method = 'GET', $params = [], $body = '', $content_type = 'multipart/form-data', $headers = [])
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
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, TRUE);
                if ($content_type == 'application/json') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                }
                break;
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
//        error_log('curl: url=' . $url);
//        error_log('curl: $headers=' . print_r($headers, true));
//        error_log('curl: body=' . $body);
        return $output;
    }

    /**
     * Lower-level HTTP GET query
     * @param $url
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    static public function get($url, $params = [], $headers = [])
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
    static public function post($url, $params, $headers = [])
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
    static public function query_json($url, $method = 'GET', $params = [], $headers = [])
    {
        $headers[] = 'Accept: application/json';
        $res = self::query($url, $method, [], (count($params) ? json_encode($params, JSON_NUMERIC_CHECK) : ''), 'application/json', $headers);
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
    static public function api_get($end_point, $params = [])
    {
        return self::query_json(self::$api . $end_point, 'GET', $params, ['Authorization: Bot ' . self::$bot_token]);
    }

    static public function api_post($end_point, $params = [])
    {
        return self::query_json(self::$api . $end_point, 'POST', $params, ['Authorization: Bot ' . self::$bot_token]);
    }

    static public function api_put($end_point, $params = [])
    {
        return self::query_json(self::$api . $end_point, 'PUT', $params, ['Authorization: Bot ' . self::$bot_token]);
    }

    static public function api_delete($end_point, $params = [])
    {
        return self::query_json(self::$api . $end_point, 'DELETE', $params, ['Authorization: Bot ' . self::$bot_token]);
    }


    //------------------------------------------------------------------------------------------------------------------
    // Discord API end-points wrappers
    // See @doc https://discordapp.com/developers/docs/reference
    //------------------------------------------------------------------------------------------------------------------
    /**
     * Get our server info
     */
    static public function discord_get_guild()
    {
        return self::api_get('/guilds/' . self::$guild_id);
    }

    static protected function sort_roles($a, $b)
    {
        if ($a->position == $b->position) {
            return 0;
        }
        return ($a->position < $b->position) ? -1 : 1;
    }

    /**
     * Get the Roles of our server
     * Make a well-sorted list, using the Roles IDs as entries keys
        [383968791454941193] => stdClass Object
        (
            [hoist] =>
            [name] => @everyone
            [mentionable] =>
            [color] => 0
            [position] => 0
            [id] => 383968791454941193
            [managed] =>
            [permissions] => 512
        )
        [404693131573985280] => stdClass Object
        (
            [hoist] => 1
            [name] => Invité
            [mentionable] =>
            [color] => 1752220
            [position] => 1
            [id] => 404693131573985280
            [managed] =>
            [permissions] => 103894528
        )
     */
    static public function discord_get_roles()
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
    static public function discord_get_user_roles($discord_id, $roles = null)
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

    /**
     * Add a role to a Discord user
     * @param $discord_id
     * @param $result
     */
    static public function addRole($discord_id, $role, $roles = null)
    {
//        error_log('synchToDiscord: adding ' . $role);
        if (!$roles) {
            $roles = self::discord_get_roles();
        }
        //-- Find and add role
        foreach ($roles as $r) {
            if ($r->name == $role) {
//                error_log('synchToDiscord: adding $r= ' . print_r($r,true));
                $result = self::api_put('/guilds/' . self::$guild_id . '/members/' . $discord_id . '/roles/' . $r->id);
//                error_log('synchToDiscord: adding $result= ' . print_r($result,true));
                return $result;
            }
        }
        return false;
    }

    /**
     * Remove a role from a Discord user
     * @param $discord_id
     * @param $role
     */
    static public function removeRole($discord_id, $role, $roles = null)
    {
//        error_log('synchToDiscord: removing ' . $role);
        if (!$roles) {
            $roles = self::discord_get_roles();
        }
        //-- Find and add role
        foreach ($roles as $r) {
            if ($r->name == $role) {
//                error_log('synchToDiscord: deleting $r= ' . print_r($r,true));
                $result = self::api_delete('/guilds/' . self::$guild_id . '/members/' . $discord_id . '/roles/' . $r->id);
//                error_log('synchToDiscord: deleting $result= ' . print_r($result,true));
                return $result;
            }
        }
        return false;
    }

    /**
     * Send a message to a Discord channel
     * @doc https://discordjs.guide/miscellaneous/parsing-mention-arguments.html#how-discord-mentions-work about the mention syntax
     * @param string $channel Channel ID
     * @param string $message
     * @return bool|mixed
     */
    static public function sendChannelMessage($channel, $message)
    {
        $params = [
            'content' => $message,
        ];
        return self::api_post('/channels/' . $channel . '/messages', $params);

    }

    /**
     * Send a private message (aka DM)
     * @param string $discord_id
     * @param string $message
     * @return bool|mixed
     */
    static public function sendPrivateMessage($discord_id, $message)
    {
        $channel = self::api_post('/users/@me/channels', ['recipient_id' => $discord_id]);
        if ($channel && $channel->id)   {
            return self::sendChannelMessage($channel->id, $message);
        } else {
            return $channel;
        }
    }
}
