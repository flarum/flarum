<?php

    class PusherBatchPushTest extends PHPUnit_Framework_TestCase
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

        public function testSimplePush()
        {
            $batch = array();
            $batch[] = array('channel' => 'test_channel', 'name' => 'my_event', 'data' => array('my' => 'data'));
            $string_trigger = $this->pusher->triggerBatch($batch);
            $this->assertTrue($string_trigger, 'Trigger with string payload');
        }

        public function testEncryptedPush()
        {
            $options = array(
                'encrypted' => true,
                'host'      => PUSHERAPP_HOST,
            );
            $pusher = new Pusher(PUSHERAPP_AUTHKEY, PUSHERAPP_SECRET, PUSHERAPP_APPID, $options);
            $pusher->set_logger(new TestLogger());

            $batch = array();
            $batch[] = array('channel' => 'test_channel', 'name' => 'my_event', 'data' => array('my' => 'data'));
            $string_trigger = $this->pusher->triggerBatch($batch);
            $this->assertTrue($string_trigger, 'Trigger with string payload');
        }

        public function testSendingOver10kBMessageReturns413()
        {
            $data = str_pad('', 11 * 1024, 'a');
            $batch = array();
            $batch[] = array('channel' => 'test_channel', 'name' => 'my_event', 'data' => $data);
            $response = $this->pusher->triggerBatch($batch, true, true);
            $this->assertContains('content of this event', $response['body']);
            $this->assertEquals(413, $response['status'], '413 HTTP status response expected');
        }

        public function testSendingOver10messagesReturns400()
        {
            $batch = array();
            foreach (range(1, 11) as $i) {
                $batch[] = array('channel' => 'test_channel', 'name' => 'my_event', 'data' => array('index' => $i));
            }
            $response = $this->pusher->triggerBatch($batch, true, false);
            $this->assertContains('Batch too large', $response['body']);
            $this->assertEquals(400, $response['status'], '400 HTTP status response expected');
        }
    }
