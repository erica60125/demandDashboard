<?php

use Module\Layout;


function getDemandData() {
    // 抓取需量資料 [B]
    $demandInfoField = '`id`, `demand_quarter`, `Temperature`, `T_Min`, `T_Max`, `datetime`';
    $londonInfoField = '`id`, `energy`, `datetime`';
	$fieldSelect = $_SESSION["london-enabled"] > 0 ? $londonInfoField : $demandInfoField;
	$tableName = $_SESSION["london-enabled"] > 0 ? 'halfhourly_avg' : 'demand_with_weather_data';
    $demandInfoTable = $tableName;
	
    $demandInfoWhereAssoc = [];
    $demandInfoExtraAssoc = [
        'orderby' => '`datetime` DESC',
        'limit' => 96
    ];
//	$whereString = "`datetime` >= '2020-08-10 00:00:00' AND `datetime` <= '2020-08-11 00:00:00'";
//	$sql = "SELECT {$fieldSelect} FROM {$tableName} WHERE {$whereString} ORDER BY `datetime`";
//	$prepare = [];
    $demandInfoResult = doTableQuery($demandInfoTable, $fieldSelect, $demandInfoWhereAssoc, $demandInfoExtraAssoc);
//	$demandInfoResult = doQuerySQL($sql, $prepare);
    if (!$demandInfoResult['result']) {
        $message = $demandInfoResult['message'];
    }

    return array_reverse($demandInfoResult['dataArray']);
    // 抓取需量資料 [E]
}

function getAllForecastData() {
    // 抓取預測資料 [B]
    $allDemandForecastList = [];
    $demandForecastField = '`prediction_1`, `prediction_2`, `prediction_3`, `prediction_4`, `created_at`';
    $demandForecastTableArray = ['demand_predict'];
    $demandForecastWhereAssoc = [];
    $demandForecastExtraAssoc = [
        'orderby' => '`created_at` DESC',
        'limit' => 102
    ];
	
    foreach ($demandForecastTableArray as $searchTableName) {
//		$whereString = "`created_at` >= '2020-08-10 00:00:00' AND `created_at` <= '2020-08-11 00:00:00'";
//		$sql = "SELECT {$demandForecastField} FROM {$searchTableName} WHERE {$whereString} ORDER BY `created_at`";
//		$prepare = [];
//		$demandForecastResult = doQuerySQL($sql, $prepare);
        $demandForecastResult = doTableQuery($searchTableName, $demandForecastField, $demandForecastWhereAssoc, $demandForecastExtraAssoc);
        if (!$demandForecastResult['result']) {
            $message = $demandForecastResult['message'];
        }
        $allDemandForecastList[$searchTableName] = array_reverse($demandForecastResult['dataArray']);
    }
    return $allDemandForecastList;
    // 抓取預測資料 [B]
}





class Dashboard
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
			$dashboardType = filter_input(INPUT_POST, 'dashboard-type', FILTER_SANITIZE_STRING);
			if (!empty($dashboardType)) {
				$_SESSION["demand-enabled"] = $dashboardType == 'demand' ? 1 : 0;
				$_SESSION["london-enabled"] = $dashboardType == 'london' ? 1 : 0;
			}
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

//		$header = $_ENV['menu'][$_GET['a']];

		// 抓取需量資料 [B]
        $demandInfo = getDemandData();
        // 抓取需量資料 [E]
        // 抓取預測資料 [B]
        $allForecastDataList = getAllForecastData();
        // 抓取預測資料 [E]

		$first = file_get_contents(__DIR__ . '/../templates/Dashboard.html');

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
        $result = FALSE;
        $message = '';
        $dataArray = [];
        do {
            // 抓取需量資料 [B]
            $demandInfo = getDemandData();
            // 抓取需量資料 [E]
            // 抓取預測資料 [B]
            $allForecastData = getAllForecastData();
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

    function getWeatherData() {
        $weatherInfoField = '`TEMP`, `D_TX`, `D_TN`, `Weather`';
        $weatherInfoWhereAssoc = [];
        $weatherInfoExtraAssoc = [
            'orderby' => 'observe_time DESC',
            'limit' => 1
        ];
        $weatherInfoResult = doTableQueryOne('weather_data_cwb', $weatherInfoField, $weatherInfoWhereAssoc, $weatherInfoExtraAssoc);
        $returnArray = [
            'result' => TRUE,
            'message' => '',
            'data' => $weatherInfoResult['data']
        ];
        echo json_encode($returnArray);
        exit;
    }
    function getRainingRate() {
        $now = date('Y-m-dTH:i:s');
        $rainingRateJson = json_decode(file_get_contents("https://opendata.cwb.gov.tw/api/v1/rest/datastore/F-C0032-001?Authorization=CWB-61B968D9-68B1-44CD-87D6-3B39404E4A3F&locationName=%E8%87%BA%E5%8D%97%E5%B8%82&elementName=PoP&sort=time&timeFrom=$now"), TRUE);
        $rainingRate = $rainingRateJson['records']['location'][0]['weatherElement'][0]['time'][0]['parameter']['parameterName'];
        $returnArray = [
            'result' => TRUE,
            'message' => '',
            'data' => $rainingRate
        ];
        echo json_encode($returnArray);
        exit;
    }

    function getCurrentDemand() {
        $dataArray = [];
        $today = date('Y-m-d H:i:00');
        # 最大需量 [B]
        $yesterday = date('Y-m-d H:i:00', strtotime('-1 days'));
        $maxDemandInfoWhereAssoc = [
            'datetime' => ">= {$yesterday}"
        ];
        $maxDemandInfoExtraAssoc = [
            'limit' => 1
        ];
        $maxDemandInfoResult = doTableQueryOne('demand_with_weather_data', 'MAX(demand_quarter) AS `max_demand`', $maxDemandInfoWhereAssoc, $maxDemandInfoExtraAssoc);
        # 最大需量 [E]
        # 現在需量 [B]
        $currentDemandInfoWhereAssoc = [
            'datetime' => ">= {$yesterday}"
        ];
        $currentDemandInfoExtraAssoc = [
            'orderby' => 'datetime DESC',
            'limit' => 1
        ];
        $currentInfoResult = doTableQueryOne('demand_with_weather_data', 'demand_quarter', $currentDemandInfoWhereAssoc, $currentDemandInfoExtraAssoc);
        # 現在需量 [E]
        $dataArray = [
            'max_demand' => $maxDemandInfoResult['data']['max_demand'],
            'current_demand' => $currentInfoResult['data']['demand_quarter']
        ];

        $returnArray = [
            'result' => TRUE,
            'message' => '',
            'data' => $dataArray
        ];
        echo json_encode($returnArray);
        exit;
    }
}



