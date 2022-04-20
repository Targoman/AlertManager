<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\classes\email;

use Targoman\AlertManager\classes\email\BaseMailer;
use Targoman\AlertManager\classes\email\IBaseMailer;

class BaseMailMessage {
    protected IBaseMailer $mailer;

    public function __construct(BaseMailer $_mailer) {
        $this->mailer = $_mailer;
    }

    public function send() {
        return $this->mailer->send($this);
    }

}
