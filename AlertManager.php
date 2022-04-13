<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

require __DIR__ . "/Autoloader.php";

class AlertManager extends Autoloader {
    public static function instantiateClass($_config) {
        $className = array_shift($_config);

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
}

AlertManager::$baseNamespace = "Targoman";
spl_autoload_register(["AlertManager", "autoload"], true, false);
AlertManager::$autoloadMap = require(__DIR__ . "/autoload.php");
krsort(AlertManager::$autoloadMap);
