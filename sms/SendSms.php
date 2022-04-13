<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

include_once(__DIR__ . "/../AlertManager.php");

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
         LIMIT 10
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












    // $smsGateway->send("a", "b", "c");
}

SendSms();
