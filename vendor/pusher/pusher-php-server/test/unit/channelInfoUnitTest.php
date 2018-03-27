<?php

    class PusherChannelInfoUnitTest extends PHPUnit_Framework_TestCase
    {
        protected function setUp()
        {
            $this->pusher = new Pusher('thisisaauthkey', 'thisisasecret', 1, true);
        }

        /**
         * @expectedException PusherException
         */
        public function testTrailingColonChannelThrowsException()
        {
            $this->pusher->get_channel_info('test_channel:');
        }

        /**
         * @expectedException PusherException
         */
        public function testLeadingColonChannelThrowsException()
        {
            $this->pusher->get_channel_info(':test_channel');
        }

        /**
         * @expectedException PusherException
         */
        public function testLeadingColonNLChannelThrowsException()
        {
            $this->pusher->get_channel_info(':\ntest_channel');
        }

        /**
         * @expectedException PusherException
         */
        public function testTrailingColonNLChannelThrowsException()
        {
            $this->pusher->get_channel_info('test_channel\n:');
        }
    }
