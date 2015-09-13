<?php

namespace tourze\StatClient;

use PHPUnit_Framework_TestCase;

/**
 * Class StatClientTest
 *
 * @package tourze\StatClient
 */
class StatClientTest extends PHPUnit_Framework_TestCase
{

    /**
     * 测试发送功能
     */
    public function testTick()
    {
        StatClient::tick('User', 'login');
        $success = rand(0, 10) >= 5;
        sleep(1);
        $result = StatClient::report('User', 'login', $success, $success ? 1 : -1, $success ? 'success' : 'failed');

        $this->assertTrue($result);
    }

}
