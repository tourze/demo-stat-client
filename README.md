# STAT客户端

## 安装

首先需要下载和安装[composer](https://getcomposer.org/)，具体请查看官网的[Download页面](https://getcomposer.org/download/)

在你的`composer.json`中增加：

    "require": {
        "tourze/security": "^1.0"
    },

或直接执行

    composer require tourze/security:"^1.0"

## 使用

代码示例：

    use tourze\Stat\Client;
    
    // 统计开始
    Client::tick("User", 'getInfo');
    // 统计的产生，接口调用是否成功、错误码、错误日志
    $success = true; $code = 0; $msg = '';
    // 假如有个User::getInfo方法要监控
    $userInfo = User::getInfo();
    $msg = '';
    if( ! $userInfo)
    {
        // 标记失败
        $success = false;
        // 获取错误码，假如getErrCode()获得
        $code = User::getErrCode();
        // 获取错误日志，假如getErrMsg()获得
        $msg = User::getErrMsg();
    }
    // 上报结果
    Client::report('User', 'getInfo', $success, $code, $msg);

