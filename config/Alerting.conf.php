<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

return [
    "smsgateway" => [
        "class" => "Targoman\AlertManager\gateways\sms\FaraPayamak",
        "username" => "09120000000",
        "password" => "password",
        "linenumber" => "800080008000",
    ],
    "db" => [
        "class" => "Targoman\AlertManager\common\db\MySql",
        "host" => "127.0.0.1",
        "port" => "3306",
        "username" => "root",
        "password" => "targoman123",
        "schema" => "dev_Common",
    ],
    "sendsms" => [
        "fetchlimit" => "10",
    ],
];
