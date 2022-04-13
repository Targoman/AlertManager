<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

include_once(__DIR__ . "/../AlertManager.php");

function SendSmsForItem($row, $smsGateway) {
    $alrID                  = $row['alrID'];
    // $alrType                = $row['alrType'];
    // $alr_usrID              = $row['alr_usrID'];
    // $alrEmail               = $row['alrEmail'];
    // $alrMobile              = $row['alrMobile'];
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

    // if (isset($newReplacements[":usrName"]))
    //     $messageBody = strtr("Dear :usrName :usrFamily, this is approval code for you. Code: :ApprovalCode", $newReplacements);
    // else
    //     $messageBody = strtr("Dear user, this is approval code for you. Code: :ApprovalCode", $newReplacements);

    $SendResult = $smsGateway->send(null, $alrReplacedContactInfo, $altBodyTemplate);

    $rowsCount = AlertManager::db()->execute(<<<SQL
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

function SendSms() {

    $smsGateway = AlertManager::instantiateClassByConfigName("smsgateway");

    $db = AlertManager::db();

    $data = $db->selectAll(<<<SQL
        SELECT *
          FROM tblAlerts
    INNER JOIN tblAlertTemplates
            ON tblAlertTemplates.altCode = tblAlerts.alr_altCode
         WHERE alr_altCode = 'approveMobile'
           AND alrLockedAt IS NULL
           AND (alrStatus = 'N'
            OR (alrStatus = 'E'
           AND alrSentDate < DATE_SUB(NOW(), INTERVAL 10 Minute)
               )
               )
      ORDER BY alrCreateDate ASC
         LIMIT 2
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
        SendSmsForItem($row, $smsGateway);
    }
}

SendSms();
