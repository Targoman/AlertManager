<?php
/**
 * @author: Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace Targoman\AlertManager\classes;

use \Exception;
use Targoman\Framework\core\Application as BaseApplication;
use Targoman\Framework\helpers\ArrayHelper;

class Application extends BaseApplication {
    public $runner = [
        "sleep" => 60,
        "maxLoopCount" => null,
    ];
    public $instanceId;
    public $fetchlimit = 1;
    public $emailFrom;

    public function run() {
        if (empty($this->instanceId)) {
            $this->instanceId = "ALM-" . uniqid(true);

            $localParams = [];
            $fileName = __DIR__ . '/../config/params-local.php';
            if (file_exists($fileName)) {
                $localParams = require($fileName);
            }

            $localParams["app"]["instanceId"] = $this->instanceId;

            $conf = ArrayHelper::dump($localParams);
            $conf = "<?php\n" . "return " . $conf . ";\n";
            file_put_contents($fileName, $conf);
        }

        //-------------------------
        $this->logger->setActor($this->instanceId);

        $this->logger->log("---------- Starting Alert Manager ----------");

        //-------------------------
        $command = 'ps aux | grep "AlertManager.php" | grep "php "';
        exec($command, $output, $return_var);
        // var_dump($output);
        // var_dump($return_var);
        if ($return_var != 0)
            throw new Exception("Error in `ps`");
        // $output = shell_exec('ps aux | grep "AlertManager.php" | grep "php "');
        // if (substr_count($output, "\n") > 2)
        if (count($output) > 2)
            throw new Exception("AlertManager is running");

        //-------------------------
        $smsGateway = $this->smsgateway;
        $mailer = $this->mailer;
        $db = $this->db;

        //-------------------------
        $counter = 0;
        while (true) {
            $this->runOnce();

            if (!empty($this->runner["maxLoopCount"]) && (++$counter >= $this->runner["maxLoopCount"]))
                break;

            sleep($this->runner["sleep"]);
        };

        return 0;
    }

    public function runOnce() {
        $qry = <<<SQL
            SELECT *
              FROM tblAlerts
        INNER JOIN tblAlertTemplates
                ON tblAlertTemplates.altCode = tblAlerts.alr_altCode
               AND tblAlertTemplates.altLanguage = tblAlerts.alrLanguage
             WHERE alrReplacedContactInfo != '__UNKNOWN__'
               AND (alrLockedAt IS NULL
                OR alrLockedAt < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                OR alrLockedBy = ?
                   )
               AND (alrStatus = 'N'
                OR (alrStatus = 'E'
               AND alrLastTryAt < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                   )
                   )
          ORDER BY alrCreateDate ASC
             LIMIT {$this->fetchlimit}
SQL;
        $data = $this->db->selectAll($qry, [
            1 => $this->instanceId,
        ]);

        if (empty($data)) {
            $this->logger->log("Nothing to do");
            return;
        }

        $this->logger->log("Count of items: " . count($data));

        $ids = array_map(function ($ar) { return $ar["alrID"]; }, $data);
        if (empty($ids))
            throw new Exception("Error in gathering ids");

        $this->logger->log("Items ID: " . implode(',', $ids));

        //lock items
        $qry = strtr(<<<SQL
            UPDATE tblAlerts
               SET alrLockedAt = NOW()
                 , alrLockedBy = ?
             WHERE alrID IN (:ids)
SQL
        , [
            ':ids' => implode(',', $ids),
        ]);
        $rowsCount = $this->db->execute($qry, [
            1 => $this->instanceId,
        ]);

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
                    $this->logger->log("[SendEmail: OK]: (id: {$alrID}) " . $refID);

                    $alrResult = array_replace_recursive($alrResult, [
                        $key => [
                            'status' => 'S',
                            'ref-id' => $refID,
                            'sent-at' => $now,
                        ],
                    ]);
                }
            } catch(Exception $_exp) {
                $this->logger->log("[Error]: (id: {$alrID}) " . $_exp->getMessage());

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
                    $this->logger->log("[SendSms: OK]: (id: {$alrID}) " . $refID);

                    $alrResult = array_replace_recursive($alrResult, [
                        $key => [
                            'status' => 'S',
                            'ref-id' => $refID,
                            'sent-at' => $now,
                        ],
                    ]);
                }
            } catch(Exception $_exp) {
                $this->logger->log("[SendSms: Error]: (id: {$alrID}) " . $_exp->getMessage());

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
            // $this->logger->log("[SendPush: OK]: (id: {$alrID}) " . $refID);

            //         $alrResult = array_replace_recursive($alrResult, [
            //             $key => [
            //                 'status' => 'S',
            //                 'ref-id' => $refID,
            //                 'sent-at' => $now,
            //             ],
            //         ]);
            //     }
            // } catch(Exception $_exp) {
                // $this->logger->log("[Error]: (id: {$alrID}) " . $_exp->getMessage());

            //     ++$errorCount;

            //     $alrResult = array_replace_recursive($alrResult, [
            //         $key => [
            //             'status' => 'E',
            //         ],
            //     ]);
            // }

            $_alrSentDate = ($errorCount == 0 ? 'NOW()' : 'NULL');
            $qry = <<<SQL
            UPDATE tblAlerts
               SET alrLockedAt = NULL
                 , alrLockedBy = NULL
                 , alrLastTryAt = NOW()
                 , alrSentDate = {$_alrSentDate}
                 , alrResult = ?
                 , alrStatus = ?
             WHERE alrID = ?
SQL;
            $rowsCount = $this->db->execute($qry, [
                1 => empty($alrResult) ? null : json_encode($alrResult),
                2 => ($errorCount == 0 ? 'S' : 'E'),
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

        throw new Exception("error in send sms: " . ($SendResult["message"]));
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

        if (empty($this->emailFrom))
            throw new Exception("error in send email: emailFrom not set in config file");

        $SendResult = $this->mailer
            ->compose()
            ->from($this->emailFrom)
            ->to($alrReplacedContactInfo)
            ->subject($altTitleTemplate)
            ->textBody($altBodyTemplate)
            ->send();

        if ($SendResult)
            return 'ok';

        throw new Exception("error in send email");
    }

    /**
     * return: refID : string
     */
    private function SendPushForItem($row) {
        throw new Exception("error in send push notification: not implemented yet");
    }

};
