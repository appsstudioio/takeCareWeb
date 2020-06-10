<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
ini_set('display_errors','on');

$_utilLibrary = new utilLibrary();
$StatusCode = "0";
$sdate = "";
$syncArray = array();
$tSuccess = 0;
$tFail = 0;

$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);


// 동기화 정보 테이블 체크 및 생성
if($_pdoObject->CheckTableInfo("sync_monitoring_info") == false)
{
	$_pdoObject->CreateSyncMonitoringInfoTableFunc();
	$_pdoObject->CreateSyncErrorInfoTableFunc();
}

$_numOfRows = "10000";
$_ip = 'http://openapi.airkorea.or.kr/openapi/services/rest/MsrstnInfoInqireSvc/getMsrstnList?pageNo=1&numOfRows='.$_numOfRows.'&ServiceKey='._SERVICE_KEY;
$xml = simplexml_load_file($_ip);

// echo $_ip.'<br>';
// var_dump($xml);
// echo '<br><br>';
if((string)$xml->header->resultCode == "00")
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
	echo 'numOfRows  : '.$xml->body->numOfRows.'<br>';
	echo 'pageNo     : '.$xml->body->pageNo.'<br>';
	echo 'totalCount : '.$xml->body->totalCount.'<br><br>';
	
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
	$syncArray[0]["errorMsg"] = "미세먼지 측정소 정보 동기화 시작";
	$syncArray[0]["sdate"] = $sdate;
	$syncArray[0]["edate"] = $sdate;
	$syncArray[0]["syncID"] = "AIRKOREA_STATION_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	
	for($i=0; $i<$cnt; $i++) 
	{
		$data = $xml->body->items->item[$i];
		if($_pdoObject->update_airkorea_station_info($data) == true)
		{
			$_tSuccess++;
		}
		else
		{
			$_tFail++;
			$errorMsg = 'DB 추가 에러 ('.$data->stationName.', '.$data->addr.')';
			$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
		}
		
	}
	$syncArray[0]["status_code"] = "SUCCESS";
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = "미세먼지 측정소 정보 동기화 완료";
	$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	
}
else
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';

	$syncArray[0]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[0]["errorMsg"] = "미세먼지 측정소 정보 동기화 실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[0]["tCount"] = 0;
	$syncArray[0]["tSuccess"] = 0;
    $syncArray[0]["tFail"] = 1;
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[0]["syncID"] = "AIRKOREA_STATION_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
$_pdoObject = null;

?>