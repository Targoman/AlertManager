<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

include_once(__DIR__ . "/../AlertManager.php");

function SendSmsForItem($row, $smsGateway) {
    $alrID                  = $row['alrID'];
    // $alrType                = $row['alrType'];
    // $alr_usrID              = $row['alr_usrID'];
    // $alrEmail               = $row['alrEmail'];
    // $alrMobile              = $row['alrMobile'];
    $alrReplacedContactInfo = $row['alrReplacedContactInfo'];
    // $alr_altCode            = $row['alr_altCode'];
    $alrReplacements        = $row['alrReplacements'];
    // $alrCreateDate          = $row['alrCreateDate'];
    // $alrLockedAt            = $row['alrLockedAt'];
    // $alrSentDate            = $row['alrSentDate'];
    // $alrStatus              = $row['alrStatus'];

    $alrReplacements = json_decode($alrReplacements, true);
    $newReplacements = [];
    foreach ($alrReplacements as $k => $v) {
        $newReplacements[":$k"] = $v;
    }

    if (isset($newReplacements[":usrName"]))
        $messageBody = strtr("Dear :usrName :usrFamily, this is approval code for you. Code: :ApprovalCode", $newReplacements);
    else
        $messageBody = strtr("Dear user, this is approval code for you. Code: :ApprovalCode", $newReplacements);

    $SendResult = $smsGateway->send(null, $alrReplacedContactInfo, $messageBody);

    $rowsCount = AlertManager::db()->execute(<<<SQL
        UPDATE tblAlerts
           SET alrLockedAt = NULL
             , alrLastTryAt = NOW()
             , alrStatus = ?
         WHERE alrID = ?
SQL
    , [
        1 => $SendResult["OK"] ? 'S' : 'N',
        2 => $alrID,
    ]);

    print_r([
        'messageBody' => $messageBody,
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
         WHERE alr_altCode = 'approveMobile'
           AND alrStatus = 'N'
           AND alrLockedAt IS NULL
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
