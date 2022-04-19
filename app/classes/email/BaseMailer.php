<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\classes\email;

use Targoman\AlertManager\classes\email\MailMessage;

interface IBaseMailer {
    public function send($_mailMessage);
}

class BaseMailer {
    public $messageClassName = BaseMailMessage::class;

    public function createMessageClass() {
        if (empty($this->messageClassName))
            throw new \Exception("messageClass not defined");

        $reflector = new \ReflectionClass($this->messageClassName);
        $message = $reflector->newInstance($this);
        unset($reflector);

        return $message;
    }

    public function compose() {
        $MailMessage = $this->createMessageClass();
        return $MailMessage;
    }

}
