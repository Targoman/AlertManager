<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

return [
    "app" => [
        "fetchlimit" => "100",
        "emailFrom" => "",
    ],
    "components" => [
        "db" => [
            "host" => "127.0.0.1",
            "port" => "3306",
            "username" => "",
            "password" => "",
            "schema" => "",
        ],
        "smsgateway" => [
            "username" => "",
            "password" => "",
            "bodyid" => "",
            // "linenumber" => "",
        ],
        "mailer" => [
            "transport" => [
                'scheme' => "smtp",
                "host" => "",
                "username" => "",
                "password" => "",
                "port" => "",
                "options" => [
                    "verify_peer" => false,
                ],
            ],
        ],
    ],
];
