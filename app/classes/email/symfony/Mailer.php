<?php
/**
 * @author: Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace Targoman\AlertManager\classes\email\symfony;

use RuntimeException;
use Symfony\Component\Mailer\Mailer as _SymfonyMailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Crypto\SMimeEncrypter;
use Symfony\Component\Mime\Crypto\SMimeSigner;
use Symfony\Component\Mime\RawMessage;
use Targoman\AlertManager\classes\email\BaseMailer;
use Targoman\AlertManager\classes\email\BaseMailMessage;
use Targoman\AlertManager\classes\email\IBaseMailer;

class Mailer extends BaseMailer implements IBaseMailer {
    public $messageClassName = MailMessage::class;

    public $transport;

    public function init() {
        // Symfony\\Component\\Mailer\\Mailer
        if (is_array($this->transport)) {
            $this->transport = $this->createTransport($this->transport);
        }
    }

    private function createTransport($_config) {
        $defaultFactories = Transport::getDefaultFactories(null, null, null);
        $transportObj = new Transport($defaultFactories);

        if (array_key_exists('dsn', $_config)) {
            $transport = $transportObj->fromString($_config['dsn']);
        } elseif(array_key_exists('scheme', $_config) && array_key_exists('host', $_config)) {
            $dsn = new Dsn(
                $_config['scheme'],
                $_config['host'],
                $_config['username'] ?? '',
                $_config['password'] ?? '',
                $_config['port'] ?? '',
                $_config['options'] ?? [],
            );
            $transport = $transportObj->fromDsnObject($dsn);
        } else {
            throw new \Exception('Transport configuration array must contain either "dsn", or "scheme" and "host" keys.');
        }

        return $transport;
    }

    public function send($_mailMessage) {
        //RawMessage
        $symfonyMessage = $_mailMessage->getSymfonyEmail();

        $symfonyMailer = new _SymfonyMailer($this->transport);
        $symfonyMailer->send($symfonyMessage);

        return true;
    }
}
