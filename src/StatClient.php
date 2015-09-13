<?php

namespace tourze\StatClient;

use tourze\Base\Base;
use tourze\Base\Config;
use tourze\Base\Helper\Arr;
use tourze\Http\Request;

/**
 * 统计客户端
 *
 * @package tourze\StatClient
 */
class StatClient
{

    /**
     * [module=>[interface=>time_start, interface=>time_start ...], module=>[interface=>time_start ..], ... ]
     *
     * @var array
     */
    protected static $timeMap = [];

    /**
     * 模块接口上报消耗时间记时
     *
     * @param string $module
     * @param string $interface
     * @return mixed
     */
    public static function tick($module, $interface)
    {
        if ( ! isset(self::$timeMap[$module]))
        {
            self::$timeMap[$module] = [];
        }
        self::$timeMap[$module][$interface] = microtime(true);
    }

    /**
     * 上报统计数据
     *
     * @param string $module
     * @param string $interface
     * @param bool   $success
     * @param int    $code
     * @param string $msg
     * @param string $reportAddress
     * @return boolean
     */
    public static function report($module, $interface, $success, $code, $msg, $reportAddress = '')
    {
        // 如果msg是个数组，那么要额外处理转换成字符串
        if (is_array($msg))
        {
            // $msg格式为[':message', [':message' => 'TEST']]
            if (count($msg) == 2 && ! Arr::isAssoc($msg) && is_array($msg[1]))
            {
                $msg = __($msg[0], $msg[1]);
            }
        }

        if (strpos($msg, '[ip]') !== false)
        {
            $msg = str_replace('[ip]', Request::$clientIp, $msg);
        }
        if (strpos($msg, '[ua]') !== false)
        {
            $msg = str_replace('[ua]', Request::$userAgent, $msg);
        }

        $reportAddress = $reportAddress ? $reportAddress : Config::load('statClient')->get('ip');
        if (isset(self::$timeMap[$module][$interface]) && self::$timeMap[$module][$interface] > 0)
        {
            $startTime = self::$timeMap[$module][$interface];
            self::$timeMap[$module][$interface] = 0;
        }
        else if (isset(self::$timeMap['']['']) && self::$timeMap[''][''] > 0)
        {
            $startTime = self::$timeMap[''][''];
            self::$timeMap[''][''] = 0;
        }
        else
        {
            $startTime = microtime(true);
        }

        //echo "\n";
        //echo $startTime . "\n";

        $endTime = microtime(true);

        //echo $endTime . "\n";

        $costTime = $endTime - $startTime;

        //echo $costTime . "\n";

        $binData = Protocol::encode($module, $interface, $costTime, $success, $code, $msg);
        Base::getLog()->debug(__METHOD__ . ' prepare bin data', [
            'bin' => $binData,
        ]);

        return self::sendData($reportAddress, $binData);
    }

    /**
     * 发送数据给统计系统
     *
     * @param string $address
     * @param string $buffer
     * @return boolean
     */
    public static function sendData($address, $buffer)
    {
        $socket = stream_socket_client($address);
        if ( ! $socket)
        {
            return false;
        }
        return stream_socket_sendto($socket, $buffer) == strlen($buffer);
    }

}
