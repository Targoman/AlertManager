<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Framework\core;

use Framework;

class Application extends Component {

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

    private static $components = [];
    public function __get($name) {
        if (isset(static::$components[$name]))
            return static::$components[$name];

        if (isset($this->config()["components"][$name]["class"])) {
            $component = Framework::instantiateClass($this->config()["components"][$name]);

            if (is_null($component))
                throw new \Exception("Could not create $name");

            static::$components[$name] = $component;

            return $component;
        }

        throw new \Exception("$name not configured");
    }

    public function run() { throw new \Exception("Application::run not implemented."); }

}
