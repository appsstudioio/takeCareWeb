<?php
/*
기관 정보 업데이트, 진료과목, 등 누락된 데이터를 받아온다.
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

$query_listCount = "
    SELECT *
    FROM
      HOSP_RES_MST
    WHERE status_code = '1'
  ";

//echo $query_listCount;
$stmt = $_pdoObject->_connection->prepare($query_listCount);
$stmt->execute();
$rs_array = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($rs_array) > 0)
{
  $_totalCount = count($rs_array);
  $_tSuccess = 0;
  $_tFail = 0;
  $_kiosk_totalCount = 0;
  $_kiosk_tSuccess = 0;
  $_kiosk_tFail = 0;
  $sdate = date("Y-m-d H:i:s", time());
  // 동기화 상태 초기화..
  $syncArray[0]["status_code"] = "START";
  $syncArray[0]["tCount"] = $_totalCount;
  $syncArray[0]["tSuccess"] = $_tSuccess;
  $syncArray[0]["tFail"] = $_tFail;
  $syncArray[0]["errorMsg"] = "병의원 정보 업데이트 시작";
  $syncArray[0]["sdate"] = $sdate;
  $syncArray[0]["edate"] = $sdate;
  $syncArray[0]["syncID"] = "HOSP_RES_MST_UPDATE";
  $_pdoObject->update_sync_monitoring_info($syncArray[0]);

  for($i=0; $i<count($rs_array); $i++)
	{

		$_ip = 'http://apis.data.go.kr/B552657/ErmctInfoInqireService/getEgytBassInfoInqire?serviceKey='._SERVICE_KEY.'&HPID='.$rs_array[$i]["hpid"].'&numOfRows=10&pageNo=1';
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
					$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
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
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = "병의원 정보 업데이트 동기화 완료";
	$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());

	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
else
{
  $syncArray[0]["status_code"] = "FAIL";
  $syncArray[0]["errorMsg"] = "병의원 정보 업데이트 동기화 실패";
  $syncArray[0]["tCount"] = 0;
  $syncArray[0]["tSuccess"] = 0;
  $syncArray[0]["tFail"] = 1;
  $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
  $syncArray[0]["syncID"] = "HOSP_RES_MST_UPDATE";
  $_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
?>
