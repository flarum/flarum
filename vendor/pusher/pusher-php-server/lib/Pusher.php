<?php

/*
        Pusher PHP Library
    /////////////////////////////////
    PHP library for the Pusher API.

    See the README for usage information: https://github.com/pusher/pusher-php-server

    Copyright 2011, Squeeks. Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php

    Contributors:
        + Paul44 (http://github.com/Paul44)
        + Ben Pickles (http://github.com/benpickles)
        + Mastercoding (http://www.mastercoding.nl)
        + Alias14 (mali0037@gmail.com)
        + Max Williams (max@pusher.com)
        + Zack Kitzmiller (delicious@zackisamazing.com)
        + Andrew Bender (igothelp@gmail.com)
        + Phil Leggetter (phil@leggetter.co.uk)
        + Simaranjit Singh (simaranjit.singh@virdi.me)
*/

class PusherException extends Exception
{
}

class PusherInstance
{
    private static $instance = null;
    private static $app_id = '';
    private static $secret = '';
    private static $api_key = '';

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function get_pusher()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new Pusher(
            self::$api_key,
            self::$secret,
            self::$app_id
        );

        return self::$instance;
    }
}

class Pusher
{
    public static $VERSION = '2.6.4';

    private $settings = array(
        'scheme'       => 'http',
        'port'         => 80,
        'timeout'      => 30,
        'debug'        => false,
        'curl_options' => array(),
    );
    private $logger = null;
    private $ch = null; // Curl handler

    /**
     * PHP5 Constructor.
     *
     * Initializes a new Pusher instance with key, secret , app ID and channel.
     * You can optionally turn on debugging for all requests by setting debug to true.
     *
     * @param string $auth_key
     * @param string $secret
     * @param int    $app_id
     * @param bool   $options  [optional]
     *                         Options to configure the Pusher instance.
     *                         Was previously a debug flag. Legacy support for this exists if a boolean is passed.
     *                         scheme - e.g. http or https
     *                         host - the host e.g. api.pusherapp.com. No trailing forward slash.
     *                         port - the http port
     *                         timeout - the http timeout
     *                         encrypted - quick option to use scheme of https and port 443.
     *                         cluster - cluster name to connect to.
     *                         notification_host - host to connect to for native notifications.
     *                         notification_scheme - scheme for the notification_host.
     * @param string $host     [optional] - deprecated
     * @param int    $port     [optional] - deprecated
     * @param int    $timeout  [optional] - deprecated
     */
    public function __construct($auth_key, $secret, $app_id, $options = array(), $host = null, $port = null, $timeout = null)
    {
        $this->check_compatibility();

        /* Start backward compatibility with old constructor **/
        if (is_bool($options) === true) {
            $options = array(
                'debug' => $options,
            );
        }

        if (!is_null($host)) {
            $match = null;
            preg_match("/(http[s]?)\:\/\/(.*)/", $host, $match);

            if (count($match) === 3) {
                $this->settings['scheme'] = $match[1];
                $host = $match[2];
            }

            $this->settings['host'] = $host;

            $this->log('Legacy $host parameter provided: '.
                                    $this->settings['scheme'].' host: '.$this->settings['host']);
        }

        if (!is_null($port)) {
            $options['port'] = $port;
        }

        if (!is_null($timeout)) {
            $options['timeout'] = $timeout;
        }

        /* End backward compatibility with old constructor **/

        if (isset($options['encrypted']) &&
                $options['encrypted'] === true &&
                !isset($options['scheme']) &&
                !isset($options['port'])) {
            $options['scheme'] = 'https';
            $options['port'] = 443;
        }

        $this->settings['auth_key'] = $auth_key;
        $this->settings['secret'] = $secret;
        $this->settings['app_id'] = $app_id;
        $this->settings['base_path'] = '/apps/'.$this->settings['app_id'];

        foreach ($options as $key => $value) {
            // only set if valid setting/option
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }

        // Set the native notification host
        if (isset($options['notification_host'])) {
            $this->settings['notification_host'] = $options['notification_host'];
        } else {
            $this->settings['notification_host'] = 'nativepush-cluster1.pusher.com';
        }

        // Set scheme for native notifications
        if (isset($options['notification_scheme'])) {
            $this->settings['notification_scheme'] = $options['notification_scheme'];
        } else {
            $this->settings['notification_scheme'] = 'https';
        }

        // handle the case when 'host' and 'cluster' are specified in the options.
        if (!array_key_exists('host', $this->settings)) {
            if (array_key_exists('host', $options)) {
                $this->settings['host'] = $options['host'];
            } elseif (array_key_exists('cluster', $options)) {
                $this->settings['host'] = 'api-'.$options['cluster'].'.pusher.com';
            } else {
                $this->settings['host'] = 'api.pusherapp.com';
            }
        }

        // ensure host doesn't have a scheme prefix
        $this->settings['host'] =
            preg_replace('/http[s]?\:\/\//', '', $this->settings['host'], 1);
    }

    /**
     * Fetch the settings.
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set a logger to be informed of internal log messages.
     */
    public function set_logger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log a string.
     *
     * @param string $msg The message to log
     */
    private function log($msg)
    {
        if (is_null($this->logger) === false) {
            $this->logger->log('Pusher: '.$msg);
        }
    }

    /**
     * Check if the current PHP setup is sufficient to run this class.
     *
     * @throws PusherException if any required dependencies are missing
     */
    private function check_compatibility()
    {
        if (!extension_loaded('curl') || !extension_loaded('json')) {
            throw new PusherException('There is missing dependant extensions - please ensure both cURL and JSON modules are installed');
        }

        if (!in_array('sha256', hash_algos())) {
            throw new PusherException('SHA256 appears to be unsupported - make sure you have support for it, or upgrade your version of PHP.');
        }
    }

    /**
     * Validate number of channels and channel name format.
     *
     * @param string[] $channels An array of channel names to validate
     *
     * @throws PusherException if $channels is too big or any channel is invalid
     */
    private function validate_channels($channels)
    {
        if (count($channels) > 100) {
            throw new PusherException('An event can be triggered on a maximum of 100 channels in a single call.');
        }

        foreach ($channels as $channel) {
            $this->validate_channel($channel);
        }
    }

    /**
     * Ensure a channel name is valid based on our spec.
     *
     * @param $channel The channel name to validate
     *
     * @throws PusherException if $channel is invalid
     */
    private function validate_channel($channel)
    {
        if (!preg_match('/\A[-a-zA-Z0-9_=@,.;]+\z/', $channel)) {
            throw new PusherException('Invalid channel name '.$channel);
        }
    }

    /**
     * Ensure a socket_id is valid based on our spec.
     *
     * @param string $socket_id The socket ID to validate
     *
     * @throws PusherException if $socket_id is invalid
     */
    private function validate_socket_id($socket_id)
    {
        if ($socket_id !== null && !preg_match('/\A\d+\.\d+\z/', $socket_id)) {
            throw new PusherException('Invalid socket ID '.$socket_id);
        }
    }

    /**
     * Utility function used to create the curl object with common settings.
     */
    private function create_curl($domain, $s_url, $request_method = 'GET', $query_params = array())
    {
        $full_url = '';

        // Create the signed signature...
        $signed_query = self::build_auth_query_string(
            $this->settings['auth_key'],
            $this->settings['secret'],
            $request_method,
            $s_url,
            $query_params);

        $full_url = $domain.$s_url.'?'.$signed_query;

        $this->log('create_curl( '.$full_url.' )');

        // Create or reuse existing curl handle
        if (null === $this->ch) {
            $this->ch = curl_init();
        }

        if ($this->ch === false) {
            throw new PusherException('Could not initialise cURL!');
        }

        $ch = $this->ch;

        // curl handle is not reusable unless reset
        if (function_exists('curl_reset')) {
            curl_reset($ch);
        }

        // Set cURL opts and execute request
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Expect:',
          'X-Pusher-Library: pusher-http-php '.self::$VERSION,
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
        if ($request_method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ($request_method === 'GET') {
            curl_setopt($ch, CURLOPT_POST, 0);
        } // Otherwise let the user configure it

        // Set custom curl options
        if (!empty($this->settings['curl_options'])) {
            foreach ($this->settings['curl_options'] as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }

        return $ch;
    }

    /**
     * Utility function to execute curl and create capture response information.
     */
    private function exec_curl($ch)
    {
        $response = array();

        $response['body'] = curl_exec($ch);
        $response['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response['body'] === false || $response['status'] < 200 || 400 <= $response['status']) {
            $this->log('exec_curl error: '.curl_error($ch));
        }

        $this->log('exec_curl response: '.print_r($response, true));

        return $response;
    }

    /**
     * Build the notification domain.
     *
     * @return string
     */
    private function notification_domain()
    {
        return $this->settings['notification_scheme'].'://'.$this->settings['notification_host'];
    }

    /**
     * Build the ddn domain.
     *
     * @return string
     */
    private function ddn_domain()
    {
        return $this->settings['scheme'].'://'.$this->settings['host'].':'.$this->settings['port'];
    }

    /**
     * Build the required HMAC'd auth string.
     *
     * @param string $auth_key
     * @param string $auth_secret
     * @param string $request_method
     * @param string $request_path
     * @param array  $query_params
     * @param string $auth_version   [optional]
     * @param string $auth_timestamp [optional]
     *
     * @return string
     */
    public static function build_auth_query_string($auth_key, $auth_secret, $request_method, $request_path,
        $query_params = array(), $auth_version = '1.0', $auth_timestamp = null)
    {
        $params = array();
        $params['auth_key'] = $auth_key;
        $params['auth_timestamp'] = (is_null($auth_timestamp) ? time() : $auth_timestamp);
        $params['auth_version'] = $auth_version;

        $params = array_merge($params, $query_params);
        ksort($params);

        $string_to_sign = "$request_method\n".$request_path."\n".self::array_implode('=', '&', $params);

        $auth_signature = hash_hmac('sha256', $string_to_sign, $auth_secret, false);

        $params['auth_signature'] = $auth_signature;
        ksort($params);

        $auth_query_string = self::array_implode('=', '&', $params);

        return $auth_query_string;
    }

    /**
     * Implode an array with the key and value pair giving
     * a glue, a separator between pairs and the array
     * to implode.
     *
     * @param string $glue      The glue between key and value
     * @param string $separator Separator between pairs
     * @param array  $array     The array to implode
     *
     * @return string The imploded array
     */
    public static function array_implode($glue, $separator, $array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $string = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $string[] = "{$key}{$glue}{$val}";
        }

        return implode($separator, $string);
    }

    /**
     * Trigger an event by providing event name and payload.
     * Optionally provide a socket ID to exclude a client (most likely the sender).
     *
     * @param array|string $channels        A channel name or an array of channel names to publish the event on.
     * @param string       $event
     * @param mixed        $data            Event data
     * @param string       $socket_id       [optional]
     * @param bool         $debug           [optional]
     * @param bool         $already_encoded [optional]
     *
     * @return bool|array
     */
    public function trigger($channels, $event, $data, $socket_id = null, $debug = false, $already_encoded = false)
    {
        if (is_string($channels) === true) {
            $this->log('->trigger received string channel "'.$channels.'". Converting to array.');
            $channels = array($channels);
        }

        $this->validate_channels($channels);
        $this->validate_socket_id($socket_id);

        $query_params = array();

        $s_url = $this->settings['base_path'].'/events';

        $data_encoded = $already_encoded ? $data : json_encode($data);

        // json_encode might return false on failure
        if (!$data_encoded) {
            $this->Log('Failed to perform json_encode on the the provided data: '.print_r($data, true));
        }

        $post_params = array();
        $post_params['name'] = $event;
        $post_params['data'] = $data_encoded;
        $post_params['channels'] = $channels;

        if ($socket_id !== null) {
            $post_params['socket_id'] = $socket_id;
        }

        $post_value = json_encode($post_params);

        $query_params['body_md5'] = md5($post_value);

        $ch = $this->create_curl($this->ddn_domain(), $s_url, 'POST', $query_params);

        $this->log('trigger POST: '.$post_value);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->exec_curl($ch);

        if ($response['status'] === 200 && $debug === false) {
            return true;
        } elseif ($debug === true || $this->settings['debug'] === true) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     * Trigger multiple events at the same time.
     *
     * @param array $batch           An array of events to send
     * @param bool  $debug           [optional]
     * @param bool  $already_encoded [optional]
     *
     * @return bool|string
     */
    public function triggerBatch($batch = array(), $debug = false, $already_encoded = false)
    {
        $query_params = array();

        $s_url = $this->settings['base_path'].'/batch_events';

        if (!$already_encoded) {
            foreach ($batch as $key => $event) {
                if (!is_string($event['data'])) {
                    $batch[$key]['data'] = json_encode($event['data']);
                }
            }
        }

        $post_params = array();
        $post_params['batch'] = $batch;

        $post_value = json_encode($post_params);

        $query_params['body_md5'] = md5($post_value);

        $ch = $this->create_curl($this->ddn_domain(), $s_url, 'POST', $query_params);

        $this->log('trigger POST: '.$post_value);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->exec_curl($ch);

        if ($response['status'] === 200 && $debug === false) {
            return true;
        } elseif ($debug === true || $this->settings['debug'] === true) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     *	Fetch channel information for a specific channel.
     *
     * @param string $channel The name of the channel
     * @param array  $params  Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     *	@return object
     */
    public function get_channel_info($channel, $params = array())
    {
        $this->validate_channel($channel);

        $response = $this->get('/channels/'.$channel, $params);

        if ($response['status'] === 200) {
            $response = json_decode($response['body']);
        } else {
            $response = false;
        }

        return $response;
    }

    /**
     * Fetch a list containing all channels.
     *
     * @param array $params Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @return array
     */
    public function get_channels($params = array())
    {
        $response = $this->get('/channels', $params);

        if ($response['status'] === 200) {
            $response = json_decode($response['body']);
            $response->channels = get_object_vars($response->channels);
        } else {
            $response = false;
        }

        return $response;
    }

    /**
     * GET arbitrary REST API resource using a synchronous http client.
     * All request signing is handled automatically.
     *
     * @param string path Path excluding /apps/APP_ID
     * @param params array API params (see http://pusher.com/docs/rest_api)
     *
     * @return See Pusher API docs
     */
    public function get($path, $params = array())
    {
        $s_url = $this->settings['base_path'].$path;

        $ch = $this->create_curl($this->ddn_domain(), $s_url, 'GET', $params);

        $response = $this->exec_curl($ch);

        if ($response['status'] === 200) {
            $response['result'] = json_decode($response['body'], true);
        } else {
            $response = false;
        }

        return $response;
    }

    /**
     * Creates a socket signature.
     *
     * @param string $socket_id
     * @param string $custom_data
     *
     * @return string
     */
    public function socket_auth($channel, $socket_id, $custom_data = null)
    {
        $this->validate_channel($channel);
        $this->validate_socket_id($socket_id);

        if ($custom_data) {
            $signature = hash_hmac('sha256', $socket_id.':'.$channel.':'.$custom_data, $this->settings['secret'], false);
        } else {
            $signature = hash_hmac('sha256', $socket_id.':'.$channel, $this->settings['secret'], false);
        }

        $signature = array('auth' => $this->settings['auth_key'].':'.$signature);
        // add the custom data if it has been supplied
        if ($custom_data) {
            $signature['channel_data'] = $custom_data;
        }

        return json_encode($signature);
    }

    /**
     * Creates a presence signature (an extension of socket signing).
     *
     * @param string $socket_id
     * @param string $user_id
     * @param mixed  $user_info
     *
     * @return string
     */
    public function presence_auth($channel, $socket_id, $user_id, $user_info = null)
    {
        $user_data = array('user_id' => $user_id);
        if ($user_info) {
            $user_data['user_info'] = $user_info;
        }

        return $this->socket_auth($channel, $socket_id, json_encode($user_data));
    }

    /**
     * Send a native notification via the Push Notifications Api.
     *
     * @param array $interests
     * @param array $payload
     * @param bool  $debug
     *
     * @throws PusherException if validation fails.
     *
     * @return bool|string
     **/
    public function notify($interests, $data = array(), $debug = false)
    {
        $query_params = array();

        if (is_string($interests)) {
            $this->log('->notify received string interests "'.$interests.'". Converting to array.');
            $interests = array($interests);
        }

        if (count($interests) === 0) {
            throw new PusherException('$interests array must not be empty');
        }

        $data['interests'] = $interests;

        $post_value = json_encode($data);

        $query_params['body_md5'] = md5($post_value);

        $notification_path = '/server_api/v1'.$this->settings['base_path'].'/notifications';
        $ch = $this->create_curl($this->notification_domain(), $notification_path, 'POST', $query_params);

        $this->log('trigger POST (Native notifications): '.$post_value);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->exec_curl($ch);

        if ($response['status'] === 202 && $debug === false) {
            return true;
        } elseif ($debug === true || $this->settings['debug'] === true) {
            return $response;
        } else {
            return false;
        }
    }
}
