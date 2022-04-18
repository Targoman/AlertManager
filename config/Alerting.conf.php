<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

return [
    "db" => [
        "class" => "Targoman\\AlertManager\\classes\\db\\MySql",
    ],
    "smsgateway" => [
        "class" => "Targoman\\AlertManager\\gateways\\sms\\FaraPayamak",
    ],
    "mailer" => [
        "class" => "Targoman\\AlertManager\\gateways\\email\\SymfonyMailer",
        "transport" => [
        ],
    ],
    "sendsms" => [
    ],
    "sendemail" => [
    ],
];
