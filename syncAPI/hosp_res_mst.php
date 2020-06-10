<?php
/*
응급의료기관 기본정보 조회 오퍼레이션 명세
기관ID를 기준으로 진료요일, 응급실 정보 등을 조회하는 응급의료기관 기본정보 조회 기능제공
http://apis.data.go.kr/B552657/ErmctInfoInqireService/getEgytBassInfoInqire
HPID=기관ID
pageNo=페이지 번호
numOfRows=목록 건수
*/
include_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
ini_set('display_errors','on');
// 메모리 사이즈를 무제한으로 설정한다. 현재 데이터가 너무 커서 설정한 아파치 웹서버 메모리 사이즈를 초과한다. 
// 2016.12.13 메모리 사이즈를 무제한으로 설정하여 처리... 
ini_set('memory_limit', '-1');
set_time_limit(0);

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


/* 전국 응급의료 정보룰 가져온다.. */
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
	
	$_kiosk_totalCount = $_totalCount;
	$_kiosk_tSuccess = 0;
	$_kiosk_tFail = 0;
	$sdate = date("Y-m-d H:i:s", time());
	// 동기화 상태 초기화..
	$syncArray[0]["status_code"] = "START";
	$syncArray[0]["tCount"] = $_kiosk_totalCount;
	$syncArray[0]["tSuccess"] = $_kiosk_tSuccess;
	$syncArray[0]["tFail"] = $_kiosk_tFail;
	$syncArray[0]["errorMsg"] = "중증질환자 수용가능정보 동기화 시작";
	$syncArray[0]["sdate"] = $sdate;
	$syncArray[0]["edate"] = $sdate;
	$syncArray[0]["syncID"] = "KIOSK_MAX_HOSP_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	
	$_totalCount = $_totalCount;
	$_tSuccess = 0;
	$_tFail = 0;
	// 동기화 상태 초기화..
	$syncArray[1]["status_code"] = "START";
	$syncArray[1]["tCount"] = $_totalCount;
	$syncArray[1]["tSuccess"] = $_tSuccess;
	$syncArray[1]["tFail"] = $_tFail;
	$syncArray[1]["errorMsg"] = "응급의료기관 기본정보 동기화 시작";
	$syncArray[1]["sdate"] = $sdate;
	$syncArray[1]["edate"] = $sdate;
	$syncArray[1]["syncID"] = "HOSP_RES_MST_EMCLS";
	$_pdoObject->update_sync_monitoring_info($syncArray[1]);

	for($i=0; $i<$cnt; $i++) 
	{
		$data = $xml->body->items->item[$i];

		$_ip = 'http://apis.data.go.kr/B552657/ErmctInfoInqireService/getEgytBassInfoInqire?serviceKey='._SERVICE_KEY.'&HPID='.$data->hpid.'&numOfRows=10&pageNo=1';
		$xml2 = simplexml_load_file($_ip);
		
		//var_dump($xml2);
		//echo '<br><br>';
		if((string)$xml2->header->resultCode == "00")
		{
			echo 'resultCode : '.$xml2->header->resultCode.'<br>';
			echo 'resultMsg  : '.$xml2->header->resultMsg.'<br>';
			echo 'totalCount : '.$xml2->body->totalCount.'<br>';

			// 총페이지
			$subCnt = count($xml2->body->items->item); 
		
			for($k=0; $k<$subCnt; $k++) 
			{
				$subData = $xml2->body->items->item[$k];
				if($_pdoObject->update_hosp_res_mst($subData) == true)
				{
					$_tSuccess++;
				}
				else
				{
					$_tFail++;
					$errorMsg = 'DB 추가 에러 ('.$subData->hpid.', '.$subData->dutyName.', '.count($subData).')';
					$_pdoObject->insert_sync_error_info($syncArray[1]["syncID"], $errorMsg);
				}
				
				if($subData->MKioskTy25 != "")
				{
					$_kiosk_totalCount++;
					if($_pdoObject->update_kiosk_max_hosp_info($subData) == true)
					{
						$_kiosk_tSuccess++;
					}
					else
					{
						$_kiosk_tFail++;
						$errorMsg = 'DB 추가 에러 ('.$subData->hpid.', '.$subData->dutyName.')';
						$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
					}
				}
			}
		}
	}
	$syncArray[0]["status_code"] = "SUCCESS";
	$syncArray[0]["tSuccess"] = $_kiosk_tSuccess;
	$syncArray[0]["tFail"] = $_kiosk_tFail;
	$syncArray[0]["errorMsg"] = "중증질환자 수용가능정보 동기화 완료";
	$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());

	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	
	$syncArray[1]["status_code"] = "SUCCESS";
	$syncArray[1]["tSuccess"] = $_tSuccess;
	$syncArray[1]["tFail"] = $_tFail;
	$syncArray[1]["errorMsg"] = "응급의료기관 기본정보 동기화 완료";
	$syncArray[1]["edate"] = date("Y-m-d H:i:s", time());

	$_pdoObject->update_sync_monitoring_info($syncArray[1]);
}
else
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
	
	$syncArray[0]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[0]["errorMsg"] = "중증질환자 수용가능정보 동기화 실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[0]["tCount"] = 0;
	$syncArray[0]["tSuccess"] = 0;
    $syncArray[0]["tFail"] = 1;
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[0]["syncID"] = "KIOSK_MAX_HOSP_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
	
	$syncArray[1]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[1]["errorMsg"] = "응급의료기관 기본정보 동기화 실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[1]["tCount"] = 0;
	$syncArray[1]["tSuccess"] = 0;
    $syncArray[1]["tFail"] = 1;
    $syncArray[1]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[1]["syncID"] = "HOSP_RES_MST_EMCLS";
	$_pdoObject->update_sync_monitoring_info($syncArray[1]);
}
?>