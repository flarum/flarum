<?php

    class PusherPushTest extends PHPUnit_Framework_TestCase
    {
        protected function setUp()
        {
            if (PUSHERAPP_AUTHKEY === '' || PUSHERAPP_SECRET === '' || PUSHERAPP_APPID === '') {
                $this->markTestSkipped('Please set the
					PUSHERAPP_AUTHKEY, PUSHERAPP_SECRET and
					PUSHERAPP_APPID keys.');
            } else {
                $this->pusher = new Pusher(PUSHERAPP_AUTHKEY, PUSHERAPP_SECRET, PUSHERAPP_APPID, true, PUSHERAPP_HOST);
                $this->pusher->set_logger(new TestLogger());
            }
        }

        public function testObjectConstruct()
        {
            $this->assertNotNull($this->pusher, 'Created new Pusher object');
        }

        public function testStringPush()
        {
            $string_trigger = $this->pusher->trigger('test_channel', 'my_event', 'Test string');
            $this->assertTrue($string_trigger, 'Trigger with string payload');
        }

        public function testArrayPush()
        {
            $structure_trigger = $this->pusher->trigger('test_channel', 'my_event', array('test' => 1));
            $this->assertTrue($structure_trigger, 'Trigger with structured payload');
        }

        public function testEncryptedPush()
        {
            $options = array(
                'encrypted' => true,
                'host'      => PUSHERAPP_HOST,
            );
            $pusher = new Pusher(PUSHERAPP_AUTHKEY, PUSHERAPP_SECRET, PUSHERAPP_APPID, $options);
            $pusher->set_logger(new TestLogger());

            $structure_trigger = $pusher->trigger('test_channel', 'my_event', array('encrypted' => 1));
            $this->assertTrue($structure_trigger, 'Trigger with over encrypted connection');
        }

        public function testSendingOver10kBMessageReturns413()
        {
            $data = str_pad('', 11 * 1024, 'a');
            echo  'sending data of size: '.mb_strlen($data, '8bit');
            $response = $this->pusher->trigger('test_channel', 'my_event', $data, null, true);
            $this->assertEquals(413, $response['status'], '413 HTTP status response expected');
        }

        /**
         * @expectedException PusherException
         */
        public function test_triggering_event_on_over_100_channels_throws_exception()
        {
            $channels = array();
            while (count($channels) <= 101) {
                $channels[] = ('channel-'.count($channels));
            }
            $data = array('event_name' => 'event_data');
            $response = $this->pusher->trigger($channels, 'my_event', $data);
        }

        public function test_triggering_event_on_multiple_channels()
        {
            $data = array('event_name' => 'event_data');
            $channels = array('test_channel_1', 'test_channel_2');
            $response = $this->pusher->trigger($channels, 'my_event', $data);

            $this->assertTrue($response);
        }
    }
