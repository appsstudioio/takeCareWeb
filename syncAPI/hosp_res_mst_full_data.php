<?php
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

$sdate = date("Y-m-d H:i:s", time());
$_totalCount = 0;
$_tSuccess = 0;
$_tFail = 0;
// 동기화 상태 초기화..
$syncArray[0]["status_code"] = "START";
$syncArray[0]["tCount"] = $_totalCount;
$syncArray[0]["tSuccess"] = $_tSuccess;
$syncArray[0]["tFail"] = $_tFail;
$syncArray[0]["errorMsg"] = "병의원 FULL DATA 동기화 시작";
$syncArray[0]["sdate"] = $sdate;
$syncArray[0]["edate"] = $sdate;
$syncArray[0]["syncID"] = "HOSP_RES_MST_FULL_DATA";
$_pdoObject->update_sync_monitoring_info($syncArray[0]);

$_pageNo = 1;
$_numOfRows = 20;
$_TPage = 0;
$_ip = 'http://apis.data.go.kr/B552657/HsptlAsembySearchService/getHsptlMdcncFullDown?serviceKey='._SERVICE_KEY.'&numOfRows='.$_numOfRows.'&pageNo='.$_pageNo;
$xml = simplexml_load_file($_ip);

//var_dump($xml);
// echo '<br><br>';
/*
H000	기관구분	A	종합병원
H000	기관구분	B	병원
H000	기관구분	C	의원
H000	기관구분	E	한방병원
H000	기관구분	G	한의원
H000	기관구분	H	약국
H000	기관구분	M	치과병원
H000	기관구분	N	치과의원
H000	기관구분	O	부속병의원
H000	기관구분	P	조산원
H000	기관구분	R	보건소
해당 정보만 수집한다...
*/
if((string)$xml->header->resultCode == "00")
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
	echo 'numOfRows  : '.$xml->body->numOfRows.'<br>';
	echo 'pageNo     : '.$xml->body->pageNo.'<br>';
	echo 'totalCount : '.$xml->body->totalCount.'<br>';
	
	$_totalCount = (int)$xml->body->totalCount;
	// 총페이지
	$_TPage = (int)(($_totalCount-1)/$_numOfRows)+1; 
	$cnt = count($xml->body->items->item); 
	
	// 동기화 상태 초기화..
	$syncArray[0]["status_code"] = "START";
	$syncArray[0]["tCount"] = $_totalCount;
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = "병의원 FULL DATA 동기화 시작";
	$syncArray[0]["sdate"] = $sdate;
	$syncArray[0]["edate"] = $sdate;
	$syncArray[0]["syncID"] = "HOSP_RES_MST_FULL_DATA";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);

	for($i=0; $i<$cnt; $i++) 
	{
		$data = $xml->body->items->item[$i];
		if($data->dutyDiv == "A" || $data->dutyDiv == "B" || $data->dutyDiv == "C" || $data->dutyDiv == "E" || $data->dutyDiv == "G" || $data->dutyDiv == "H" || $data->dutyDiv == "M" || $data->dutyDiv == "N" || $data->dutyDiv == "O" || $data->dutyDiv == "P" || $data->dutyDiv == "R") 
		{
			if($_pdoObject->update_hosp_res_mst_full_data($data) == true)
			{
				$_tSuccess++;
			}
			else
			{
				$_tFail++;
				$errorMsg = 'DB 추가 에러 ('.$data->hpid.', '.$data->dutyName.', '.count($data).')';
				$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
			}
		}
		else 
		{
			$_totalCount--;
		}
		
	}
	$_pageNo++;
	if($_pageNo < $_TPage)
	{
		for($p=$_pageNo; $p<=$_TPage; $p++)
		{
			$_ip = 'http://apis.data.go.kr/B552657/HsptlAsembySearchService/getHsptlMdcncFullDown?serviceKey='._SERVICE_KEY.'&numOfRows='.$_numOfRows.'&pageNo='.$p;
			$xml = simplexml_load_file($_ip);
			if((string)$xml->header->resultCode == "00")
			{
				$cnt = count($xml->body->items->item);
				for($i=0; $i<$cnt; $i++) 
				{
					$data = $xml->body->items->item[$i];
					if($data->dutyDiv == "A" || $data->dutyDiv == "B" || $data->dutyDiv == "C" || $data->dutyDiv == "E" || $data->dutyDiv == "G" || $data->dutyDiv == "H" || $data->dutyDiv == "M" || $data->dutyDiv == "N" || $data->dutyDiv == "O" || $data->dutyDiv == "P" || $data->dutyDiv == "R") 
					{

						if($_pdoObject->update_hosp_res_mst_full_data($data) == true)
						{
							$_tSuccess++;
						}
						else
						{
							$_tFail++;
							$errorMsg = 'DB 추가 에러 ('.$data->hpid.', '.$data->dutyName.', '.count($data).')';
							$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
						}
					}
					else 
					{
						$_totalCount--;
					}
				}
			}
		}
	}
	
	$syncArray[0]["status_code"] = "SUCCESS";
	$syncArray[0]["tSuccess"] = $_tSuccess;
	$syncArray[0]["tFail"] = $_tFail;
	$syncArray[0]["errorMsg"] = "병의원 FULL DATA 동기화 완료";
	$syncArray[0]["edate"] = date("Y-m-d H:i:s", time());

	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
else
{
	echo 'resultCode : '.$xml->header->resultCode.'<br>';
	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
	
	$syncArray[0]["status_code"] = "FAIL (".$xml->header->resultCode.")";
    $syncArray[0]["errorMsg"] = "병의원 FULL DATA 동기화 실패 [".(string)$xml->header->resultMsg."]";
    $syncArray[0]["tCount"] = 0;
	$syncArray[0]["tSuccess"] = 0;
    $syncArray[0]["tFail"] = 1;
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    $syncArray[0]["syncID"] = "HOSP_RES_MST_FULL_DATA";
	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
?>