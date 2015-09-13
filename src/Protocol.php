<?php

namespace tourze\StatClient;

/**
 * 协议相关内容
 *
 * struct statistic Protocol
 * {
 *     unsigned char module_name_len;
 *     unsigned char interface_name_len;
 *     float cost_time;
 *     unsigned char success;
 *     int code;
 *     unsigned short msg_len;
 *     unsigned int time;
 *     char[module_name_len] module_name;
 *     char[interface_name_len] interface_name;
 *     char[msg_len] msg;
 * }
 *
 *
 * @package tourze\StatClient
 */
class Protocol
{
    /**
     * @var int 包头长度
     */
    const PACKAGE_FIXED_LENGTH = 17;

    /**
     * @var int udp包最大长度
     */
    const MAX_UDP_PACKAGE_SIZE = 65507;

    /**
     * @var int char类型能保存的最大数值
     */
    const MAX_CHAR_VALUE = 255;

    /**
     * @var int unsigned short 能保存的最大数值
     */
    const MAX_UNSIGNED_SHORT_VALUE = 65535;

    /**
     * 编码
     *
     * @param string $module
     * @param string $interface
     * @param float  $costTime
     * @param int    $success
     * @param int    $code
     * @param string $msg
     * @return string
     */
    public static function encode($module, $interface, $costTime, $success, $code = 0, $msg = '')
    {
        // 防止模块名过长
        if (strlen($module) > self::MAX_CHAR_VALUE)
        {
            $module = substr($module, 0, self::MAX_CHAR_VALUE);
        }

        // 防止接口名过长
        if (strlen($interface) > self::MAX_CHAR_VALUE)
        {
            $interface = substr($interface, 0, self::MAX_CHAR_VALUE);
        }

        // 防止msg过长
        $moduleNameLength = strlen($module);
        $interfaceNameLength = strlen($interface);
        $availableSize = self::MAX_UDP_PACKAGE_SIZE - self::PACKAGE_FIXED_LENGTH - $moduleNameLength - $interfaceNameLength;
        if (strlen($msg) > $availableSize)
        {
            $msg = substr($msg, 0, $availableSize);
        }

        // 打包
        return pack('CCfCNnN', $moduleNameLength, $interfaceNameLength, $costTime, $success ? 1 : 0, $code, strlen($msg), time()) . $module . $interface . $msg;
    }

    /**
     * 解包
     *
     * @param string $bin_data
     * @return array
     */
    public static function decode($bin_data)
    {
        // 解包
        $data = unpack("Cmodule_name_len/Cinterface_name_len/fcost_time/Csuccess/Ncode/nmsg_len/Ntime", $bin_data);
        $module = substr($bin_data, self::PACKAGE_FIXED_LENGTH, $data['module_name_len']);
        $interface = substr($bin_data, self::PACKAGE_FIXED_LENGTH + $data['module_name_len'], $data['interface_name_len']);
        $msg = substr($bin_data, self::PACKAGE_FIXED_LENGTH + $data['module_name_len'] + $data['interface_name_len']);
        return [
            'module'    => $module,
            'interface' => $interface,
            'cost_time' => $data['cost_time'],
            'success'   => $data['success'],
            'time'      => $data['time'],
            'code'      => $data['code'],
            'msg'       => $msg,
        ];
    }

}
