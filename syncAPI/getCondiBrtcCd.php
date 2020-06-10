<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
ini_set('display_errors','on');

$_utilLibrary = new utilLibrary();
$StatusCode = "0";
$sdate = "";
$syncArray = array();
$tSuccess = 0;
$tFail = 0;

/*
$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);

// 동기화 정보 테이블 체크 및 생성
if($_pdoObject->CheckTableInfo("sync_monitoring_info") == false)
{
	$_pdoObject->CreateSyncMonitoringInfoTableFunc();
	$_pdoObject->CreateSyncErrorInfoTableFunc();
}
*/

$_ip = 'https://nip.cdc.go.kr/irapi/rest/getCondVcnCd.do?serviceKey=';
$headers = array(
	'Content-Type: text/xml', 'Accept: text/xml' 
);
// Open connection
$ch = curl_init();
// Set the url, number of POST vars, POST data
curl_setopt( $ch, CURLOPT_URL, $_ip );
curl_setopt( $ch, CURLOPT_POST, false );
curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
// Avoids problem with https certificate
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt( $ch, CURLOPT_TIMEOUT, 20);

// Execute post
$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if((int)$http_code == 200)
{
	echo $http_code.':성공!! -> <br>'.$result;
}
else
{
	echo $http_code;
}

/*
$ch = cURL_init();

cURL_setopt($ch, CURLOPT_URL, $url);
cURL_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$response = cURL_exec($ch);
cURL_close($ch); 
var_dump($response);

$object = simplexml_load_string($response);
var_dump($object);
*/

/*
$_ip = 'https://nip.cdc.go.kr/irapi/rest/getCondBrtcCd.do?serviceKey='._SERVICE_KEY;
// $_ip = 'https://nip.cdc.go.kr/irapi/rest/getCondSggCd.do?FbrtcCd=1100000000&serviceKey=tP2sGrkjz6fVT6Xo3r1eTKs1gIsxzs7Z0z6sj675AHS%2FsbhpPw0FkpfBtd8au2RVHNJMVtiup1jLkya%2B46xbxw%3D%3D';
$xml = simplexml_load_file($_ip);

echo $_ip.'<br><br>';
var_dump($xml);

// unecho '<br><br>';
if((string)$xml->header->resultCode == "00")
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
	echo 'numOfRows  : '.$xml->body->numOfRows.'<br>';
	echo 'pageNo     : '.$xml->body->pageNo.'<br>';
	echo 'totalCount : '.$xml->body->totalCount.'<br>';

	$_totalCount = (int)$xml->body->totalCount;
	$cnt = count($xml->body->items->item);

	$_tSuccess = 0;
	$_tFail = 0;
	$sdate = date("Y-m-d H:i:s", time());
	// 동기화 상태 초기화..
	$syncArray[0]["status_code"] = "START";
	$syncArray[0]["tCount"] = $_totalCount;
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = "코드마스터 정보 동기화 시작";
	$syncArray[0]["sdate"] = $sdate;
	$syncArray[0]["edate"] = $sdate;
	$syncArray[0]["syncID"] = "CodeMast";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);

	if($_pdoObject->delete_CodeMast() == true)
	{
		for($i=0; $i<$cnt; $i++)
		{
			$data = $xml->body->items->item[$i];

			if($_pdoObject->insert_CodeMast($data)== true)
			{
				$_tSuccess++;
			}
			else
			{
				$_tFail++;
				$errorMsg = 'DB 추가 에러 ('.$data->cmMid.', '.$data->cmMnm.', '.$data->cmSid.', '.$data->cmSnm.')';
				$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
			}
		}

		$syncArray[0]["status_code"] = "SUCCESS";
		$syncArray[0]["tSuccess"] = $_tSuccess;
		$syncArray[0]["tFail"] = $_tFail;
		$syncArray[0]["errorMsg"] = "코드마스터 정보 동기화 완료";
		$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());

		$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	}
	else
	{
		$syncArray[0]["status_code"] = "FAIL";
	    $syncArray[0]["errorMsg"] = "코드마스터 정보 삭제 실패";
	    $syncArray[0]["tFail"] = 1;
	    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
		$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	}

}
else
{

	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';

	$syncArray[0]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[0]["errorMsg"] = "코드마스터 정보 동기화 실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[0]["tCount"] = 0;
	$syncArray[0]["tSuccess"] = 0;
    $syncArray[0]["tFail"] = 1;
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[0]["syncID"] = "CodeMast";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);

}
*/
?>
