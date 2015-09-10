<?php

namespace tourze\Tourze\Statistic;
use tourze\Base\Helper\Arr;
use tourze\Http\Request;

/**
 * 统计客户端，调用方法：
 *
 * // 统计开始
 * Client::tick("User", 'getInfo');
 * // 统计的产生，接口调用是否成功、错误码、错误日志
 * $success = true; $code = 0; $msg = '';
 * // 假如有个User::getInfo方法要监控
 * $user_info = User::getInfo();
 * if( ! $user_info)
 * {
 *     // 标记失败
 *     $success = false;
 *     // 获取错误码，假如getErrCode()获得
 *     $code = User::getErrCode();
 *     // 获取错误日志，假如getErrMsg()获得
 *     $msg = User::getErrMsg();
 * }
 * // 上报结果
 * Client::report('User', 'getInfo', $success, $code, $msg);
 *
 * @author workerman.net
 */
class Client
{

    /**
     * @var string 默认上报地址
     */
    public static $reportAddress = 'udp://127.0.0.1:55656';

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
    public static function tick($module = '', $interface = '')
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

        $reportAddress = $reportAddress ? $reportAddress : self::$reportAddress;
        if (isset(self::$timeMap[$module][$interface]) && self::$timeMap[$module][$interface] > 0)
        {
            $time_start = self::$timeMap[$module][$interface];
            self::$timeMap[$module][$interface] = 0;
        }
        else if (isset(self::$timeMap['']['']) && self::$timeMap[''][''] > 0)
        {
            $time_start = self::$timeMap[''][''];
            self::$timeMap[''][''] = 0;
        }
        else
        {
            $time_start = microtime(true);
        }

        $cost_time = microtime(true) - $time_start;

        $binData = Protocol::encode($module, $interface, $cost_time, $success, $code, $msg);

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
