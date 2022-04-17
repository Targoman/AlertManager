<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

return [
    "db" => [
        "class" => "Targoman\\AlertManager\\classes\\db\\MySql",
        "host" => "127.0.0.1",
        "port" => "3306",
        "username" => "root",
        "password" => "1234",
        "schema" => "dev_Common",
    ],
    "smsgateway" => [
        "class" => "Targoman\\AlertManager\\gateways\\sms\\FaraPayamak",
        "username" => "09120000000",
        "password" => "password",
        "linenumber" => "800080008000",
    ],
    "mailer" => [
        "class" => "Targoman\\AlertManager\\gateways\\email\\SymfonyMailer",
        "transport" => [
            'scheme' => 'smtp',
            "host" => "smtp.domain.dom",
            "username" => "support@domain.dom",
            "password" => "1234",
            "port" => "587",
            "options" => [
                "verify_peer" => false,
            ],
        ],
    ],
    "sendsms" => [
        "fetchlimit" => "10",
    ],
    "sendemail" => [
        "fetchlimit" => "10",
        "from" => "support@domain.dom",
    ],
];
