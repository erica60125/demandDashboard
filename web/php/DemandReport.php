<?php

use Module\Layout;


function getDemandData($beginDate, $endDate) {
    // 抓取需量資料 [B]
    $demandInfoField = '`id`, `demand_quarter`, `Temperature`, `T_Min`, `T_Max`, `datetime`';
    $londonInfoField = '`id`, `energy`, `datetime`';
	$fieldSelect = $_SESSION["london-enabled"] > 0 ? $londonInfoField : $demandInfoField;
	$demandInfoTable = $_SESSION["london-enabled"] > 0 ? 'halfhourly_avg' : 'demand_with_weather_data';
    $demandInfoWhereAssocString = "`datetime` >= '{$beginDate}' AND `datetime` <= '{$endDate}'";
    $demandInfoExtraString = 'ORDER BY `datetime` DESC';
    $demandInfoSQL = "SELECT {$fieldSelect} FROM {$demandInfoTable} WHERE {$demandInfoWhereAssocString} {$demandInfoExtraString}";
    $prepareArray = [];
    $demandInfoResult = doQuerySQL($demandInfoSQL, $prepareArray);
    if (!$demandInfoResult['result']) {
        $message = $demandInfoResult['message'];
    }
    return array_reverse($demandInfoResult['dataArray']);
    // 抓取需量資料 [E]
}

function getAllForecastData($beginDate, $endDate) {
    // 抓取預測資料 [B]
    $allDemandForecastList = [];
    $demandForecastField = '`prediction_1`, `prediction_2`, `prediction_3`, `prediction_4`, `created_at`';
//    $demandForecastTableArray = ['stacked_demand_predict', 'stacked_extend_demand_predict', 'stacked_kmeans_demand_predict', 'stacked_period_type_predict',
//                                 'bidirectional_demand_predict', 'bidirectional_extend_demand_predict', 'bidirectional_kmeans_demand_predict', 'bidirectional_period_type_predict',
//                                 'vanilla_demand_predict', 'vanilla_extend_demand_predict', 'vanilla_kmeans_demand_predict', 'vanilla_period_type_predict'];
    //$demandForecastTableArray = ['vanilla_demand_predict_4step', 'vanilla_demand_predict_48step', 'vanilla_demand_predict_96step'];
	$demandForecastTableArray = ['demand_predict'];

    $demandForecastWhereAssocString = "`created_at` >= '{$beginDate}' AND `created_at` <= '{$endDate}'";
    $demandForecastExtraString = 'ORDER BY `created_at` DESC';

    foreach ($demandForecastTableArray as $searchTableName) {
        $demandForecastSQL = "SELECT {$demandForecastField} FROM {$searchTableName} WHERE {$demandForecastWhereAssocString} {$demandForecastExtraString}";
        $prepareArray = [];
        $demandForecastResult = doQuerySQL($demandForecastSQL, $prepareArray);
        if (!$demandForecastResult['result']) {
            $message = $demandForecastResult['message'];
        }
        $allDemandForecastList[$searchTableName] = array_reverse($demandForecastResult['dataArray']);
    }
    return $allDemandForecastList;
    // 抓取預測資料 [B]
}

class DemandReport
{
    function __construct()
    {
        if (!empty($_GET['b'])) {
            $MethodName = $_GET['b'];
            switch ($MethodName) {
                case (preg_match('/\w/', $MethodName) ? true : false):
                    if (method_exists(__CLASS__, $MethodName)) {
                        $this->$MethodName();
                        exit;
                    }
                    break;
            }
        } else {
            $this->Page();
        }

    }

    function Page()
    {
		// 開始寫入 html [B]
		$page = array(
			'Title' => "Demand Forecast Dashboard"
		);
		$layout = Layout::Admin($page);
		$beginDate = date('Y-m-d 00:00:00');
		$endDate = date('Y-m-d 00:00:00', strtotime("{$beginDate} -1 month"));

//		$header = $_ENV['menu'][$_GET['a']];

		// 抓取需量資料 [B]
        $demandInfo = getDemandData($beginDate, $endDate);
        // 抓取需量資料 [E]
        // 抓取預測資料 [B]
        $allForecastDataList = getAllForecastData($beginDate, $endDate);
        // 抓取預測資料 [E]

		$first = file_get_contents(__DIR__ . '/../templates/DemandReport.html');

		$html = str_replace('{Content}', $first, $layout);
		$dom = \phpQuery::newDocument($html);
		$dom = str_replace('{demandInfo}',  json_encode($demandInfo), $dom);
		$dom = str_replace('{allForecastDataList}',  json_encode($allForecastDataList), $dom);
		$dom = str_replace('::version::', uniqid(), $dom);
		$dashboardTypeName = $_SESSION["demand-enabled"] > 0 ? 'demand' : 'london';
		$html = str_replace( '{dashboardType}', $dashboardTypeName, $html );
//		// JavaScript 多國語言
//		$jsLanguage = file_get_contents(HDPath .'/page/js/Language.js');
//		$dom = str_replace('//jsLanguage', $jsLanguage, $dom);
//
//		// 多國語言替換
//		$dom = \Module\Language::Convert($dom);

		echo $dom;
		// 開始寫入 html [E]
	}

    function updateData(){
        $beginDate = filter_input(INPUT_POST, 'begin-date', FILTER_SANITIZE_STRING);
        $endDate = filter_input(INPUT_POST, 'end-date', FILTER_SANITIZE_STRING);

        $result = FALSE;
        $message = '';
        $dataArray = [];
        do {
            // 抓取需量資料 [B]
            $demandInfo = getDemandData($beginDate, $endDate);
            // 抓取需量資料 [E]
            // 抓取預測資料 [B]
            $allForecastData = getAllForecastData($beginDate, $endDate);
            // 抓取預測資料 [E]
            $dataArray['forecast_data'] = $allForecastData;
            $dataArray['demand_info'] = $demandInfo;
            $result = TRUE;
        } while(false);
         $returnArray = [
            'result' => $result,
            'message' => $message,
            'data' => $dataArray
        ];
        echo json_encode($returnArray);
        exit;
    }
    
}



