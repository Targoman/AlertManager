<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\gateways\sms;

use Targoman\AlertManager\classes\sms\BaseSmsGateway;
use Targoman\AlertManager\classes\sms\ISmsGateway;

// https://github.com/nosratiz/Payamak-Panel
class FaraPayamak extends BaseSmsGateway implements ISmsGateway {

    const URL_API = "https://rest.payamak-panel.com/api/SendSMS"; ///BaseServiceNumber";

    public $username;
    public $password;
    public $linenumber;

    public function send(
        $_from, //null : use line number defined in config
        $_to,
        $_message
    ) {
        //todo: validate clsss and input parameters

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::URL_API . "/SendSMS");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "content-type: application/json; charset=utf-8",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "username"    => $this->username,
            "password"    => $this->password,
            "to"          => $_to,
            "from"        => $this->linenumber ?? $_from,
            "text"        => $_message
            // "bodyId"      => "$ SMSBodyID",
        ]));

        //
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // curl_setopt($ch, CURLOPT_VERBOSE, 1);

        $response = curl_exec($ch);

        $parts = preg_split("@\r?\n\r?\nHTTP/@u", $response);
        $parts = (count($parts) > 1 ? 'HTTP/' : '').array_pop($parts);
        list($headers, $body) = preg_split("@\r?\n\r?\n@u", $parts, 2);

        $data = json_decode($body, true);

        // print_r([
        //     "response" => $response,
        //     "headers" => $headers,
        //     "body" => $body,
        //     "data" => $data,
        // ]);

        return [
            "OK" => ($data["RetStatus"] == 1),
            "refID" => $data["Value"],
            "message" => ($data["RetStatus"] ?? "") . " - " . ($data["StrRetStatus"] ?? ""),
        ];
    }

}
