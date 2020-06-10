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

/*
Z000[정보센터]
Y009[중증이용_연락번호구분]
Y008[중증이용_전원처리결과]
Y007[중증이용_의뢰경로]
Y006[중증이용_통화자_구분]
Y005[중증이용_부정확사유]
Y004[중증이용_수용사유]
Y003[중증이용_미수용사유]
Y002[질환별정보상태]
Y001[점검]
Y000[증상]
SIDO[지역코드]
S000[특수클리닉및센터]
P004[시도]
P003[진료과목]
P002[영업구분]
P001[의료기관구분]
O000[의료자원(실시간)]
N000[의료자원(필수)]
M000[중증응급실질환]
L000[지역별링크정보]
J004[지역보건소]
J003[일반의약업]
J002[응급의료기관]
J001[1339]
J000[직종]
H050[전문의구분]
H040[운영상태]
H030[응급의료기관구분]
H020[의료기관구분]
H010[설립구분]
H000[기관구분]
D001[중증질환_핫라인]
D000[진료과목]
AMBL[기도[1**]/호흡[2**]/순환[3**]/약품{4**}/고정[5**]/기타[6**]]
AED2[공동주택]
AED1[다중이용시설]
AED0[구비의무기관]
L[경상남도 시군구]
K[전라북도 시군구]
J[강원도 시군구]
I[강원도 시군구]
H[경기도 시군구]
G[경기도 시군구]
F[인천광역시 시군구]
E[대전광역시 시군구/충청북도 시군구]
D[광주광역시 시군구/전라남도 시군구]
C[대구광역시 시군구/경상북도 시군구]
B[부상광역시 시군구/울산광역 시시군구]
A[서울특별시 시군구/제주시 시군구]
*/

$_numOfRows = "5000";
$_ip = 'http://apis.data.go.kr/B552657/CodeMast/info?serviceKey='._SERVICE_KEY.'&numOfRows='.$_numOfRows.'&pageNo=1';
$xml = simplexml_load_file($_ip);

// echo $_ip.'<br><br>';
// var_dump($xml);
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
?>