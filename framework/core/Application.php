<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Framework\core;

use Framework;

class Application {

    private static $config = null;
    public function config() {
        return self::$config;
    }
    public function setConfig($_config) {
        self::$config = $_config;
    }

    public function __construct($_config) {
        $this->setConfig($_config);
    }

    private static $db = null;
    public function db() {
        if (self::$db == null)
            self::$db = Framework::instantiateClassByConfigName(self::$config, "db");
        return self::$db;
    }

    private static $smsgateway = null;
    public function smsgateway() {
        if (self::$smsgateway == null)
            self::$smsgateway = Framework::instantiateClassByConfigName(self::$config, "smsgateway");
        return self::$smsgateway;
    }

    private static $mailer = null;
    public function mailer() {
        if (self::$mailer == null)
            self::$mailer = Framework::instantiateClassByConfigName(self::$config, "mailer");
        return self::$mailer;
    }

    public function run() {}
}
