<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\common;

interface ISmsGateway {

    public function send(
        $from, //null : use line number defined in config
        $to,
        $message
    );

}
