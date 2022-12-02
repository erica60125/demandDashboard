<?php
namespace Admin;


class Login
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

        $html = file_get_contents(__DIR__ . '/../../templates/Admin/Login.html');

        $dom = \phpQuery::newDocument($html);

        echo $dom;
    }

    function Process()
    {
        global $G_ROLE_ACCOUNTANT, $G_ROLE_SALES_ASSISTANT, $G_ROLE_ENGINEERING;
        $resultArray = [
    		'result' => FALSE,
			'message' => '',
			'data' => []
		];

		$loginUser = filter_input(INPUT_POST, 'login-user', FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_AMP);
		$loginPassword = filter_input(INPUT_POST, 'login-password', FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_AMP);


		$userWhereAssoc = [
			'`user`' => $loginUser,
			'`role_id`' => "<= {$G_ROLE_SALES_ASSISTANT}"
		];
		$userResult = doTableQueryOne('`users`', '*', $userWhereAssoc);
		if (!$userResult['result']) {
			$resultArray['message'] = $userResult['message'];
			echo json_encode($resultArray);
			exit;
		}
		if (empty($userResult['data'])) {
			$resultArray['message'] = '帳號或密碼錯誤!';
			echo json_encode($resultArray);
			exit;
		}

		if ($userResult['data']['pwd'] !== $loginPassword) {
			$resultArray['message'] = "密碼已錯誤 {$userResult['data']['loginerrcount']} 次!";
			echo json_encode($resultArray);
			exit;
		}

		$_SESSION['userindex'] = $userResult['data']['index'];
		$_SESSION['user-name'] = $userResult['data']['username'];
		$_SESSION['user'] = $userResult['data']['user'];
		$_SESSION['role-id'] = $userResult['data']['role_id'];

		$resultArray['data']['location'] = in_array($_SESSION['role-id'], [$G_ROLE_ACCOUNTANT, $G_ROLE_SALES_ASSISTANT, $G_ROLE_ENGINEERING]) ? './?a=SalesOrder'  : './?a=User';
		$resultArray['result'] = TRUE;
        echo json_encode($resultArray);
    }

}
