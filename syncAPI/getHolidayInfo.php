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

$_ip = 'http://apis.data.go.kr/B090041/openapi/service/SpcdeInfoService/getHoliDeInfo?ServiceKey='._SERVICE_KEY."&solYear=".date("Y",time())."&solMonth=".date("m", time());
$xml = simplexml_load_file($_ip);
// echo $_ip.'<br><br>';
// var_dump($xml);
$_syncTitleName = date("Y", time()).".".date("m", time())." 휴일 정보 동기화 ";

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
	$syncArray[0]["errorMsg"] = $_syncTitleName."시작";
	$syncArray[0]["sdate"] = $sdate;
	$syncArray[0]["edate"] = $sdate;
	$syncArray[0]["syncID"] = "HOLIDAY_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);

	for($i=0; $i<$cnt; $i++)
	{
		$data = $xml->body->items->item[$i];
        // var_dump($data)."<br>";
        // { ["dateKind"]=> string(2) "01" ["dateName"]=> string(6) "설날" ["isHoliday"]=> string(1) "Y" ["locdate"]=> string(8) "20200124" ["seq"]=> string(1) "1" }
//         echo $data->isHoliday."<br>";
		if($_pdoObject->update_holiday_info((string)$data->locdate, (string)$data->isHoliday, (string)$data->dateName) == true)
		{
			$_tSuccess++;
		}
		else
		{
			$_tFail++;
			$errorMsg = 'DB 추가 에러 ('.$data->locdate.', '.$data->dateKind.', '.$data->dateName.')';
			$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
		}
	}

	$syncArray[0]["status_code"] = "SUCCESS";
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = $_syncTitleName."완료";
	$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());

	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
else
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';

	$syncArray[0]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[0]["errorMsg"] = $_syncTitleName."실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[0]["tCount"] = 0;
	$syncArray[0]["tSuccess"] = 0;
    $syncArray[0]["tFail"] = 1;
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[0]["syncID"] = "HOLIDAY_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}


/*
// ===================================================================================================================================
// 국가예방접종 스케쥴 등록
$_ip = 'https://nip.cdc.go.kr/irapi/rest/getCondVcnCd.do?serviceKey='._SERVICE_KEY;
$xml = simplexml_load_file($_ip);
echo $_ip.'<br><br>';
var_dump($xml);
$_syncTitleName = "국가예방접종 정보 동기화 ";

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
	$syncArray[0]["errorMsg"] = $_syncTitleName."시작";
	$syncArray[0]["sdate"] = $sdate;
	$syncArray[0]["edate"] = $sdate;
	$syncArray[0]["syncID"] = "SCHEDULE_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);

	if($cnt > 0) 
	{
		for($i=0; $i<$cnt; $i++)
		{
			$data = $xml->body->items->item[$i];

			$_ipSub = 'https://nip.cdc.go.kr/irapi/rest/getVcnInfo.do?serviceKey='._SERVICE_KEY.'&vcnCd='.$data->cd;
			$subXml = simplexml_load_file($_ipSub);
			$subCnt = count($subXml->body->items->item);
			if($subCnt > 0) 
			{
				$subData = $subXml->body->items->item[0];
				if($_pdoObject->update_vcn_schedule_info($data->cd, $subData->title, $subData->message) == true)
				{
					$_tSuccess++;
				}
				else
				{
					$_tFail++;
					$errorMsg = 'DB 추가 에러 ('.$data->locdate.', '.$data->dateKind.', '.$data->dateName.')';
					$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
				}
			}
			
		}
	}

	$syncArray[0]["status_code"] = "SUCCESS";
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = $_syncTitleName."완료";
	$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());

	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
else
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';

	$syncArray[0]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[0]["errorMsg"] = $_syncTitleName."실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[0]["tCount"] = 0;
	$syncArray[0]["tSuccess"] = 0;
    $syncArray[0]["tFail"] = 1;
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[0]["syncID"] = "SCHEDULE_INFO";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
*/
?>
