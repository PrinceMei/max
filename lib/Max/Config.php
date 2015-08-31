<?php
/*
 * \Max\Config类，\Max\App会调用此类中的set方法保存config.json中配置
 */
namespace Max;
class Config {
    static $config;
    public static function get($name) {
        return self::$config[$name];
    }
    public static function set($name, $value) {
        self::$config[$name] = $value;
    }
}