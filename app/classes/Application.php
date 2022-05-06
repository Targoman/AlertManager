<?php
/**
 * @author: Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace Targoman\AlertManager\classes;

use Framework\core\Application as BaseApplication;

class Application extends BaseApplication {

    public function run() {
        echo "Starting Alert Manager\n";

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
               AND tblAlertTemplates.altLanguage = tblAlerts.alrLanguage
             WHERE alrReplacedContactInfo != '__UNKNOWN__'
               AND (alrLockedAt IS NULL
                OR alrLockedAt < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                   )
               AND (alrStatus = 'N'
                OR (alrStatus = 'E'
               AND alrLastTryAt < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                   )
                   )
          ORDER BY alrCreateDate ASC
             LIMIT {$fetchLimit}
SQL
        );

        // print_r($data);

        if (empty($data))
            return;

        $ids = array_map(function ($ar) { return $ar["alrID"]; }, $data);

        // print_r($ids);

        if (empty($ids))
            throw new \Exception("Error in gathering ids");

        //lock items
        $rowsCount = $db->execute(strtr(<<<SQL
            UPDATE tblAlerts
               SET alrLockedAt = NOW()
             WHERE alrID IN (:ids)
SQL
        , [
            ':ids' => implode(',', $ids),
        ]));

        // print_r([implode(',', $ids), $rowsCount]);

        foreach ($data as $row) {
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
            $alrResult              = json_decode($row["alrResult"], true) ?? [];

            // $altlID                  = $row['altlID'];
            // $altCode                 = $row['altCode'];
            $altMedia                = $row['altMedia'];
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
            } else
                $newReplacements = $alrReplacements;

            $altTitleTemplate = strtr($altTitleTemplate, $newReplacements);
            $altBodyTemplate = strtr($altBodyTemplate, $newReplacements);

            $row['altTitleTemplate'] = $altTitleTemplate;
            $row['altBodyTemplate'] = $altBodyTemplate;

            /*
                alrResult:
                {
                    'E' => [
                        'status' => 'S',    // S:Sent, E:Error
                        'ref-id' => 'id',
                        'sent-at' => 'date-time',
                    ],
                }
            */

            $errorCount = 0;
            $now = date('U');

            //-- email -----
            try {
                $key = 'E';

                if (in_array($altMedia, [$key, 'A'])
                    && (($alrResult[$key]['status'] ?? 'N') != 'S')
                ) {
                    $refID = $this->SendEmailForItem($row);
                    echo "[SendEmail: OK]: (id: {$alrID}) " . $refID . "\n";

                    $alrResult = array_replace_recursive($alrResult, [
                        $key => [
                            'status' => 'S',
                            'ref-id' => $refID,
                            'sent-at' => $now,
                        ],
                    ]);
                }
            } catch(\Exception $_exp) {
                echo "[Error]: (id: {$alrID}) " . $_exp->getMessage() . "\n";

                ++$errorCount;

                $alrResult = array_replace_recursive($alrResult, [
                    $key => [
                        'status' => 'E',
                    ],
                ]);
            }

            //-- sms -----
            try {
                $key = 'S';

                if (in_array($altMedia, [$key, 'A'])
                    && (($alrResult[$key]['status'] ?? 'N') != 'S')
                ) {
                    $refID = $this->SendSmsForItem($row);
                    echo "[SendSms: OK]: (id: {$alrID}) " . $refID . "\n";

                    $alrResult = array_replace_recursive($alrResult, [
                        $key => [
                            'status' => 'S',
                            'ref-id' => $refID,
                            'sent-at' => $now,
                        ],
                    ]);
                }
            } catch(\Exception $_exp) {
                echo "[SendSms: Error]: (id: {$alrID}) " . $_exp->getMessage() . "\n";

                ++$errorCount;

                $alrResult = array_replace_recursive($alrResult, [
                    $key => [
                        'status' => 'E',
                    ],
                ]);
            }

            //-- push -----
            // try {
            //     $key = 'P';

            //     if (in_array($altMedia, [$key, 'A'])
            //         && (($alrResult[$key]['status'] ?? 'N') != 'S')
            //     ) {
            //         $refID = $this->SendPushForItem($row);
            // echo "[SendPush: OK]: (id: {$alrID}) " . $refID . "\n";

            //         $alrResult = array_replace_recursive($alrResult, [
            //             $key => [
            //                 'status' => 'S',
            //                 'ref-id' => $refID,
            //                 'sent-at' => $now,
            //             ],
            //         ]);
            //     }
            // } catch(\Exception $_exp) {
                // echo "[Error]: (id: {$alrID}) " . $_exp->getMessage() . "\n";

            //     ++$errorCount;

            //     $alrResult = array_replace_recursive($alrResult, [
            //         $key => [
            //             'status' => 'E',
            //         ],
            //     ]);
            // }

            $rowsCount = $this->db->execute(<<<SQL
                UPDATE tblAlerts
                   SET alrLockedAt = NULL
                     , alrLastTryAt = NOW()
                     , alrStatus = ?
                     , alrResult = ?
                 WHERE alrID = ?
SQL
            , [
                1 => ($errorCount == 0) ? 'S' : 'E',
                2 => empty($alrResult) ? null : json_encode($alrResult),
                3 => $alrID,
            ]);
        }

    }

    /**
     * return: refID : string
     */
    private function SendSmsForItem($row) {
        // $alrID                  = $row['alrID'];
        // $alrType                = $row['alrType'];
        // $alr_usrID              = $row['alr_usrID'];
        $alrReplacedContactInfo = trim($row['alrReplacedContactInfo']);
        $alr_altCode            = $row['alr_altCode'];
        // $alrReplacements        = trim($row['alrReplacements']);
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
        // $altParamsPrefix         = trim($row['altParamsPrefix']);
        // $altParamsSuffix         = trim($row['altParamsSuffix']);
        $altLanguage             = $row["altLanguage"];

        $SendResult = $this->smsgateway->send(
            null,
            $alrReplacedContactInfo,
            $altBodyTemplate,
            $alr_altCode,
            $altLanguage
        );

        if ($SendResult["OK"] ?? false)
            return $SendResult["refID"];

        throw new \Exception("error in send sms: " . ($SendResult["message"]));
    }

    /**
     * return: refID : string
     */
    private function SendEmailForItem($row) {
        // $alrID                  = $row['alrID'];
        // $alrType                = $row['alrType'];
        // $alr_usrID              = $row['alr_usrID'];
        $alrReplacedContactInfo = trim($row['alrReplacedContactInfo']);
        // $alr_altCode            = $row['alr_altCode'];
        // $alrReplacements        = trim($row['alrReplacements']);
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
        // $altParamsPrefix         = trim($row['altParamsPrefix']);
        // $altParamsSuffix         = trim($row['altParamsSuffix']);

        if (empty($this->config()["app"]["emailFrom"]))
            throw new \Exception("error in send email: emailFrom not set in config file");

        $SendResult = $this->mailer
            ->compose()
            ->from($this->config()["app"]["emailFrom"])
            ->to($alrReplacedContactInfo)
            ->subject($altTitleTemplate)
            ->textBody($altBodyTemplate)
            ->send();

        if ($SendResult)
            return 'ok';

        throw new \Exception("error in send email");
    }

    /**
     * return: refID : string
     */
    private function SendPushForItem($row) {
        throw new \Exception("error in send push notification: not implemented yet");
    }

};
