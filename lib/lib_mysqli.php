<?php

define('G_DATABASE_QUERY', 1);
define('G_DATABASE_INSERT', 2);
define('G_DATABASE_UPDATE', 4);
define('G_DATABASE_DELETE', 8);

$G_DATABASE_ACTION_INDEXED_ARRAY = [1 => '查詢', 2 => '新增', 4 => '修改', 8 => '刪除'];

function mysqliClose() {
    global $dbh;

    if ($dbh) {
        $dbh->close();
    }
}

function mysqliPing($connectCount = 0) {
    global $dbh;
    
    $filteredConnectCount = filter_var($connectCount, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
    
    if ($filteredConnectCount >= 5) {
        sleep(5);
        if (function_exists('ConsoleLog')) {
            ConsoleLog(__FILE__, __LINE__, "Connect failed: {$dbh->connect_error}");
        }
        header('Content-Type: text/plain; charset=utf-8');
        exit('Connect failed!');
    }

    $pingState = $dbh ? $dbh->ping() : FALSE;
    if (!$pingState) {
        mysqliClose();
        $filteredConnectCount += 1;
		$databaseName = $_SESSION["london-enabled"] > 0 ? 'london_smart_meters' : 'ncku_demand';
//        $dbh = new mysqli(G_DATABASE_HOST, G_DATABASE_USER, G_DATABASE_PASSWORD, G_DATABASE_NAME);
        $dbh = new mysqli(G_DATABASE_HOST, G_DATABASE_USER, G_DATABASE_PASSWORD, $databaseName, G_DATABASE_PORT); // 線上環境要刪掉!!
        $pingState = $dbh ? $dbh->ping() : FALSE;
    }
    
    if ($pingState) {
        $dbh->query('SET CHARACTER SET utf8mb4');
    } else {
        sleep(3);
        mysqliPing($filteredConnectCount);
    }
}

function mysqliExecuteQuery($sql, &$resultArray, &$message = '') {
    global $dbh;
    
    $result = FALSE;
    do {
        mysqliPing();
        $mysqliResult = $dbh->query($sql);
        if ($mysqliResult === FALSE) {
            $errorDescription = "資料表查詢失敗: {$dbh->error}";
            $errorLogId = ErrorLog(__FILE__, __LINE__, $errorDescription);
            $message = "資料表查詢失敗 (#{$errorLogId})!";
            break;
        }
        $resultArray = $mysqliResult->fetch_all(MYSQLI_ASSOC);
        $mysqliResult->free();
        $result = TRUE;
    } while (FALSE);
    return $result;
}

function mysqliExecuteQueryOne($sql, &$resultAssoc, &$message = '') {
    $resultArray = [];
    $result = mysqliExecuteQuery($sql, $resultArray, $message);
    if (is_array($resultArray) && count($resultArray) > 0) {
        $resultAssoc = $resultArray[0];
    }
    return $result;
}

function mysqliExecuteUpdate($sql, &$resultAssoc, &$message = '') {
    global $dbh;
    
    $result = FALSE;
    do {
        mysqliPing();
        $executeResult = $dbh->query($sql);
        if ($executeResult === FALSE) {
            $errorDescription = "資料表更新失敗: {$dbh->error}";
            $errorLogId = ErrorLog(__FILE__, __LINE__, $errorDescription);
            $message = "資料表更新失敗 (#{$errorLogId})!";
            break;
        }
        $resultAssoc['affectedRows'] = $dbh->affected_rows;
        if (stripos($sql, 'INSERT') === 0) {
            $resultAssoc['lastInsertId'] = $dbh->insert_id;
        }
        $result = TRUE;
    } while (FALSE);
    return $result;
}

function mysqliValueType($val) {
    if (is_float($val)) {
        return 'd';
    } elseif (is_int($val)) {
        return 'i';
    } else {
        return 's';
    }
}

function mysqliPrepare($sql, &$stmt, &$message = '') {
    global $dbh;

    $result = FALSE;
    do {
        if (!$dbh) {
            $message = '資料庫連線異常!';
            break;
        }
        /* 查詢指令預編譯 (B) */
        $stmt = $dbh->prepare($sql);
        if (!$stmt) {
            $errorDescription = "資料庫指令編譯失敗: {$dbh->error}";
            $errorLogId = ErrorLog(__FILE__, __LINE__, $errorDescription);
            $message = "資料庫指令編譯失敗 (#{$errorLogId})!";
            break;
        }
        /* 查詢指令預編譯 (E) */
        $result = TRUE;
    } while (FALSE);
    return $result;
}

function mysqliExecute(&$stmt, &$message = '', $errorActionAssoc = FALSE) {
    global $dbh;

    $result = FALSE;
    do {
        if (!$dbh) {
            $message = '資料庫連線異常!';
            break;
        }
        /* 更新指令預編譯參數綁定 (B) */
        $executeResult = $stmt->execute();
        if (!$executeResult) {
            $errorCode = $stmt->errno; # 0 means execute success
            $errorDescription = "資料庫指令執行失敗: {$stmt->error}";
            $duplicateEntryMessage = $errorCode === 1062 ? ', 資料重複!' : '';
            if (is_array($errorActionAssoc) && intval($errorActionAssoc['code']) === $errorCode) {
                $errorLogId = $errorActionAssoc['log'] ? ErrorLog(__FILE__, __LINE__, $errorDescription) : "#{$errorCode}";
                $message = "資料庫指令執行失敗{$duplicateEntryMessage} (#{$errorLogId})!";
            } else {
                $errorLogId = ErrorLog(__FILE__, __LINE__, $errorDescription);
                $message = "資料庫指令執行失敗{$duplicateEntryMessage} (#{$errorLogId})!";
            }
            break;
        }
        /* 更新指令預編譯參數綁定 (E) */
        $result = TRUE;
    } while (FALSE);
    return $result;
}

/**
 * 產生 SQL 欄位字串的格式
 * @param string $fieldString 符合純量或者 SQL / MySQL 欄位格式的字串
 * @return string 若符合特定格式, 則回傳處理過後的字串, 否則將原始字串做安全性過濾後回傳
 */
function mysqliFieldString($fieldString) {
    $resultString = '';
    if (preg_match('/^(\d+)|([\'"][^\'"]+[\'"])$/u', $fieldString) > 0) { # 純數字或 'xxx' 格式的字串
        $resultString = $fieldString;
    } elseif (preg_match('/^(\w+|`\w+`(\.`\w+`|\.\w+){0,2})$/i', $fieldString) > 0) { # 符合 MySQL 欄位格式: `column`, `table`.`column`, `database`.`table`.`column`
        $resultString = implode('.', array_map(function ($col) {
                    return preg_match('/^`\w+`$/i', $col) > 0 ? $col : "`{$col}`";
                }, explode('.', $fieldString)));
    } else {
        $resultString = filter_var($fieldString, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_AMP);
    }
    return $resultString;
}

/**
 * 判斷並轉換變數值為 MySQL 的表示型態, 如: 字串為 'string', 空為 NULL, 其餘則直接回傳
 * @param string|int $val 判斷資料, 型態可為字串, NULL, 或數字等等
 * @param string|int $resultValue 回傳值, 若為字串則為 'string', 空為 NULL, 其餘 (整數, 及函式或欄位) 則直接回傳
 * @return boolean 回傳 TRUE 表示為預理參數, 回傳 FALSE 表示為保持原值
 */
function mysqliPreparedValueString($val) {
    $resultValue = '?';
    $matchesArray = [];
    if (is_null($val)) {
        $resultValue = 'NULL';
    } elseif (is_bool($val)) {
        $resultValue = $val ? 'TRUE' : 'FALSE';
    } elseif (preg_match('/^`\w+`(\.`\w+`){0,2}$/i', $val) > 0) { # 符合 MySQL 欄位格式: `column`, `table`.`column`, `database`.`table`.`column`
        $resultValue = $val;
    } elseif (preg_match('/^([\'"])([^\1]+)\1$/iu', $val, $matchesArray) > 0) { # 符合 'val', "val" 格式, 主要用在 MySQL 函式
        $resultValue = $matchesArray[2];
    } elseif (is_int($val)) { # 符合數字
        $resultValue = '?';
    } else {
        $resultValue = '?';
    }
    return $resultValue;
}

function mysqliPreparedFieldsValuesString(&$dataAssoc, $glue = ', ') {
    if (!is_array($dataAssoc) || count($dataAssoc) === 0) {
        return FALSE;
    }
    $mysqiPreparedFieldsArray = array();
    $isQuery = preg_match('/^\s+and\s+$/i', $glue) === 1;
    array_walk($dataAssoc, function (&$value, $field) use (&$mysqiPreparedFieldsArray, $isQuery) {
        $matchesArray = [];
        
        /* process field (B) */
        $sqlField = mysqliFieldString($field);
        /* process field (E) */

        /* process value (B) */
        if (is_array($value)) {
            $sqlValue = ($valueCount = count($value)) > 0 ? implode(', ', array_fill(0, $valueCount, '?')) : 1;
            $mysqiPreparedFieldsArray[] = "{$sqlField} IN ({$sqlValue})";
        } elseif (is_null($value)) {
            $mysqiPreparedFieldsArray[] = "{$sqlField} " . ($isQuery ? 'IS' : '=') . ' NULL';
        } elseif (strpos($value, '%') !== FALSE) {
            $mysqiPreparedFieldsArray[] = "{$sqlField} LIKE ?";
        } elseif (preg_match('/^between\s+((`)[^\2]+\2(\.\2[^\2]+\2){0,2})\s+and\s+(\2[^\2]+\2(\.\2[^\2]+\2){0,2})\s*$/', $value, $matchesArray) > 0) { # between `columnA` and `columnB` / `table`.`columnA` and `table`.`columnB`
            $value = [$matchesArray[1], $matchesArray[4]];
//            $value = $matchesArray[1];
            $mysqiPreparedFieldsArray[] = "({$sqlField} BETWEEN {$matchesArray[1]} AND {$matchesArray[4]})";
        } elseif (preg_match("/^between\s+['\"]?([^'\"]+)['\"]?\s+and\s+['\"]?([^'\"]+)['\"]?\s*$/i", $value, $matchesArray) > 0) {
            $value = [$matchesArray[1], $matchesArray[2]];
            $mysqiPreparedFieldsArray[] = "({$sqlField} BETWEEN ? AND ?)";
        } elseif (preg_match('/^\s*(>|<|>=|<=|<>|!=)\s+(.*)\s*$/i', $value, $matchesArray) > 0) { # '>= 2016-07-06 00:00:00', 含有 >, <, >=, <=, <>, != 符號
            $value = $matchesArray[2]; # may be 'xxx', "xxx", 'xxx()', "xxx()", xxx
            $preparedValue = mysqliPreparedValueString($value);
            $sqlValue = $preparedValue === '?' ? '?' : $value;
            if (preg_match('/^[A-Z]+[A-Z_]*\(.*\)$/iu', $preparedValue) > 0) {
                $sqlValue = $preparedValue;
            }
            $mysqiPreparedFieldsArray[] = "{$sqlField} {$matchesArray[1]} {$sqlValue}";
        } else {
            $preparedValue = mysqliPreparedValueString($value);
            $mysqiPreparedFieldsArray[] = "{$sqlField} = {$preparedValue}";
        }
        /* process value (E) */
    });
    return implode($glue, $mysqiPreparedFieldsArray);
}

/**
 * 處理 SQL 的 GROUP BY, HAVING, ORDER BY, LIMIT 等條件的字串處理與串接<br />
 * 格式如: GROUP BY ... HAVING ... ORDER BY ... LIMIT ...
 * @param array $sqlCondAssoc
 * @return string 成功回傳結果字串, 失敗回傳 FALSE
 */
function mysqliConditionString($sqlCondAssoc) {
    $resultString = FALSE;
    do {
        if (!is_array($sqlCondAssoc) || count($sqlCondAssoc) === 0) {
            break;
        }
        $extraCondArray = array();
        if (array_key_exists('groupby', $sqlCondAssoc)) {
            $extraCondArray[] = 'GROUP BY';
            $extraCondArray[] = is_array($sqlCondAssoc['groupby']) ? implode(', ', $sqlCondAssoc['groupby']) : $sqlCondAssoc['groupby'];
        }
        if (array_key_exists('having', $sqlCondAssoc)) {
            $extraCondArray[] = 'HAVING';
            $extraCondArray[] = $sqlCondAssoc['having'];
        }
        if (array_key_exists('orderby', $sqlCondAssoc)) {
            $extraCondArray[] = 'ORDER BY';
            $extraCondArray[] = is_array($sqlCondAssoc['orderby']) ? implode(', ', $sqlCondAssoc['orderby']) : $sqlCondAssoc['orderby'];
        }
        if (array_key_exists('limit', $sqlCondAssoc)) {
            $extraCondArray[] = 'LIMIT';
            $extraCondArray[] = is_array($sqlCondAssoc['limit']) ? implode(', ', array_map('intval', $sqlCondAssoc['limit'])) : intval($sqlCondAssoc['limit']);
        }
        $resultString = implode(' ', $extraCondArray);
    } while (FALSE);
    return $resultString;
}

function mysqliBindParam(mysqli_stmt &$stmt, &$preparedValuesArray, &$message = '') {
    $result = FALSE;
    do {
        if (!is_object($stmt)) {
            $message = 'mysqli_stmt 物件為空!';
            break;
        }
        if (!is_array($preparedValuesArray) || count($preparedValuesArray) === 0) {
            $message = '預處理關聯陣列為空!';
            break;
        }
        $paramTypes = '';
        $paramByValArray = [];
        foreach ($preparedValuesArray as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (mysqliPreparedValueString($v) !== '?') {
                        continue;
                    }
                    $paramTypes .= mysqliValueType($v);
                    $paramByValArray[] = $v;
                }
//                $valueArray = array_values($value);
//                $paramTypes .= implode('', array_map('mysqliValueType', $valueArray));
//                $paramByValArray = array_merge($paramByValArray, $valueArray);
            } elseif (mysqliPreparedValueString($value) !== '?') {
                continue;
            } else {
                $paramTypes .= mysqliValueType($value);
                $paramByValArray[] = $value;
            }
        }
        $result = TRUE;
        if (count($paramByValArray) > 0) {
            $paramByRefArray = [];
            for ($i = 0; $i < count($paramByValArray); $i += 1) {
                $paramByRefArray[] = &$paramByValArray[$i];
            }
            array_unshift($paramByRefArray, $paramTypes);
            $result = call_user_func_array([$stmt, 'bind_param'], $paramByRefArray);
            if (!$result) {
                $errorDescription = "預處理資料繫結錯誤!";
                $errorLogId = ErrorLog(__FILE__, __LINE__, $errorDescription);
                $message = "預處理資料繫結錯誤 (#{$errorLogId})!";
                break;
            }
        }
    } while (FALSE);
    return $result;
}

function mysqliPreparedQuery($sql, $preparedValuesArray, &$resultArray, &$message = '') {
    global $dbh;

    $result = FALSE;
    do {
        mysqliPing();
        
        /* 查詢指令預編譯 (B) */
        $stmt = FALSE;
        $prepareResult = mysqliPrepare($sql, $stmt, $message);
        if (!$prepareResult || !$stmt) {
            break;
        }
        /* 查詢指令預編譯 (E) */
        
        /* 查詢指令預編譯參數綁定 (B) */
        if (is_array($preparedValuesArray) && count($preparedValuesArray) > 0) {
            $bindResult = mysqliBindParam($stmt, $preparedValuesArray, $message);
            if (!$bindResult) {
                break;
            }
        }
        /* 查詢指令預編譯參數綁定 (E) */
        
        /* 執行查詢 (B) */
        $executeResult = mysqliExecute($stmt, $message);
        if (!$executeResult) {
            break;
        }
        /* 執行查詢 (E) */
        
        /* 查詢指令結果集擷取 (B) */
        $mysqliResult = $stmt->get_result();
        if (!$mysqliResult) {
            $errorDescription = "擷取資料表查詢結果失敗: {$dbh->error}";
            $errorLogId = ErrorLog(__FILE__, __LINE__, $errorDescription);
            $message = "擷取資料表查詢結果失敗 (#{$errorLogId})!";
            break;
        }
        $resultArray = $mysqliResult->fetch_all(MYSQLI_ASSOC);
        $mysqliResult->free();
        /* 查詢指令結果集擷取 (E) */
        $result = TRUE;
    } while (FALSE);
    return $result;
}

function mysqliPreparedQueryOne($sql, $preparedValuesArray, &$resultAssoc, &$message = '') {
    $resultArray = [];
    $result = mysqliPreparedQuery($sql, $preparedValuesArray, $resultArray, $message);
    if (is_array($resultArray) && count($resultArray) > 0) {
        $resultAssoc = $resultArray[0];
    }
    return $result;
}

function mysqliPreparedUpdate($sql, $preparedValuesArray, &$resultAssoc, &$message = '', $errorActionAssoc = FALSE) {
    global $dbh;

    $result = FALSE;
    do {
        mysqliPing();
        
        /* 更新指令預編譯 (B) */
        $stmt = FALSE;
        $prepareResult = mysqliPrepare($sql, $stmt, $message);
        if (!$prepareResult || !$stmt) {
            break;
        }
        /* 更新指令預編譯 (E) */
        
        /* 更新指令預編譯參數綁定 (B) */
        if (is_array($preparedValuesArray) && count($preparedValuesArray) > 0) {
            $bindResult = mysqliBindParam($stmt, $preparedValuesArray, $message);
            if (!$bindResult) {
                break;
            }
        }
        /* 更新指令預編譯參數綁定 (E) */
        
        /* 執行更新 (B) */
        $executeResult = mysqliExecute($stmt, $message, $errorActionAssoc);
        if (!$executeResult) {
            $resultAssoc['errorCode'] = $stmt->errno;
            break;
        }
        /* 執行更新 (E) */
        
        $resultAssoc['affectedRows'] = $dbh->affected_rows;
        if (stripos($sql, 'INSERT') === 0) {
            $resultAssoc['lastInsertId'] = $dbh->insert_id;
        }
        $result = TRUE;
    } while (FALSE);
    return $result;
}

function mysqliPreparedUpdateBatch($sql, $preparedValuesBatchArray, &$resultAssoc, &$message = '') {
    global $dbh;

    $result = FALSE;
    do {
        if (!is_array($preparedValuesBatchArray) || count($preparedValuesBatchArray) === 0) {
            $message = '預處理資料為空!';
            break;
        }
        
        mysqliPing();
        
        /* 更新指令預編譯 (B) */
        $stmt = FALSE;
        $prepareResult = mysqliPrepare($sql, $stmt, $message);
        if (!$prepareResult || !$stmt) {
            break;
        }
        /* 更新指令預編譯 (E) */
        
        $insertBatch = stripos($sql, 'INSERT') === 0;
        $executeResult = FALSE;
        $affectedRows = 0;
        foreach ($preparedValuesBatchArray as $preparedValuesArray) {
            /* 更新指令預編譯參數綁定 (B) */
            mysqliBindParam($stmt, $preparedValuesArray, $message);
            $executeResult = mysqliExecute($stmt, $message);
            if (!$executeResult) {
                break;
            }
            /* 更新指令預編譯參數綁定 (E) */
            $affectedRows += $dbh->affected_rows;
            if ($insertBatch) {
                $resultAssoc['insertIdArray'][] = $dbh->insert_id;
            }
        }
        if (!$executeResult) {
            break;
        }
        $resultAssoc['affectedRows'] = $affectedRows;
        $result = TRUE;
    } while (FALSE);
    return $result;
}
