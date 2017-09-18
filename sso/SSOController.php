<?php

class SSOController
{
    const REMEMBER_ME_KEY = 'flarum_remember';

    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/config.php';
    }

    /**
     * Call this method after your user is successfully authenticated.
     *
     * @param $username
     * @param $email
     * @param $avatarUrl
     */
    public function login($username, $email, $avatarUrl)
    {
        $password = $this->createPassword($username);
        $token = $this->getToken($username, $password);

        if (empty($token)) {
            $this->signup($username, $password, $email, $avatarUrl);
            $token = $this->getToken($username, $password);
        }

        $this->setRememberMeCookie($token);
    }

    /**
     * Call this method after you logged out your user.
     */
    public function logout()
    {
        $this->removeRememberMeCookie();
    }

    /**
     * Redirects a user back to the forum.
     * @param $targetUrl
     */
    public function redirectToForum($targetUrl)
    {
        $targetUrl = (!is_null($targetUrl)) ? $targetUrl : '';
        header('Location: ' . $this->config['flarum_url'] . $targetUrl);
        die();
    }

    private function createPassword($username)
    {
        return hash('sha256', $username . $this->config['password_token']);
    }

    private function getToken($username, $password)
    {
        $data = [
            'identification' => $username,
            'password' => $password,
            'lifetime' => $this->getLifetimeInSeconds(),
        ];

        $response = $this->sendPostRequest('/api/token', $data);

        return isset($response['token']) ? $response['token'] : '';
    }

    private function signup($username, $password, $email, $avatarUrl)
    {
        $data = [
            "data" => [
                "type" => "users",
                "attributes" => [
                    "username" => $username,
                    "password" => $password,
                    "email" => $email,
                    "avatarUrl" => $avatarUrl
                ]
            ]
        ];

        $response = $this->sendPostRequest('/api/users', $data);

        return isset($response['data']['id']);
    }

    private function sendPostRequest($path, $data)
    {
        $data_string = json_encode($data);

        $ch = curl_init($this->config['flarum_url'] . $path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'Authorization: Token ' . $this->config['flarum_api_key'] . '; userId=1',
            ]
        );
        $result = curl_exec($ch);

        return json_decode($result, true);
    }

    private function setRememberMeCookie($token)
    {
        $this->setCookie(self::REMEMBER_ME_KEY, $token, time() + $this->getLifetimeInSeconds());
    }

    private function removeRememberMeCookie()
    {
        unset($_COOKIE[self::REMEMBER_ME_KEY]);
        $this->setCookie(self::REMEMBER_ME_KEY, '', time() - 10);
    }

    private function setCookie($key, $token, $time)
    {
        setcookie($key, $token, $time, '/', $this->config['root_domain']);
    }

    private function getLifetimeInSeconds()
    {
        return $this->config['lifetime_in_days'] * 60 * 60 * 24;
    }
}
