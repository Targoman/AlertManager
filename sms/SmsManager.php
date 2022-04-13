#!/usr/bin/php
<?php
// @author: Kambiz Zandi <kambizzandi@gmail.com>

/*

$LockFile="/var/lock/sendAlerts";
#    if (mkdir($LockFile) === false){
$a=shell_exec('ps -aux | grep "/Targoman/AlertManager/SendSMS.php" | grep "php "');
echo $a;

if (substr_count($a,"\n") > 2) {
    echo "Script is running\n";
    exit;
}

include_once(_DIR_ . "/MySQLDBPDO.php.inc");

$MySQLHost = "192.168.111.103";
$MySQLUser = "smtuser";
$MySQLPass = "Access2MySQL";
$MySQLDB   = "SMT";

$DB = new MySQLDBPDO($MySQLHost, $MySQLUser, $MySQLPass, $MySQLDB);
$Alerts = $DB->fetchAll("
    SELECT
            tbl_COMMON_Alert.alrID,
--                tbl_AAA_User.usrEmail,
    Tarjomyar.tbl_Tarjomyar_UserAccounts.accEmail AS usrEmail,
            tbl_AAA_User.usrMobile,
            tbl_AAA_User.usrPreferedLanguage,
            tbl_COMMON_Alert.alrType,
            tbl_COMMON_Alert.alrReplacements,
            tbl_COMMON_AlertTemplates.altMedia,
            tbl_COMMON_AlertTemplates.altTitleTemplate,
            tbl_COMMON_AlertTemplates.altBodyTemplate
    FROM  tbl_COMMON_Alert
        JOIN tbl_AAA_User
            ON tbl_AAA_User.usrID = tbl_COMMON_Alert.alr_usrID
        INNER JOIN tbl_COMMON_AlertTemplates
            ON tbl_COMMON_AlertTemplates.altCode = tbl_COMMON_Alert.alr_altCode
        JOIN Tarjomyar.tbl_Tarjomyar_UserAccounts
    ON Tarjomyar.tbl_Tarjomyar_UserAccounts.acc_usrID = tbl_AAA_User.usrID
    WHERE (tbl_COMMON_Alert.alrStatus = 'N'
            OR (tbl_COMMON_Alert.alrStatus = 'E'
                AND tbl_COMMON_Alert.alrSentDate < DATE_SUB(now(), INTERVAL 10 Minute)))
            AND tbl_COMMON_AlertTemplates.altLanguage = tbl_AAA_User.usrPreferedLanguage
            AND Tarjomyar.tbl_Tarjomyar_UserAccounts.accStatus = 'A'
    ORDER BY tbl_COMMON_Alert.alrID");
$LastSubject = "";
foreach ($Alerts as &$Alert){
    $Replacements = json_decode(trim($Alert['alrReplacements']), true);
    foreach ($Replacements as $Key => $Value){
        $Alert['altTitleTemplate'] = str_replace("%@%$Key%@%", $Value, $Alert['altTitleTemplate']);
        $Alert['altBodyTemplate']  = str_replace("%@%$Key%@%", $Value, $Alert['altBodyTemplate']);
    }

    if ($Alert['altMedia'] == 'E' || $Alert['altMedia'] == 'A'){
        $TempEmailFileNoex = tempnam ('/tmp', 'targoman-alert');
        $TempEmailFile = strtolower($TempEmailFileNoex.".html");
        if ($TempEmailFile) {
            $TempFile = fopen ($TempEmailFile, 'w+');
            if ($TempFile) {
                $EmailBody="<html><body dir=\"".($Alert["usrPreferedLanguage"] == 'fa' ? 'rtl' : 'ltr')."\">".$Alert['altBodyTemplate']."</body></html>";
                var_dump($EmailBody);

                fwrite($TempFile, $EmailBody);
                fclose($TempFile);
                if($LastSubject != $Alert['altTitleTemplate']){
        $LastSubject = $Alert['altTitleTemplate'];
                    $Command="/SMT/bin/mailsend -v -ehlo -auth -starttls -smtp 'smtp.tarjomyar.ir' -port 587 -t '".$Alert['usrEmail']."' -user 'support@tarjomyar.ir' -pass '$PASSWORD' -name 'Tarjomyar' -f 'no-reply@tarjomyar.ir' -sub '=?utf-8?B?".base64_encode(".:: ".$Alert['altTitleTemplate']." ::.")."?=' -mime-type 'text/html' -msg-body  $TempEmailFile";
            }else
                    $Command="/SMT/bin/mailsend -v -ehlo -starttls -auth -smtp 'smtp.tarjomyar.ir' -port 587 -t '".$Alert['usrEmail']."' -user 'support@tarjomyar.ir' -pass '$PASSWORD' -name 'Tarjomyar' -f 'no-reply@tarjomyar.ir' -sub '=?utf-8?B?".base64_encode(".:: ".$Alert['altTitleTemplate']." ::.")."?=' -mime-type 'text/html' -msg-body  $TempEmailFile";
        echo $Command;
                exec($Command, $output, $return);
                $DB->execute("UPDATE tbl_COMMON_Alert SET
                                    tbl_COMMON_Alert.alrStatus = ?,
                                    tbl_COMMON_Alert.alrSentDate = now()
                            WHERE tbl_COMMON_Alert.alrID = ?",
                            array(($return == 0 ? 'S' : 'E'),
                                $Alert['alrID']));
            }
            unlink ($TempEmailFile);
            unlink ($TempEmailFileNoex);
        }
    }
usleep(100000);
}

#   rmdir($LockFile);


*/
