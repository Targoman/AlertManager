<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

require __DIR__ . "/Autoloader.php";

class AlertManager extends Autoloader {
    public static function instantiateClass($config) {
        $className = array_shift($config);

        $reflector = new \ReflectionClass($className);
        $class = $reflector->newInstanceWithoutConstructor();
        unset($reflector);

        foreach($config as $prop => $value) {
            $class->$prop = $value;
        }

        return $class;
    }
}

AlertManager::$baseNamespace = "Targoman";
spl_autoload_register(["AlertManager", "autoload"], true, false);
AlertManager::$autoloadMap = require(__DIR__ . "/autoload.php");
krsort(AlertManager::$autoloadMap);
