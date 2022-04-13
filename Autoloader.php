<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

class Autoloader {
    public static $baseNamespace = "";
    public static $autoloadMap = [];

    public static function autoload($className) {
        if (strpos($className, "\\") !== false) {
			$name = str_replace("\\", "/", $className) . ".php";
			if (strpos($name, self::$baseNamespace . "/") !== 0)
				return;

			if (strpos($name, "/") === 0)
				$name = substr($name, 1);

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

		if (!class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false))
			throw new \exception("Unable to find '$className' in file: $classFile. Namespace missing?");
    }
}
