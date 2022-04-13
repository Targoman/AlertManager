<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

include_once(__DIR__ . "/../AlertManager.php");

$config = require(__DIR__ . "/../config/Alerting.conf.php");

$smsGateway = AlertManager::instantiateClass($config["smsgateway"]);

$smsGateway->send("a", "b", "c");
