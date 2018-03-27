<?php

    class PusherNotificationsUnitTest extends PHPUnit_Framework_TestCase
    {
        protected function setUp()
        {
            $this->pusher = new Pusher('thisisaauthkey', 'thisisasecret', 1);
        }

        /**
         * @expectedException PusherException
         */
        public function testInvalidEmptyInterests()
        {
            $this->pusher->notify(array(), array('foo' => 'bar'));
        }
    }
