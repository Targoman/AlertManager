<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

defined('FW_DEBUG') or define('FW_DEBUG', true);
defined('FW_ENV_DEV') or define('FW_ENV_DEV', true);

include_once(__DIR__ . "/../framework/Framework.php");

$config = require(__DIR__ . "/config/Alerting.conf.php");

if (FW_ENV_DEV) {
    $config = array_replace_recursive(
        $config,
        require(__DIR__ . "/config/params-local.php")
    );
} else {
    $config = array_replace_recursive(
        $config,
        require(__DIR__ . "/config/params.php")
    );
}

exit((new \Targoman\AlertManager\classes\AppAlertManager($config))->run());
