<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

require __DIR__ . "/../vendor/autoload.php";

class Framework {
    public static $baseNamespaces = [""];
    public static $autoloadMap = [];

    public static function autoload($_className) {
        if (strpos($_className, "\\") !== false) {
			$name = str_replace("\\", "/", $_className) . ".php";
            if (strpos($name, "/") === 0)
				$name = substr($name, 1);

            $ret = array_filter(array_map(
                function($value) use ($name) {
                    if (strpos($name, $value . "/") !== 0)
                        return "";
                    return $value;
                },
                self::$baseNamespaces));
            if (empty($ret)) //not found
				return;

			foreach (static::$autoloadMap as $k => $v) {
				$k = str_replace("\\", "/", $k);
				$p = strpos($name, $k);
				if ($p === 0) {
					$name = $v . "/" . substr($name, strlen($k));
					break;
				}
			}

			$classFile = $name;
			if ($classFile === false || !is_file($classFile))
                return;
		} else
			return;

		include $classFile;

		if (!class_exists($_className, false) && !interface_exists($_className, false) && !trait_exists($_className, false))
			throw new \Exception("Unable to find '$_className' in file: $classFile. Namespace missing?");
    }

    public static function instantiateClass($_config) {
        if (empty($_config["class"]))
            throw new \Exception("class item not defined in config array");

        $className = $_config["class"];
        unset($_config["class"]);

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

    public static function instantiateClassByConfigName($_config, $_configName) {
        if (empty($_config[$_configName]))
            throw new \Exception("$_configName not configured");

        $object = self::instantiateClass($_config[$_configName]);

        if (is_null($object))
            throw new \Exception("Could not create $_configName");

        return $object;
    }

}

Framework::$baseNamespaces = [
    "Framework",
    "Targoman"
];
spl_autoload_register(["Framework", "autoload"], true, false);
Framework::$autoloadMap = array_replace_recursive(
    [
        "Framework" => __DIR__
    ],
    require(__DIR__ . "/../app/autoload.php")
);
krsort(Framework::$autoloadMap);
