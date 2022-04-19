<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

namespace Targoman\AlertManager\classes;

use Framework;
use Framework\core\Application;

class AppAlertManager extends Application {

    // private static $db = null;
    // public function db() {
    //     if (self::$db == null)
    //         self::$db = Framework::instantiateClassByConfigName($this->config(), "db");
    //     return self::$db;
    // }

    // private static $smsgateway = null;
    // public function smsgateway() {
    //     if (self::$smsgateway == null)
    //         self::$smsgateway = Framework::instantiateClassByConfigName($this->config(), "smsgateway");
    //     return self::$smsgateway;
    // }

    // private static $mailer = null;
    // public function mailer() {
    //     if (self::$mailer == null)
    //         self::$mailer = Framework::instantiateClassByConfigName($this->config(), "mailer");
    //     return self::$mailer;
    // }

    public function run() {
        $a = shell_exec('ps -aux | grep "AlertManager.php" | grep "php "');
        if (substr_count($a, "\n") > 2) {
            echo "AlertManager is running\n";
            return;
        }

        //-------------------------
        $smsGateway = $this->smsgateway;
        $mailer = $this->mailer;
        $db = $this->db;

        $fetchLimit = $this->config()["app"]["fetchlimit"] ?? 10;

        $data = $db->selectAll(<<<SQL
            SELECT *
              FROM tblAlerts
        INNER JOIN tblAlertTemplates
                ON tblAlertTemplates.altCode = tblAlerts.alr_altCode
             WHERE altMedia = 'M'
               AND alrReplacedContactInfo != '__UNKNOWN__'
               AND alrLockedAt IS NULL
               AND (alrStatus = 'N'
                OR (alrStatus = 'E'
               AND alrLastTryAt < DATE_SUB(NOW(), INTERVAL 10 Minute)
                   )
                   )
          ORDER BY alrCreateDate ASC
             LIMIT {$fetchLimit}
SQL
        );

        print_r($data);

        if (empty($data))
            return;

        $ids = array_map(function ($ar) { return $ar["alrID"]; }, $data);

        print_r($ids);

        if (empty($data))
            throw new \Exception("Error in gathering alerts ids");

        //lock items
        $rowsCount = $db->execute(strtr(<<<SQL
            UPDATE tblAlerts
               SET alrLockedAt = NOW()
             WHERE alrID IN (:ids)
SQL
        , [
            ':ids' => implode(',', $ids),
        ]));

        print_r([implode(',', $ids), $rowsCount]);

        foreach ($data as $row) {
            $this->SendSmsForItem($row);
        }
    }

    private function SendSmsForItem($row) {
        $alrID                  = $row['alrID'];
        // $alrType                = $row['alrType'];
        // $alr_usrID              = $row['alr_usrID'];
        $alrReplacedContactInfo = trim($row['alrReplacedContactInfo']);
        // $alr_altCode            = $row['alr_altCode'];
        $alrReplacements        = trim($row['alrReplacements']);
        // $alrCreateDate          = $row['alrCreateDate'];
        // $alrLockedAt            = $row['alrLockedAt'];
        // $alrSentDate            = $row['alrSentDate'];
        // $alrStatus              = $row['alrStatus'];

        // $altlID                  = $row['altlID'];
        // $altCode                 = $row['altCode'];
        // $altMedia                = $row['altMedia'];
        // $altLanguage             = $row['altLanguage'];
        // $altTitleTemplate        = trim($row['altTitleTemplate']);
        $altBodyTemplate         = trim($row['altBodyTemplate']);
        $altParamsPrefix         = trim($row['altParamsPrefix']);
        $altParamsSuffix         = trim($row['altParamsSuffix']);

        $alrReplacements = json_decode($alrReplacements, true);
        $newReplacements = [];
        if (!empty($altParamsPrefix) || !empty($altParamsSuffix)) {
            foreach ($alrReplacements as $k => $v) {
                $newReplacements[$altParamsPrefix . $k . $altParamsSuffix] = $v;
            }
        }
        else
            $newReplacements = $alrReplacements;

        $altBodyTemplate = strtr($altBodyTemplate, $newReplacements);

        $SendResult = $this->smsgateway->send(null, $alrReplacedContactInfo, $altBodyTemplate);

        $rowsCount = $this->db->execute(<<<SQL
            UPDATE tblAlerts
               SET alrLockedAt = NULL
                 , alrLastTryAt = NOW()
                 , alrStatus = ?
             WHERE alrID = ?
SQL
        , [
            1 => $SendResult["OK"] ? 'S' : 'E',
            2 => $alrID,
        ]);

        print_r([
            'messageBody' => $altBodyTemplate,
            'SendResult' => $SendResult,
            'rowsCount' => $rowsCount,
        ]);
    }

    private function SendEmailForItem($row) {
        $alrID                  = $row['alrID'];
        // $alrType                = $row['alrType'];
        // $alr_usrID              = $row['alr_usrID'];
        $alrReplacedContactInfo = trim($row['alrReplacedContactInfo']);
        // $alr_altCode            = $row['alr_altCode'];
        $alrReplacements        = trim($row['alrReplacements']);
        // $alrCreateDate          = $row['alrCreateDate'];
        // $alrLockedAt            = $row['alrLockedAt'];
        // $alrSentDate            = $row['alrSentDate'];
        // $alrStatus              = $row['alrStatus'];

        // $altlID                  = $row['altlID'];
        // $altCode                 = $row['altCode'];
        // $altMedia                = $row['altMedia'];
        // $altLanguage             = $row['altLanguage'];
        $altTitleTemplate        = trim($row['altTitleTemplate']);
        $altBodyTemplate         = trim($row['altBodyTemplate']);
        $altParamsPrefix         = trim($row['altParamsPrefix']);
        $altParamsSuffix         = trim($row['altParamsSuffix']);

        $alrReplacements = json_decode($alrReplacements, true);
        $newReplacements = [];
        if (!empty($altParamsPrefix) || !empty($altParamsSuffix)) {
            foreach ($alrReplacements as $k => $v) {
                $newReplacements[$altParamsPrefix . $k . $altParamsSuffix] = $v;
            }
        }
        else
            $newReplacements = $alrReplacements;

        $altTitleTemplate = strtr($altTitleTemplate, $newReplacements);
        $altBodyTemplate = strtr($altBodyTemplate, $newReplacements);

        $SendResult = $this->mailer
            ->compose()
            ->from($this->config()["app"]["emailFrom"])
            ->to($alrReplacedContactInfo)
            ->subject($altTitleTemplate)
            ->textBody($altBodyTemplate)
            ->send();

        $rowsCount = $this->db->execute(<<<SQL
            UPDATE tblAlerts
               SET alrLockedAt = NULL
                 , alrLastTryAt = NOW()
                 , alrStatus = ?
             WHERE alrID = ?
SQL
        , [
            1 => $SendResult["OK"] ? 'S' : 'E',
            2 => $alrID,
        ]);

        print_r([
            'messageTitle' => $altTitleTemplate,
            'messageBody' => $altBodyTemplate,
            'SendResult' => $SendResult,
            'rowsCount' => $rowsCount,
        ]);
    }

};
