<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

return [
    "db" => [
        "class" => "Targoman\\AlertManager\\framework\\db\\MySql",
    ],
    "smsgateway" => [
        "class" => "Targoman\\AlertManager\\gateways\\sms\\FaraPayamak",
    ],
    "mailer" => [
        "class" => "Targoman\\AlertManager\\gateways\\email\\SymfonyMailer",
        "transport" => [
        ],
    ],
    "app" => [
    ],
];
