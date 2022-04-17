<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/Autoloader.php";

class AlertManager extends Autoloader {

    private static $config = null;
    public static function config() {
        if (self::$config == null)
            self::$config = require(__DIR__ . "/../config/Alerting.conf.php");
        return self::$config;
    }

    public static function instantiateClass($_config) {
        $className = array_shift($_config);

        // foreach ($_config as $k => &$v) {
        //     if (isset($v["class"]))
        //         $v["class"] = static:: instantiateClass($v);
        // }

        $reflector = new \ReflectionClass($className);
        $class = $reflector->newInstance(); //WithoutConstructor();
        unset($reflector);

        foreach($_config as $prop => $value) {
            $class->$prop = $value;
        }

        if (method_exists($class, "init"))
            $class->init();

        return $class;
    }

    public static function instantiateClassByConfigName($_configName) {
        $config = self::config();

        if (empty($config[$_configName])) {
            throw new \Exception("$_configName not configured");
            return;
        }

        $object = AlertManager::instantiateClass($config[$_configName]);

        if (is_null($object)) {
            throw new \Exception("Could not create $_configName");
            return;
        }

        return $object;
    }

    private static $db = null;
    public static function db() {
        if (self::$db == null)
            self::$db = self::instantiateClassByConfigName("db");
        return self::$db;
    }

    private static $smsgateway = null;
    public static function smsgateway() {
        if (self::$smsgateway == null)
            self::$smsgateway = self::instantiateClassByConfigName("smsgateway");
        return self::$smsgateway;
    }

    private static $mailer = null;
    public static function mailer() {
        if (self::$mailer == null)
            self::$mailer = self::instantiateClassByConfigName("mailer");
        return self::$mailer;
    }

}

AlertManager::$baseNamespace = "Targoman";
spl_autoload_register(["AlertManager", "autoload"], true, false);
AlertManager::$autoloadMap = require(__DIR__ . "/autoload.php");
krsort(AlertManager::$autoloadMap);
