<?php

function getTableQuerySQL($table, $fields, &$whereAssoc = FALSE, $extraAssoc = FALSE) {
    $where = is_array($whereAssoc) && count($whereAssoc) > 0 ? mysqliPreparedFieldsValuesString($whereAssoc, ' AND ') : '1';
    $extraConditionString = is_array($extraAssoc) && count($extraAssoc) > 0 ? mysqliConditionString($extraAssoc) : '';
    $sql = "SELECT {$fields} FROM {$table} WHERE {$where}";
    if (!empty($extraConditionString)) {
        $sql .= " {$extraConditionString}";
    }
    return $sql;
}

function doQuerySQL($sql, $preparedValuesArray) {
    $resultArray = [];
    $message = '';
    $result = mysqliPreparedQuery($sql, $preparedValuesArray, $resultArray, $message);
    $resultAssoc = [
        'result' => $result,
        'message' => $message,
        'dataArray' => $resultArray,
    ];
    return $resultAssoc;
}

function doTableQuery($table, $fields, &$whereAssoc = FALSE, $extraAssoc = FALSE) {
    $sql = getTableQuerySQL($table, $fields, $whereAssoc, $extraAssoc);
    $resultAssoc = doQuerySQL($sql, $whereAssoc);
    return $resultAssoc;
}

function doTableQueryOne($table, $fields, &$whereAssoc = FALSE, $extraAssoc = FALSE) {
    if (!is_array($extraAssoc) || count($extraAssoc) === 0) {
        $extraAssoc = ['limit' => 1];
    } elseif (!array_key_exists('limit', $extraAssoc) || intval($extraAssoc['limit']) !== 1) {
        $extraAssoc['limit'] = 1;
    }
    $sql = getTableQuerySQL($table, $fields, $whereAssoc, $extraAssoc);
    $resultArray = [];
    $message = '';
    $result = mysqliPreparedQueryOne($sql, $whereAssoc, $resultArray, $message);
    $resultAssoc = [
        'result' => $result,
        'message' => $message,
        'data' => $resultArray,
    ];
    return $resultAssoc;
}

function doTableInsert($table, &$fieldsValuesAssoc, $errorActionAssoc = FALSE) {
    $resultAssoc = [];
    do {
        $message = '';
        if (!is_array($fieldsValuesAssoc) || count($fieldsValuesAssoc) === 0) {
            $resultAssoc['message'] = '新增資料表欄位為空!';
            break;
        }
        $preparedValuesArray = array_values($fieldsValuesAssoc);
        $fields = implode(', ', array_map('mysqliFieldString', array_keys($fieldsValuesAssoc)));
        $values = implode(', ', array_map('mysqliPreparedValueString', $preparedValuesArray));
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
        $resultAssoc['result'] = mysqliPreparedUpdate($sql, $preparedValuesArray, $resultAssoc, $message, $errorActionAssoc);
        $resultAssoc['message'] = $message;
    } while (FALSE);
    return $resultAssoc;
}

function doTableInsertIgnore($table, &$fieldsValuesAssoc, $errorActionAssoc = FALSE) {
    $resultAssoc = [];
    do {
        $message = '';
        if (!is_array($fieldsValuesAssoc) || count($fieldsValuesAssoc) === 0) {
            $resultAssoc['message'] = '新增資料表欄位為空!';
            break;
        }
        $preparedValuesArray = array_values($fieldsValuesAssoc);
        $fields = implode(', ', array_map('mysqliFieldString', array_keys($fieldsValuesAssoc)));
        $values = implode(', ', array_map('mysqliPreparedValueString', $preparedValuesArray));
        $sql = "INSERT IGNORE INTO {$table} ({$fields}) VALUES ({$values})";
        $resultAssoc['result'] = mysqliPreparedUpdate($sql, $preparedValuesArray, $resultAssoc, $message, $errorActionAssoc);
        $resultAssoc['message'] = $message;
    } while (FALSE);
    return $resultAssoc;
}

function doTableUpdate($table, &$fieldsValuesAssoc, &$whereAssoc, $extraAssoc = FALSE, $errorActionAssoc = FALSE) {
    $resultAssoc = [];
    do {
        if (!is_array($fieldsValuesAssoc) || count($fieldsValuesAssoc) === 0) {
            $resultAssoc['message'] = '更新資料表欄位為空!';
            break;
        }
        if (!is_array($whereAssoc) || count($whereAssoc) === 0) {
            $resultAssoc['message'] = '更新資料表條件為空!';
            break;
        }
        $fields = mysqliPreparedFieldsValuesString($fieldsValuesAssoc);
        $where = mysqliPreparedFieldsValuesString($whereAssoc, ' AND ');
        $extraConditionString = is_array($extraAssoc) && count($extraAssoc) > 0 ? mysqliConditionString($extraAssoc) : '';
        $sql = "UPDATE {$table} SET {$fields} WHERE {$where}";
        if (!empty($extraConditionString)) {
            $sql .= " {$extraConditionString}";
        }
        $preparedValuesArray = array_merge(array_values($fieldsValuesAssoc), array_values($whereAssoc));
        $message = '';
        $resultAssoc['result'] = mysqliPreparedUpdate($sql, $preparedValuesArray, $resultAssoc, $message, $errorActionAssoc);
        $resultAssoc['message'] = $message;
    } while (FALSE);
    return $resultAssoc;
}

function doTableDelete($table, &$whereAssoc, $fields = '') {
    $resultAssoc = [];
    do {
        if (!is_array($whereAssoc) || count($whereAssoc) === 0) {
            $resultAssoc['message'] = '刪除條件為空!';
            break;
        }
        $where = mysqliPreparedFieldsValuesString($whereAssoc, ' AND ');
        $sql = "DELETE {$fields} FROM {$table} WHERE {$where}";
        $preparedValuesArray = array_values($whereAssoc);
        $message = '';
        $resultAssoc['result'] = mysqliPreparedUpdate($sql, $preparedValuesArray, $resultAssoc, $message);
        $resultAssoc['message'] = $message;
    } while (FALSE);
    return $resultAssoc;
}
