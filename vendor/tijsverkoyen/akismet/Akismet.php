<?php
namespace TijsVerkoyen\Akismet;

/**
 * Akismet class
 *
 * @author Tijs Verkoyen <php-akismet@verkoyen.eu>
 * @version 1.1.0
 * @copyright Copyright (c) Tijs Verkoyen. All rights reserved.
 * @license BSD License
 */
class Akismet
{
    // internal constant to enable/disable debugging
    const DEBUG = false;

    // url for the api
    const API_URL = 'http://rest.akismet.com';

    // port for the api
    const API_PORT = 80;

    // version of the api
    const API_VERSION = '1.1';

    // current version
    const VERSION = '1.1.0';

    /**
     * The key for the API
     * @var string
     */
    private $apiKey;

    /**
     * The timeout
     * @var int
     */
    private $timeOut = 60;

    /**
     * The user agent
     * @var string
     */
    private $userAgent;

    /**
     * The url
     * @var string
     */
    private $url;

// class methods
    /**
     * Default constructor
     * Creates an instance of the Akismet Class.
     *
     * @param string $apiKey API key being verified for use with the API.
     * @param string $url    The front page or home URL of the instance making
     *                       the request. For a blog or wiki this would be the
     *                       front page. Note: Must be a full URI, including
     *                       http://.
     */
    public function __construct($apiKey, $url)
    {
        $this->setApiKey($apiKey);
        $this->setUrl($url);
    }

    /**
     * Make the call
     * @param  string          $url          URL to call.
     * @param  array[optional] $aParameters  The parameters to pass.
     * @param  bool[optional]  $authenticate Should we authenticate?
     * @return string
     */
    private function doCall($url, $aParameters = array(), $authenticate = true)
    {
        // redefine
        $url = (string) $url;
        $aParameters = (array) $aParameters;
        $authenticate = (bool) $authenticate;

        // build url
        $url = self::API_URL . '/' . self::API_VERSION . '/' . $url;

        // add key in front of url
        if ($authenticate) {
            // get api key
            $apiKey = $this->getApiKey();

            // validate apiKey
            if ($apiKey == '') throw new Exception('Invalid API-key');

            // prepend key
            $url = str_replace('http://', 'http://' . $apiKey . '.', $url);
        }

        // add url into the parameters
        $aParameters['blog'] = $this->getUrl();

        // set options
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_PORT] = self::API_PORT;
        $options[CURLOPT_USERAGENT] = $this->getUserAgent();
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
        }
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $aParameters;

        // speed up things, use HTTP 1.0
        $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;

        // init
        $curl = curl_init();

        // set options
        curl_setopt_array($curl, $options);

        // execute
        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);

        // fetch errors
        $errorNumber = curl_errno($curl);
        $errorMessage = curl_error($curl);

        // close
        curl_close($curl);

        // invalid headers
        if (!in_array($headers['http_code'], array(0, 200))) {
            // should we provide debug information
            if (self::DEBUG) {
                // make it output proper
                echo '<pre>';

                // dump the header-information
                var_dump($headers);

                // dump the raw response
                var_dump($response);

                // end proper format
                echo '</pre>';

                // stop the script
                exit();
            }

            // throw error
            throw new Exception(null, (int) $headers['http_code']);
        }

        // error?
        if ($errorNumber != '') throw new Exception($errorMessage, $errorNumber);

        // return
        return $response;
    }

    /**
     * Get the API-key that will be used
     *
     * @return string
     */
    private function getApiKey()
    {
        return (string) $this->apiKey;
    }

    /**
     * Get the timeout that will be used
     *
     * @return int
     */
    public function getTimeOut()
    {
        return (int) $this->timeOut;
    }

    /**
     * Get the url of the instance making the request
     *
     * @return string
     */
    public function getUrl()
    {
        return (string) $this->url;
    }

    /**
     * Get the useragent that will be used.
     * Our version will be prepended to yours. It will look like:
     * "PHP Akismet/<version> <your-user-agent>"
     *
     * @return string
     */
    public function getUserAgent()
    {
        return (string) 'PHP Akismet/' . self::VERSION . ' ' . $this->userAgent;
    }

    /**
     * Set API key that has to be used
     *
     * @param string $apiKey API key to use.
     */
    private function setApiKey($apiKey)
    {
        $this->apiKey = (string) $apiKey;
    }

    /**
     * Set the timeout
     * After this time the request will stop. You should handle any errors
     * triggered by this.
     *
     * @param int $seconds The timeout in seconds.
     */
    public function setTimeOut($seconds)
    {
        $this->timeOut = (int) $seconds;
    }

    /**
     * Set the url of the instance making the request
     * @param string $url The URL making the request.
     */
    private function setUrl($url)
    {
        $this->url = (string) $url;
    }

    /**
     * Set the user-agent for you application
     * It will be appended to ours, the result will look like:
     * "PHP Akismet/<version> <your-user-agent>"
     *
     * @param string $userAgent The user-agent, it should look like:
     *                          <app-name>/<app-version>.
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = (string) $userAgent;
    }

    // api methods
    /**
     * Verifies the key
     * @return bool if the key is valid it will return true, otherwise false
     *              will be returned.
     */
    public function verifyKey()
    {
        // possible answers
        $aPossibleResponses = array('valid', 'invalid');

        // build parameters
        $aParameters['key'] = $this->getApiKey();

        // make the call
        $response = $this->doCall('verify-key', $aParameters, false);

        // validate response
        if (!in_array($response, $aPossibleResponses)) {
            throw new Exception($response, 400);
        }

        // valid key
        if ($response == 'valid') return true;

        // fallback
        return false;
    }

    /**
     * Check if the comment is spam or not
     * This is basically the core of everything. This call takes a number of
     * arguments and characteristics about the submitted content and then
     * returns a thumbs up or thumbs down.
     * Almost everything is optional, but performance can drop dramatically if
     * you exclude certain elements.
     * REMARK: If you are having trouble triggering you can send
     * "viagra-test-123" as the author and it will trigger a true response,
     * always.
     *
     * @param string[optional] $content   The content that was submitted.
     * @param string[optional] $author    The name.
     * @param string[optional] $email     The email address.
     * @param string[optional] $url       The URL.
     * @param string[optional] $permalink The permanent location of the entry
     *                                    the comment was submitted to.
     * @param string[optional] $type The type, can be blank, comment,
     *                                    trackback, pingback, or a made up
     *                                    value like "registration".
     * @return bool If the comment is spam true will be
     *                                    returned, otherwise false.
     */
    public function isSpam(
        $content,
        $author = null,
        $email = null,
        $url = null,
        $permalink = null,
        $type = null
    )
    {
        // possible answers
        $aPossibleResponses = array('true', 'false');

        // redefine
        $content = (string) $content;
        $author = (string) $author;
        $email = (string) $email;
        $url = (string) $url;
        $permalink = (string) $permalink;
        $type = (string) $type;

        // get stuff from the $_SERVER-array
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else $ip = '';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = (string) $_SERVER['HTTP_USER_AGENT'];
        } else $userAgent = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referrer = (string) $_SERVER['HTTP_REFERER'];
        } else $referrer = '';

        // build parameters
        $aParameters['user_ip'] = $ip;
        $aParameters['user_agent'] = $userAgent;
        if ($referrer != '') $aParameters['referrer'] = $referrer;
        if ($permalink != '') $aParameters['permalink'] = $permalink;
        if ($type != '') $aParameters['comment_type'] = $type;
        if ($author != '') $aParameters['comment_author'] = $author;
        if ($email != '') $aParameters['comment_author_email'] = $email;
        if ($url != '') $aParameters['comment_author_url'] = $url;
        $aParameters['comment_content'] = $content;

        // add all stuff from $_SERVER
        foreach ($_SERVER as $key => $value) {
            // keys to ignore
            $aKeysToIgnore = array(
                'HTTP_COOKIE', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_HOST',
                'HTTP_MAX_FORWARDS', 'HTTP_X_FORWARDED_SERVER',
                'REDIRECT_STATUS', 'SERVER_PORT', 'PATH', 'DOCUMENT_ROOT',
                'SERVER_ADMIN', 'QUERY_STRING', 'PHP_SELF', 'argv', 'argc',
                'SCRIPT_FILENAME', 'SCRIPT_NAME'
            );

            // add to parameters if not in ignore list
            if (!in_array($key, $aKeysToIgnore)) $aParameters[$key] = $value;
        }

        // make the call
        $response = $this->doCall('comment-check', $aParameters);

        // validate response
        if (!in_array($response, $aPossibleResponses)) {
            throw new Exception($response, 400);
        }

        // process response
        if ($response == 'true') return true;

        // fallback
        return false;
    }

    /**
     * Submit ham to Akismet
     * This call is intended for the marking of false positives, things that
     * were incorrectly marked as spam.
     * @param string           $userIp    The address of the comment submitter.
     * @param string           $userAgent The agent information.
     * @param string[optional] $content   The content that was submitted.
     * @param string[optional] $author    The name of the author.
     * @param string[optional] $email     The email address.
     * @param string[optional] $url       The URL.
     * @param string[optional] $permalink The permanent location of the entry
     *                                    the comment was submitted to.
     * @param string[optional] $type The type, can be blank, comment,
     *                                    trackback, pingback, or a made up
     *                                    value like "registration".
     * @param string[optional] $referrer The content of the HTTP_REFERER
     *                                    header should be sent here.
     * @param array[optional] $others Extra data (the variables from
     *                                    $_SERVER).
     * @return bool If everything went fine true will be
     *                                    returned, otherwise an exception
     *                                    will be triggered.
     */
    public function submitHam(
        $userIp,
        $userAgent,
        $content,
        $author = null,
        $email = null,
        $url = null,
        $permalink = null,
        $type = null,
        $referrer = null,
        $others = null
    )
    {
        // possible answers
        $aPossibleResponses = array('Thanks for making the web a better place.');

        // redefine
        $userIp = (string) $userIp;
        $userAgent = (string) $userAgent;
        $content = (string) $content;
        $author = (string) $author;
        $email = (string) $email;
        $url = (string) $url;
        $permalink = (string) $permalink;
        $type = (string) $type;
        $referrer = (string) $referrer;
        $others = (array) $others;

        // build parameters
        $aParameters['user_ip'] = $userIp;
        $aParameters['user_agent'] = $userAgent;
        if ($referrer != '') $aParameters['referrer'] = $referrer;
        if ($permalink != '') $aParameters['permalink'] = $permalink;
        if ($type != '') $aParameters['comment_type'] = $type;
        if ($author != '') $aParameters['comment_author'] = $author;
        if ($email != '') $aParameters['comment_author_email'] = $email;
        if ($url != '') $aParameters['comment_author_url'] = $url;
        $aParameters['comment_content'] = $content;

        // add other parameters
        foreach ($others as $key => $value) $aParameters[$key] = $value;

        // make the call
        $response = $this->doCall('submit-ham', $aParameters);

        // validate response
        if (in_array($response, $aPossibleResponses)) return true;

        // fallback
        throw new Exception($response);
    }

    /**
     * Submit spam to Akismet
     * This call is for submitting comments that weren't marked as spam but
     * should have been.
     * @param string           $userIp    The address of the comment submitter.
     * @param string           $userAgent The agent information.
     * @param string[optional] $content   The content that was submitted.
     * @param string[optional] $author    The name of the author.
     * @param string[optional] $email     The email address.
     * @param string[optional] $url       The URL.
     * @param string[optional] $permalink The permanent location of the entry
     *                                    the comment was submitted to.
     * @param string[optional] $type The type, can be blank, comment,
     *                                    trackback, pingback, or a made up
     *                                    value like "registration".
     * @param string[optional] $referrer The content of the HTTP_REFERER
     *                                    header should be sent here.
     * @param array[optional] $others Extra data (the variables from
     *                                    $_SERVER).
     * @return bool If everything went fine true will be
     *                                    returned, otherwise an exception
     *                                    will be triggered.
     */
    public function submitSpam(
        $userIp,
        $userAgent,
        $content,
        $author = null,
        $email = null,
        $url = null,
        $permalink = null,
        $type = null,
        $referrer = null,
        $others = null
    )
    {
        // possible answers
        $aPossibleResponses = array('Thanks for making the web a better place.');

        // redefine
        $userIp = (string) $userIp;
        $userAgent = (string) $userAgent;
        $content = (string) $content;
        $author = (string) $author;
        $email = (string) $email;
        $url = (string) $url;
        $permalink = (string) $permalink;
        $type = (string) $type;
        $referrer = (string) $referrer;
        $others = (array) $others;

        // build parameters
        $aParameters['user_ip'] = $userIp;
        $aParameters['user_agent'] = $userAgent;
        if ($referrer != '') $aParameters['referrer'] = $referrer;
        if ($permalink != '') $aParameters['permalink'] = $permalink;
        if ($type != '') $aParameters['comment_type'] = $type;
        if ($author != '') $aParameters['comment_author'] = $author;
        if ($email != '') $aParameters['comment_author_email'] = $email;
        if ($url != '') $aParameters['comment_author_url'] = $url;
        $aParameters['comment_content'] = $content;

        // add other parameters
        foreach ($others as $key => $value) $aParameters[$key] = $value;

        // make the call
        $response = $this->doCall('submit-spam', $aParameters);

        // validate response
        if (in_array($response, $aPossibleResponses)) return true;

        // fallback
        throw new Exception($response);
    }
}
