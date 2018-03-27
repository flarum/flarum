<?php

    class PusherAuthQueryString extends PHPUnit_Framework_TestCase
    {
        protected function setUp()
        {
            $this->pusher = new Pusher('thisisaauthkey', 'thisisasecret', 1, true);
        }

        public function testArrayImplode()
        {
            $val = array('testKey' => 'testValue');

            $expected = 'testKey=testValue';
            $actual = Pusher::array_implode('=', '&', $val);

            $this->assertEquals($actual,
                $expected,
                'auth signature valid');
        }

        public function testArrayImplodeWithTwoValues()
        {
            $val = array('testKey' => 'testValue', 'testKey2' => 'testValue2');

            $expected = 'testKey=testValue&testKey2=testValue2';
            $actual = Pusher::array_implode('=', '&', $val);

            $this->assertEquals($actual,
                $expected,
                'auth signature valid');
        }

        public function testGenerateSignature()
        {
            $time = time();
            $auth_version = '1.0';
            $method = 'POST';
            $auth_key = 'thisisaauthkey';
            $auth_secret = 'thisisasecret';
            $request_path = '/channels/test_channel/events';
            $query_params = array(
            'name' => 'an_event',
          );
            $auth_query_string = Pusher::build_auth_query_string(
          $auth_key,
          $auth_secret,
          $method,
          $request_path,
          $query_params,
          $auth_version,
          $time);

            $expected_to_sign = "POST\n$request_path\nauth_key=$auth_key&auth_timestamp=$time&auth_version=$auth_version&name=an_event";
            $expected_auth_signature = hash_hmac('sha256', $expected_to_sign, $auth_secret, false);
            $expected_query_string = "auth_key=$auth_key&auth_signature=$expected_auth_signature&auth_timestamp=$time&auth_version=$auth_version&name=an_event";

            $this->assertEquals($auth_query_string,
                $expected_query_string,
                'auth signature valid');
        }
    }
