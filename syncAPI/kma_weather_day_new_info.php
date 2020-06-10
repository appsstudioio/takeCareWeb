<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
ini_set('display_errors','on');
ini_set('memory_limit', '-1');
set_time_limit(0);

// https://appsstudio.site/syncAPI/kma_weather_day_new_info.php?sido=세종특별자치시&areaNo=3100000000
$sido = urldecode($_REQUEST["sido"]);
$areaNo = $_REQUEST["areaNo"];

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

// 지역코드 가져오기
$query_notice = "
    SELECT `sido`, `areaX`, `areaY` 
    FROM
    	locationInfo 
    WHERE
    	`sigungu` <> '' 
    AND `dong` <> '' 
    AND `sido` = :sido
    GROUP BY
    	`areaX`,
    	`areaY` 
";
try
{
	$stmt = $_pdoObject->_connection->prepare($query_notice);
	$stmt->bindParam(":sido", $sido, PDO::PARAM_STR);
	$stmt->execute();
	$locationArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
catch(Exception $e)
{
	$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
}

$data = array();
$_syncTitleName = "";
if(count($locationArray) > 0) {
    $_syncTitleName = $sido." 초단기실황 날씨 정보 동기화 ";               
    $_tSuccess = 0;
	$_tFail = 0;
	$sdate = date("Y-m-d H:i:s", time());
	// 동기화 상태 초기화..
	$syncArray[0]["sdate"] = $sdate;
    $syncArray[0]["edate"] = $sdate;
	$syncArray[0]["tCount"] = count($locationArray);
	$syncArray[0]["syncID"] = "KMA_WEATHER_NEW_DAY_".$areaNo;
        
    for($k=0; $k<count($locationArray); $k++) {                    
        $data["adate"] = "";
        $data["basedate"] = "";
        $data["nx"] = $locationArray[$k]["areaX"];
        $data["ny"] = $locationArray[$k]["areaY"];
        // 당일
        $data["RN1"] = "";
        $data["PTY"] = "";
        $data["UUU"] = "";
        $data["VVV"] = "";
        $data["REH"] = "";
        $data["SKY"] = "";
        $data["T1H"] = "";
        $data["VEC"] = "";
        $data["WSD"] = "";
        $data["LGT"] = "";
    		
    	$_ip = "http://apis.data.go.kr/1360000/VilageFcstInfoService/getUltraSrtNcst";
        $_ip .= "?serviceKey="._SERVICE_KEY_NEW;
        $_ip .= "&base_date=".date("Ymd", time());
        $_ip .= "&base_time=".date("H", time())."00";
        $_ip .= "&nx=".$locationArray[$k]["areaX"];
        $_ip .= "&ny=".$locationArray[$k]["areaY"];
        $_ip .= "&numOfRows=30";
        $_ip .= "&pageNo=1&dataType=XML";
        $xml = simplexml_load_file($_ip);
        echo $_ip.'<br><br>';
        // var_dump($xml); 
        if( ((int)$xml->header->resultCode) == 0)
        {
        	echo 'resultCode : '.$xml->header->resultCode.'<br>';
        	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
            echo 'numOfRows  : '.$xml->body->numOfRows.'<br>';
            echo 'pageNo     : '.$xml->body->pageNo.'<br>';
            echo 'totalCount : '.$xml->body->totalCount.'<br><br>';
 
            $_totalCount = (int)$xml->body->totalCount;
            $cnt = count($xml->body->items->item);
            for($i=0; $i<count($xml->body->items->item); $i++) 
        	{
        		$item = $xml->body->items->item[$i];        
    
                $fcstDateTime = $item->baseDate.$item->baseTime;
                if($i == 0) {
                    $data["adate"] = date("Y-m-d", strtotime($item->baseDate));
                    $data["basedate"] = $fcstDateTime;
                } else {
                   if($fcstDateTime != $data["basedate"]) {
                        if($_pdoObject->update_kma_weather_new_day_info($data) == false)
                		{
                			$errorMsg = 'DB 추가 에러 ('.$data["nx"].', '.$data["ny"].', '.$data.')';
                			$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
                		}
                   }
                   $data["adate"] = date("Y-m-d", strtotime($item->baseDate));
                   $data["basedate"] = $fcstDateTime;
                }
                
                if($item->category == "RN1") { $data["RN1"] = $item->obsrValue; }
                if($item->category == "PTY") { $data["PTY"] = $item->obsrValue; }
                if($item->category == "UUU") { $data["UUU"] = $item->obsrValue; }
                if($item->category == "VVV") { $data["VVV"] = $item->obsrValue; }
                if($item->category == "REH") { $data["REH"] = $item->obsrValue; }
                if($item->category == "SKY") { $data["SKY"] = $item->obsrValue; }
                if($item->category == "T1H") { $data["T1H"] = $item->obsrValue; }
                if($item->category == "VEC") { $data["VEC"] = $item->obsrValue; }
                if($item->category == "WSD") { $data["WSD"] = $item->obsrValue; }
                if($item->category == "LGT") { $data["LGT"] = $item->obsrValue; }
        	}
        	
        	if($data["PTY"] != "") 
        	{
            	if($_pdoObject->update_kma_weather_new_day_info($data) == true)
        		{
        			$_tSuccess++;
        		}
        		else
        		{
        			$_tFail++;
        			// $_tFail++;
        			$errorMsg = 'DB 추가 에러 ('.$locationArray[$k]["sido"].' '.$data["nx"].', '.$data["ny"].', '.$_ip.')';
        			$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
        		}
        	} else {
            	$_tFail++;
        	}
        }
        else
        {
        	echo 'resultCode : '.$xml->header->resultCode.'<br>';
        	echo 'resultMsg  : '.$xml->header->resultMsg.'<br>';
        	$errorMsg = 'xml request 에러 ('.$_ip.', '.$xml->header->resultCode.', '.$xml->header->resultMsg.')';
        	$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
        	// var_dump($xml); 
        	$_tFail++;
        }
    }
    $syncArray[0]["status_code"] = ($_tSuccess == 0 ? "FAIL" : "SUCCESS");
    $syncArray[0]["tSuccess"] = $_tSuccess;
    $syncArray[0]["tFail"] = $_tFail;
    $syncArray[0]["errorMsg"] = $_syncTitleName."완료";
    $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
    
    $_pdoObject->update_sync_monitoring_info($syncArray[0]);
}
$_pdoObject = null;
?>
