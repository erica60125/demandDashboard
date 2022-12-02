<?php
namespace Module;


class Layout
{
	public static function Front()
	{
		$layout = file_get_contents(__DIR__ .'../../templates/Layout/tbody.html');

        $dom = \phpQuery::newDocument($layout);

        $html = $dom;
        $html = str_replace( '::version::', uniqid(), $html );

        //
        //foreach( $_ENV['menu'] as $k=>$v ){
        //    $html = str_replace( '{menu/'. $k .'}', $v, $html );
        //}

        // 作用中網頁
        $html = str_replace( '{'. $_GET['a'] .'}', 'activeLink', $html );
//        $html = str_replace('{UserName}', $_SESSION['user-name'], $html);

        return $html;
	}

    //
	public static function Admin($page=null)
	{
		global $G_ROLE_ADMINISTRATOR, $G_ROLE_MANAGER, $G_ROLE_CUSTOMER_SERVICE, $G_ROLE_ENGINEERING, $G_ROLE_ACCOUNTANT, $G_ROLE_SALES_ASSISTANT;

		$layout = file_get_contents(__DIR__ .'/../../templates/Layout/tbody.html');
        $layout = str_replace('{Title}', $page['Title'], $layout);

        $dom = \phpQuery::newDocument($layout);

        // URL a 選單折疊
        $menu = $_GET['a'];
		$topMenu = explode('/', $menu)[0];

        $html = $dom;
        $html = str_replace( '{menu/'. $menu .'}', 'active', $html );
        $html = str_replace( '{menu/'. $menu .'/active}', 'active', $html );
		$dashboardTypeName = $_SESSION["demand-enabled"] > 0 ? 'demand' : 'london';
		$notDashboardTypeName = $_SESSION["demand-enabled"] > 0 ? 'london' : 'demand';
		$html = str_replace( '{dashboardType}', $dashboardTypeName, $html );
		$html = str_replace( '{notDashboardTypeName}', $notDashboardTypeName, $html );
		
//        $html = str_replace( '{Username}', $_SESSION['user-name'], $html );
		
        //
        if ($topMenu == 'Client' ) {
            $html = str_replace('{menu/User/active}', 'active', $html);
            $html = str_replace('{menu/Client/active}', 'active', $html);
            $html = str_replace('{menu/Client/Collapsed}', '', $html);
            $html = str_replace('{menu/Client/show}', 'show', $html);
        }
        else{
            $html = str_replace('{menu/Client/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/Client/show}', '', $html);
        }

        //
        if ($topMenu == 'Financial' ) {
            $html = str_replace('{menu/Financial/active}', 'active', $html);
            $html = str_replace('{menu/Financial/Collapsed}', '', $html);
            $html = str_replace('{menu/Financial/show}', 'show', $html);
        }
        else{
            $html = str_replace('{menu/Financial/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/Financial/show}', '', $html);
        }

        return $html;
	}

    //

    //
	public static function User($page=null)
	{
		global $PDO;

		$layout = file_get_contents(HD_PATH .'/page/Layout/User.html');
        $layout = str_replace('{Title}', $page['Title'], $layout);

        $dom = \phpQuery::newDocument($layout);

        // 該使用者最後讀取警報時間
        $sql = " SELECT * FROM `User` WHERE id=:id ";
        $pre = $PDO->prepare($sql);
        $pre->bindValue(':id', $_SESSION['User']['id']);
        $pre->execute();
        //$user = array();date('Y-m-d H:i:s')
        $alarm_read_time = '0000-00-00 00:00:00';
        if ( $pre->rowCount() >=1 ){
            $user = $pre->fetch(2);
            $user_status = json_decode($user['Status'], true);
            if ( $user_status['AlarmReadTime'] ) $alarm_read_time = $user_status['AlarmReadTime'];
        }

        // Alarm 數量
        $sql = " SELECT Alarm.id FROM Alarm JOIN User_Channel ON Alarm.Gateway_id=User_Channel.Gateway_id AND Alarm.Model=User_Channel.Model AND Alarm.`Phase`=User_Channel.`Phase` AND Alarm.Address=User_Channel.Address WHERE User_Channel.User_id=:User_id AND Alarm.DataTime>=:AlarmReadTime ";
        $pre = $PDO->prepare($sql);
        $pre->bindValue(':User_id', $_SESSION['User']['id']);
        $pre->bindValue(':AlarmReadTime', $alarm_read_time);
        $pre->execute();

        $alarm_number = $pre->rowCount();

        if ( $alarm_number >= 100 ) $alarm_number = '99+';

        $dom->find('#AlarmNumber')->html($alarm_number);

        // 店家名稱
//        $UserName = $_SESSION['User']['Name'];

        // 找出所有後台選單 確定權限
        foreach( pq('.menuItem',$dom) as $item ){

            //echo pq($item)->attr('data-id'); $_SESSION['User']['Identity'] Meter

            // 沒有使用電錶
            if ( $_SESSION['User']['Identity'] !='Meter' ){
                if ( pq($item)->attr('data-id') =='User/Project' || pq($item)->attr('data-id') =='User/TOUSetting' || pq($item)->attr('data-id') =='Collapse/UserReport' ){
                    pq($item)->remove();
                }
            }
        }

        // --------------------------------------------------------
        $html = $dom;

        // URL a 選單
        $menu = $_GET['a'];

        // 選單 active class 處理
        $html = str_replace( '{menu/'. $_GET['a'] .'}', 'active', $html );

        //
//        $html = str_replace( '{UserName}', $UserName, $html );
//        $html = str_replace( '{PageCount}', PageCount, $html );
        $html = str_replace( '::Language::', $_SESSION['Language'], $html );
        $html = str_replace( '::version::', uniqid(), $html );
        $html = str_replace( '{'. $_GET['a'] .'}', 'activeLink', $html );

        // 選單折疊判斷處理
        if ( $menu == 'User/ReportChannelData' || $menu == 'User/ReportAlarm' || $menu == 'User/ReportUsageAmount' || $menu == 'User/ReportDemand' || $menu == 'User/ReportEnergyCompare' || $menu == 'User/ReportSettlement' ){
            $html = str_replace('{menu/Report/Collapsed}', 'active', $html );
            $html = str_replace('{menu/Report/show}', 'show', $html );
        }
        else{
            $html = str_replace('{menu/Report/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/Report/show}', '', $html);
        }

        if ( $menu == 'User/ReportEnergyCompare' ){
            $html = str_replace('{menu/ReportEnergy/Collapsed}', 'active', $html );
            $html = str_replace('{menu/ReportEnergy/show}', 'show', $html );
        }
        else{
            $html = str_replace('{menu/ReportEnergy/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/ReportEnergy/show}', '', $html);
        }

        if ( $menu == 'User/ReportEnergySaving' ){
            $html = str_replace('{menu/ReportEnergySaving/Collapsed}', 'active', $html );
            $html = str_replace('{menu/ReportEnergySaving/show}', 'show', $html );
        }
        else{
            $html = str_replace('{menu/ReportEnergySaving/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/ReportEnergySaving/show}', '', $html);
        }

        if ( $menu == 'User/LiveVirtualMeter' || $menu == 'User/LiveChannel' ){
            $html = str_replace('{menu/LiveInfo/Collapsed}', 'active', $html);
            $html = str_replace('{menu/LiveInfo/show}', 'show', $html);
        }
        else{
            $html = str_replace('{menu/LiveInfo/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/LiveInfo/show}', '', $html);
        }

        // eMap
        if ( $menu == 'User/EMapSetting' || $menu == 'User/EMapView' ){
            $html = str_replace('{menu/EMap/Collapsed}', 'active', $html );
            $html = str_replace('{menu/EMap/show}', 'show', $html );
        }
        else{
            $html = str_replace('{menu/EMap/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/EMap/show}', '', $html);
        }

        // ReportGateway
        if ( $menu == 'User/ReportDeviceDateRange' || $menu == 'User/ReportDeviceCourse' || $menu == 'User/ReportDeviceCompare' || $menu == 'User/ReportDeviceDay' || $menu == 'User/ReportDeviceWeek' || $menu == 'User/ReportDeviceMonth' || $menu == 'User/ReportDeviceYear' ) {
            $html = str_replace('{menu/ReportDevice/Collapsed}', '', $html);
            $html = str_replace('{menu/ReportDevice/show}', 'show', $html);
        }
        else{
            $html = str_replace('{menu/ReportDevice/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/ReportDevice/show}', '', $html);
        }

        // ReportGroup
        if ( $menu == 'User/ReportVirtualMeterDateRange' || $menu == 'User/ReportVirtualMeterCourse' || $menu == 'User/ReportVirtualMeterCompare' || $menu == 'User/ReportVirtualMeterDay' ) {
            $html = str_replace('{menu/ReportVirtualMeter/Collapsed}', '', $html);
            $html = str_replace('{menu/ReportVirtualMeter/show}', 'show', $html);
        }
        else{
            $html = str_replace('{menu/ReportVirtualMeter/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/ReportVirtualMeter/show}', '', $html);
        }

        //
        if ( $menu == 'User/SettingLocation' ) {
            $html = str_replace('{menu/Setting/Collapsed}', '', $html);
            $html = str_replace('{menu/Setting/show}', 'show', $html);
        }
        else{
            $html = str_replace('{menu/Setting/Collapsed}', 'collapsed', $html);
            $html = str_replace('{menu/Setting/show}', '', $html);
        }

        return $html;
	}

    //
	public static function OnePage($page=null)
	{
		//global $PDO;

		$layout = file_get_contents(HD_PATH .'/page/Layout/OnePage.html');
        $layout = str_replace('{Title}', $page['Title'], $layout);

        $dom = \phpQuery::newDocument($layout);

		// 登入
		if ( $_SESSION['UserInfo']['id'] ){
            $dom->find('.LoginNo')->remove();
		}

		//
		else{
            $dom->find('.LoginYes')->remove();
		}

        $html = $dom;
        $html = str_replace( '::version::', uniqid(), $html );
        $html = str_replace( '{'. $_GET['a'] .'}', 'activeLink', $html );

        // 多國語言替換
        $html = \Module\Language::Convert($html);

        return $html;
	}

}
