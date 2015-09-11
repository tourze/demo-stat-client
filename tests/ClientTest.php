<?php

namespace tourze\Stat;

use PHPUnit_Framework_TestCase;

/**
 * Class ClientTest
 *
 * @package tourze\Stat
 */
class ClientTest extends PHPUnit_Framework_TestCase
{

    /**
     * 测试发送功能
     */
    public function testTick()
    {
        Client::tick('User', 'login');
        $success = rand(0, 10) >= 5;
        sleep(1);
        $result = Client::report('User', 'login', $success, $success ? 1 : -1, $success ? 'success' : 'failed');

        $this->assertTrue($result);
    }

}
