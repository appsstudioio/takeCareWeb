<?php
/*
응급실 실시간 가용병상정보 조회 오퍼레이션 명세
주소를 기준으로 응급실 실시간 가용병상정보 등을 조회하는 응급실 실시간 가용병상정보 조회 기능제공
http://apis.data.go.kr/B552657/ErmctInfoInqireService/getEmrrmRltmUsefulSckbdInfoInqire
STAGE1=주소(시도)
STAGE2=시군구
pageNo=페이지번호
numOfRows=목록 건수
*/

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

$_numOfRows = "1000";
// &STAGE1=%EC%84%9C%EC%9A%B8%ED%8A%B9%EB%B3%84%EC%8B%9C&STAGE2=%EC%A2%85%EB%A1%9C%EA%B5%AC&pageNo=1
$_ip = 'http://apis.data.go.kr/B552657/ErmctInfoInqireService/getEmrrmRltmUsefulSckbdInfoInqire?serviceKey='._SERVICE_KEY.'&numOfRows='.$_numOfRows;
$xml = simplexml_load_file($_ip);

//var_dump($xml);
//echo '<br><br>';
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
	$syncArray[0]["errorMsg"] = "응급실 실시간 가용병상정보 동기화 시작";
	$syncArray[0]["sdate"] = $sdate;
	$syncArray[0]["edate"] = $sdate;
	$syncArray[0]["syncID"] = "HOSP_MAX_VAR_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	
	for($i=0; $i<$cnt; $i++) 
	{
		$data = $xml->body->items->item[$i];
		
		// echo 'hvidate : '.$data->hvidate.' change : '.date("Y-m-d H:i:s", strtotime($data->hvidate)).'<br>';
		if($_pdoObject->update_hosp_max_var_info($data) == true)
		{
			$_tSuccess++;
		}
		else
		{
			$_tFail++;
			$errorMsg = 'DB 추가 에러 ('.$data->hpid.', '.$data->dutyName.')';
			$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
		}
		
	}
	$syncArray[0]["status_code"] = "SUCCESS";
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = "응급실 실시간 가용병상정보 동기화 완료";
	$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
else
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
	
	$syncArray[0]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[0]["errorMsg"] = "응급실 실시간 가용병상정보 동기화 실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[0]["tCount"] = 0;
	$syncArray[0]["tSuccess"] = 0;
    $syncArray[0]["tFail"] = 1;
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[0]["syncID"] = "HOSP_MAX_VAR_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
?>