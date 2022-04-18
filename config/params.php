<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

return [
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
    "sendsms" => [
        "fetchlimit" => "100",
    ],
    "sendemail" => [
        "fetchlimit" => "100",
        "from" => "",
    ],
];
