<?php
//
header('Access-Control-Allow-Origin: *');

// 不緩存 http://php.net/manual/zh/function.header.php
header("Content-Type:text/html; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// 永遠的壽命 ...
//$life = 86400*3650;
$life = 4 * 600;

function start_session($expire = 600)
{
	ini_set('session.gc_maxlifetime',  $expire);
	ini_set('session.cookie_lifetime', $expire);

	if (empty($_COOKIE['PHPSESSID'])) {
		session_set_cookie_params($expire);
		session_start();
	} else {
		session_start();
		setcookie('PHPSESSID', session_id(), time() + $expire);
	}
}

start_session($life);

spl_autoload_register(function ($class_name) {
	$load_FileName = str_replace('\\', '/', $class_name);
	$file_name = __DIR__ ."/php/". $load_FileName .".php";
	if ( is_file($file_name) === true ) {
		require_once( $file_name );
	}
});

// 設定瀏覽器關閉 下次開啟還是繼續同一個 session_id
//echo $_COOKIE['CookieSession'] .'　　';
//if ( $_COOKIE['CookieSession'] !='' ){
//    session_id($_COOKIE['CookieSession']);
//}
//$CookieSession = isset($_COOKIE['CookieSession']) ? $_COOKIE['CookieSession'] : null;
//if($CookieSession) session_id($CookieSession);

//session_start([$life]);

// 瀏覽器語言 [B]
//$_SESSION['Language'] = G_SYSTEM_LANG;
// 瀏覽器語言 [E]

require_once '../config/config-patch.php';
require_once '../lib/phpQuery/phpQuery.php';
require_once '../lib/lib_log.php';
require_once '../lib/lib_mysqli.php';
require_once '../lib/lib_mysqli_exec.php';

use \Module\Layout;

//phpinfo();


class index {

	function __construct()
	{
		/**
		 * 頁面
		 * 由 ?a= 指定
		 */
		if ( !empty( $_GET['a'] ) ) {

			$ClassName = str_replace('/', '\\', $_GET['a']);
			$load_FileName = str_replace('\\', '/', $ClassName);
			$file_name = __DIR__ . "/php/" . $load_FileName . ".php";
			//echo $file_name;
			
			if (is_file($file_name) === false) {
				header('Location: ./');
				exit;
			}

			if (class_exists($ClassName)) {
				$$ClassName = new $ClassName();
			}
		}

		// 無指定 ?a 顯示預設首頁
		else {
			// 進入選擇頁面
			$_SESSION["demand-enabled"] = 1;
			$_SESSION["london-enabled"] = 0;
			$this->Page();
		}
	}

	//
	function Page()
	{
		header('location: ./?a=Dashboard');
		//$layout = Layout::Front();
		//$html = str_replace('::version::', uniqid(), $layout);
		//$html = str_replace('{{Title}}', '', $html);
		//$html = str_replace('{{UserName}}', $_SESSION['User']['displayName'], $html);
		//
		////
		//$dom = \phpQuery::newDocumentHTML($html);
		//
		////print_r($_SESSION);
		//
		//// 已登入
		//if ( $_SESSION['UserLogin'] =='Y' ){
		//    $dom->find('.LoginNo')->remove();
		//}
		//
		//// 未登入
		//else{
		//    $dom->find('.LoginYes')->remove();
		//}
		//
		//// 多國語言替換
		//$dom = \Module\Language::Convert($dom);
		//
		//echo $dom;
	}
	
	

}

new index();
