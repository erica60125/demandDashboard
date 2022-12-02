<?php

define('G_LOG_FILE_NAME_PREFIX', 'system');
define('G_LOG_FILE_NAME_SUFFIX', 'log');

/**
 * 紀錄 Error Log 到檔案
 * @param string $filePath 記錄檔案路徑
 * @param int $fileLine 記錄錯誤行號
 * @param string $errorDescription 記錄錯誤訊息
 * @return string 寫入成功, 則回傳 Log 記錄的識別值, 否則回傳 FALSE
 */
function SystemLog($filePath, $fileLine, $errorDescription) {
    list($currentTimeInt) = explode('.', microtime(TRUE));
    $logId = uniqid();
    $currentTimestamp = date('Y-m-d H:i:s', $currentTimeInt);
    $today = date('Y-m-d', $currentTimeInt);
    $virtualFilePath = str_replace(['\\', preg_replace('#/?$#', '', $_SERVER['DOCUMENT_ROOT']), G_ROOT_PATH], ['/', '', ''], $filePath);
    $errorMessage = "{$currentTimestamp} \${$logId} - - {$virtualFilePath}(:{$fileLine})\t{$errorDescription}\n";
    $logFilePath = G_LOG_PATH . '/' . G_LOG_FILE_NAME_PREFIX . "-{$today}." . G_LOG_FILE_NAME_SUFFIX;
    $writeResult = error_log($errorMessage, 3, $logFilePath);
    return $writeResult ? $logId : FALSE;
}

/**
 * 紀錄 Error Log
 * @param string $systemFilePath 記錄檔案實體路徑
 * @param int $fileLine 記錄錯誤行號
 * @param string $errorDescription 記錄錯誤訊息
 * @return mixed 記錄成功則回傳自動遞增欄位產生的值, 若未產生值則回傳 0 (回傳值同 mysql_insert_id() 函式); 若資料新增失敗, 則回傳寫入文字 Log 檔隨機產生的識別字串, 若寫入文字 Log 檔失敗, 則回傳 FALSE;
 */
function ErrorLog($systemFilePath, $fileLine, $errorDescription) {
    global $dbh;

    $callStackArray = debug_backtrace();
    $originCallStackAssoc = $callStackArray[count($callStackArray) - 1] ? : ['file' => $systemFilePath, 'line' => $fileLine];
    $currentUser = $_SESSION['user'] ? : '';
    $virtualFilePath = str_replace(['\\', preg_replace('#/?$#', '', $_SERVER['DOCUMENT_ROOT']), G_ROOT_PATH], ['/', '', ''], $originCallStackAssoc['file']);
    $getAsString = var_export($_GET, TRUE);
    $postAsString = var_export($_POST, TRUE);
    $cookieAsString = var_export($_COOKIE, TRUE);
    $sessionAsString = var_export($_SESSION, TRUE);
    $serverAsString = var_export($_SERVER, TRUE);
    $errorDescription .= PHP_EOL . 'Args: ' . var_export($originCallStackAssoc['args'], TRUE);

    $table = '`error_log`';
    $fields = '`e_user`, `e_program`, `e_line`, `e_description`, `e_get`, '
            . '`e_post`, `e_cookie`, `e_session`, `e_server`';
    $values = '?, ?, ?, ?, ?, ?, ?, ?, ?';
    $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
    $stmt = $dbh->prepare($sql);
    $stmt->bind_param('ssissssss', $currentUser, $virtualFilePath, $originCallStackAssoc['line'], $errorDescription, $getAsString, $postAsString, $cookieAsString, $sessionAsString, $serverAsString);
    $insertResult = $stmt->execute(); # using native api to avoid recursive
    if (!$insertResult) {
        $systemLog = "新增 Log 記錄失敗: {$dbh->error}\n"
                . "Args: [\n"
                . "    0 => '{$sql}',\n"
                . "    1 => '{$currentUser}',\n"
                . "    2 => '{$virtualFilePath}',\n"
                . "    3 => {$originCallStackAssoc['line']},\n"
                . "    4 => '{$errorDescription}',\n"
                . "    5 => '{$getAsString}',\n"
                . "    6 => '{$postAsString}',\n"
                . "    7 => '{$cookieAsString}',\n"
                . "    8 => '{$sessionAsString}',\n"
                . "    9 => '{$serverAsString}'\n"
                . "]\n";
        $logId = SystemLog(__FILE__, __LINE__, $systemLog);
        return $logId;
    }
    $affectedRows = $dbh->affected_rows;
    $resultInt = $affectedRows > 0 ? intval($dbh->insert_id) : 0;
    return $resultInt;
}

/**
 * 用於 Console Mode 列印 Error Log
 * @param string $filePath 記錄檔案路徑
 * @param int $fileLine 記錄錯誤行號
 * @param string $errorDescription 記錄錯誤訊息
 */
function ConsoleLog($filePath, $fileLine, $errorDescription) {
    list($currentTimeInt, $currentTimeMicro) = explode('.', microtime(TRUE));
    $currentTimestamp = date('Y-m-d H:i:s', $currentTimeInt);
    $virtualFilePath = str_replace(array('\\', preg_replace('#/?$#', '', $_SERVER['DOCUMENT_ROOT'])), array('/', ''), $filePath);
    echo "{$currentTimestamp}.{$currentTimeMicro}\t{$virtualFilePath}(:{$fileLine})\t{$errorDescription}\n";
}
