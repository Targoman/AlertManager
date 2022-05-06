<?php
/**
 * @author: Kambiz Zandi <kambizzandi@gmail.com>
 */

return [
    "app" => [
    ],
    "components" => [
        "db" => [
            "class" => "Framework\\db\\MySql",
        ],
        "smsgateway" => [
            "class" => "Targoman\\AlertManager\\gateways\\sms\\FaraPayamak",
        ],
        "mailer" => [
            "class" => "Targoman\\AlertManager\\gateways\\email\\SymfonyMailer",
            "transport" => [
            ],
        ],
    ],
];
