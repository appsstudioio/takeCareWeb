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
// A01_2 : 식중독,  A07_1 : 자외선, A22 : 더위체감지수, D04 : 피부질환가능지수, D05 : 감기가능지수, D01 : 천식폐질환가능지수   6, 18시에만 동기화
$whereSql = "";
if((string)date("H", time()) != "06" && (string)date("H", time()) != "18"){
    $whereSql = " AND `code` NOT IN ('A01_2', 'A07_1', 'A22', 'D05', 'D01', 'D04') ";
}
// 동기화 목록
$query_notice = "SELECT * FROM KMA_API_LIST_INFO_NEW WHERE liveMonth LIKE '%".date("m", time())."%' ".$whereSql;
try
{
	$stmt = $_pdoObject->_connection->prepare($query_notice);
	$stmt->execute();
	$apiArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
catch(Exception $e)
{
	$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
}

// 지역코드 가져오기
$query_notice = "SELECT * FROM locationInfo WHERE sigungu <> '' AND dong <> '' ";
try
{
	$stmt = $_pdoObject->_connection->prepare($query_notice);
	$stmt->execute();
	$locationArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
catch(Exception $e)
{
	$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
}

$data = array();
$_syncTitleName = "";
if(count($apiArray) > 0) {
    for($i=0; $i<count($apiArray); $i++) {
        $_syncTitleName = $apiArray[$i]["getIdName"]." 정보 동기화 ";                
        $_tSuccess = 0;
    	$_tFail = 0;
    	$_totalCount = count($locationArray);
    	$sdate = date("Y-m-d H:i:s", time());
    	// 동기화 상태 초기화..
    	$syncArray[0]["status_code"] = "START";
    	$syncArray[0]["tCount"] = $_totalCount;
    	$syncArray[0]["tSuccess"] = $_tSuccess;
    	$syncArray[0]["tFail"] = $_tFail;
    	$syncArray[0]["errorMsg"] = $_syncTitleName."시작";
    	$syncArray[0]["sdate"] = $sdate;
    	$syncArray[0]["edate"] = $sdate;
    	$syncArray[0]["syncID"] = "KMA_API_LIST_INFO_".$apiArray[$i]["code"];
    	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
  
        // API 신청이 안됨...ㅠㅠ 
        /*    
        for($k=0; $k<count($locationArray); $k++) {
            
            if( $apiArray[$i]["getId"] == "getColdIdx" || $apiArray[$i]["getId"] == "getSkinDiseaseIdx" || $apiArray[$i]["getId"] == "getAsthmaIdx" || $apiArray[$i]["getId"] == "getFoodPoisoningIdx") {
                $_ip = "http://apis.data.go.kr/1360000/HealthWthrIdxService/".$apiArray[$i]["getId"]."?ServiceKey="._SERVICE_KEY_NEW."&areaNo=".$locationArray[$k]["areaNo"]."&time=".date("YmdH", time())."&pageNo=1&numOfRows=10&dataType=XML";
            } else {
                $_ip = "http://apis.data.go.kr/1360000/LivingWthrIdxService/".$apiArray[$i]["getId"]."?ServiceKey="._SERVICE_KEY_NEW."&areaNo=".$locationArray[$k]["areaNo"]."&time=".date("YmdH", time())."&pageNo=1&numOfRows=10&dataType=XML";
            }
            
            if( $apiArray[$i]["getId"] == "getHeatFeelingIdx" ) {
                $_ip = $_ip."&requestCode=A22";
            }
            $xml = simplexml_load_file($_ip);
            echo $_ip.'<br><br>';
            var_dump($xml); 
            if((string)$xml->Header->ReturnCode == "00")
            {
            	echo 'ReturnCode : '.$xml->Header->ReturnCode.'<br>';
            	echo 'ErrMsg     : '.$xml->Header->ErrMsg.'<br>';
            	echo 'SuccessYN  : '.$xml->Body->SuccessYN.'<br>';
            	echo $_syncTitleName.' : '.$locationArray[$k]["areaNo"].'('.$locationArray[$k]["sido"].')<br>';
                $IndexModel = $xml->Body->IndexModel;
                
                $data["adate"] = date("Y-m-d",time());
                $data["areaNo"] = $IndexModel->areaNo;
                $data["code"] = $IndexModel->code;
                $data["date"] = $IndexModel->date;
                if($apiArray[$i]["apiType"] == "A") {
                    $data["v1"] = $IndexModel->h3;
                    $data["v2"] = $IndexModel->h6;
                    $data["v3"] = $IndexModel->h9;
                    $data["v4"] = $IndexModel->h12;
                    $data["v5"] = $IndexModel->h15;
                    $data["v6"] = $IndexModel->h18;
                    $data["v7"] = $IndexModel->h21;
                    $data["v8"] = $IndexModel->h24;
                    $data["v9"] = $IndexModel->h27;
                    $data["v10"] = $IndexModel->h30;
                    $data["v11"] = $IndexModel->h33;
                    $data["v12"] = $IndexModel->h36;
                    $data["v13"] = $IndexModel->h39;
                    $data["v14"] = $IndexModel->h42;
                    $data["v15"] = $IndexModel->h45;
                    $data["v16"] = $IndexModel->h48;
                } else {
                    $data["v1"] = $IndexModel->today;
                    $data["v2"] = $IndexModel->tomorrow;
                    $data["v3"] = $IndexModel->theDayAfterTomorrow;
                    $data["v4"] = "";
                    $data["v5"] = "";
                    $data["v6"] = "";
                    $data["v7"] = "";
                    $data["v8"] = "";
                    $data["v9"] = "";
                    $data["v10"] = "";
                    $data["v11"] = "";
                    $data["v12"] = "";
                    $data["v13"] = "";
                    $data["v14"] = "";
                    $data["v15"] = "";
                    $data["v16"] = "";
                }
                
        		if($_pdoObject->update_kma_api_info($data) == true)
        		{
        			$_tSuccess++;
        		}
        		else
        		{
        			$_tFail++;
        			$errorMsg = 'DB 추가 에러 ('.$IndexModel->areaNo.', '.$IndexModel->code.', '.$IndexModel->date.')';
        			$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
        		}
            }
            else
            {
            	echo 'ReturnCode : '.$xml->Header->ReturnCode.'<br>';
            	echo 'ErrMsg     : '.$xml->Header->ErrMsg.'<br>';
            	$errorMsg = 'xml request 에러 ('.$_ip.', '.$xml->Header->ReturnCode.', '.$xml->Header->ErrMsg.')';
            	$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
            	// var_dump($xml); 
            	$_tFail++;
            }
        }
    */
        $syncArray[0]["status_code"] = ($_tSuccess == 0 ? "FAIL" : "SUCCESS");
        $syncArray[0]["tSuccess"] = $_tSuccess;
        $syncArray[0]["tFail"] = $_tFail;
        $syncArray[0]["errorMsg"] = $_syncTitleName."완료";
        $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
        
        $_pdoObject->update_sync_monitoring_info($syncArray[0]);
    }
}
?>
