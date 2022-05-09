<?php
/**
 * @author: Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace Targoman\AlertManager\classes\sms;

interface ISmsGateway {

    public function send(
        $_from, //null : use line number defined in config
        $_to,
        $_smessage,
        $_template,
        $_language
    );

}

class BaseSmsGateway {
    use \Targoman\Framework\core\ComponentTrait;
}
