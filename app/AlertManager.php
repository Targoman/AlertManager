<?php
/**
 * @author: Kambiz Zandi <kambizzandi@gmail.com>
 */

defined('FW_DEBUG') or define('FW_DEBUG', true);
defined('FW_ENV_DEV') or define('FW_ENV_DEV', true);

require(__DIR__ . "/../vendor/autoload.php");
require(__DIR__ . "/../vendor/targoman/php-micro-framework/src/TargomanFramework.php");

$config = array_replace_recursive(
    require(__DIR__ . "/config/AlertManager.conf.php"),
    require(__DIR__ . "/config/params.php")
);

if (FW_ENV_DEV) {
    $config = array_replace_recursive(
        $config,
        @require(__DIR__ . "/config/params-local.php")
    );
}

exit((new \Targoman\AlertManager\classes\Application($config))->run());
