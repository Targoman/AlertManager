<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

return [
    "app" => [
        "fetchlimit" => "100",
        "emailFrom" => "",
    ],
    "db" => [
        "host" => "",
        "port" => "",
        "username" => "",
        "password" => "",
        "schema" => "",
    ],
    "smsgateway" => [
        "username" => "",
        "password" => "",
        "linenumber" => "",
    ],
    "mailer" => [
        "transport" => [
            'scheme' => '',
            "host" => "",
            "username" => "",
            "password" => "",
            "port" => "",
            "options" => [
                "verify_peer" => false,
            ],
        ],
    ],
];
