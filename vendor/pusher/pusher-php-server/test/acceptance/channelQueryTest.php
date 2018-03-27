<?php

    class PusherChannelQueryTest extends PHPUnit_Framework_TestCase
    {
        protected function setUp()
        {
            $this->pusher = new Pusher(PUSHERAPP_AUTHKEY, PUSHERAPP_SECRET, PUSHERAPP_APPID, true, PUSHERAPP_HOST);
            $this->pusher->set_logger(new TestLogger());
        }

        public function testChannelInfo()
        {
            $response = $this->pusher->get_channel_info('channel-test');

            //print_r( $response );

            $this->assertObjectHasAttribute('occupied', $response, 'class has occupied attribute');
        }

        public function testChannelList()
        {
            $result = $this->pusher->get_channels();
            $channels = $result->channels;

             // print_r( $channels );

            foreach ($channels as $channel_name => $channel_info) {
                echo  "channel_name: $channel_name\n";
                echo  'channel_info: ';
                print_r($channel_info);
                echo  "\n\n";
            }

            $this->assertTrue(is_array($channels), 'channels is an array');
        }

        public function testFilterByPrefixNoChannels()
        {
            $options = array(
                'filter_by_prefix' => '__fish',
            );
            $result = $this->pusher->get_channels($options);

// print_r( $result );

          $channels = $result->channels;

          // print_r( $channels );

            $this->assertTrue(is_array($channels), 'channels is an array');
            $this->assertEquals(0, count($channels), 'should be an empty array');
        }

        public function testFilterByPrefixOneChannel()
        {
            $options = array(
                'filter_by_prefix' => 'test_',
            );
            $result = $this->pusher->get_channels($options);

// print_r( $result );

          $channels = $result->channels;

          // print_r( $channels );

            $this->assertEquals(1, count($channels), 'channels have a single test-channel present. For this test to pass you must have your API Access setting open for the application you are testing against');
        }

        public function test_providing_info_parameter_with_prefix_query_fails_for_public_channel()
        {
            $options = array(
                'filter_by_prefix' => 'test_',
                'info'             => 'user_count',
            );
            $result = $this->pusher->get_channels($options);

            $this->assertFalse($result, 'query should fail');
        }

        public function test_channel_list_using_generic_get()
        {
            $response = $this->pusher->get('/channels');

            $this->assertEquals($response['status'], 200);

            $result = $response['result'];

            $channels = $result['channels'];

            $this->assertEquals(1, count($channels), 'channels have a single test-channel present. For this test to pass you must have your API Access setting open for the application you are testing against');

            $test_channel = $channels['test_channel'];

            $this->assertEquals(0, count($test_channel));
        }

        public function test_channel_list_using_generic_get_and_prefix_param()
        {
            $response = $this->pusher->get('/channels', array('filter_by_prefix' => 'test_'));

            $this->assertEquals($response['status'], 200);

            $result = $response['result'];

            $channels = $result['channels'];

            $this->assertEquals(1, count($channels), 'channels have a single test-channel present. For this test to pass you must have your API Access setting open for the application you are testing against');

            $test_channel = $channels['test_channel'];

            $this->assertEquals(0, count($test_channel));
        }

        public function test_single_channel_info_using_generic_get()
        {
            $response = $this->pusher->get('/channels/channel-test');

            $this->assertEquals($response['status'], 200);

            $result = $response['result'];

            $this->assertArrayHasKey('occupied', $result, 'class has occupied attribute');
        }
    }
