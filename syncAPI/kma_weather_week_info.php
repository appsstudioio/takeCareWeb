<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
ini_set('display_errors','off');
ini_set('memory_limit', '-1');
set_time_limit(0);

// https://appsstudio.site/syncAPI/kma_weather_week_info.php?sido=서울특별시&areaNo=1100000000
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
    $_syncTitleName = $sido." 동네예보(주간) 날씨 정보 동기화 ";               
    $_tSuccess = 0;
	$_tFail = 0;
	$sdate = date("Y-m-d H:i:s", time());
	// 동기화 상태 초기화..
	$syncArray[0]["sdate"] = $sdate;
    $syncArray[0]["edate"] = $sdate;
	$syncArray[0]["tCount"] = count($locationArray);
	$syncArray[0]["syncID"] = "KMA_WEATHER_WEEK_".$areaNo;
        
    for($k=0; $k<count($locationArray); $k++) {
        // Base_time  : 0200, 0500, 0800, 1100, 1400, 1700, 2000, 2300 (1일 8회)
        $_ip = "http://apis.data.go.kr/1360000/VilageFcstInfoService/getVilageFcst";
        $_ip .= "?serviceKey="._SERVICE_KEY_NEW;
        $_ip .= "&base_date=".date("Ymd", time());
        $_ip .= "&base_time=".date("H", time())."00";
        // $_ip .= "&base_date=20200427";
        // $_ip .= "&base_time=2300";
        $_ip .= "&nx=".$locationArray[$k]["areaX"];
        $_ip .= "&ny=".$locationArray[$k]["areaY"];
        $_ip .= "&numOfRows=30";
        $_ip .= "&pageNo=1&dataType=XML";
        $xml = simplexml_load_file($_ip);
        // echo $_ip.'<br><br>';
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
            
            $data["adate"] = "";
            $data["basedate"] = "";
            $data["nx"] = $locationArray[$k]["areaX"];
            $data["ny"] = $locationArray[$k]["areaY"];
            $data["POP"] = "";
            $data["PTY"] = "";
            $data["R06"] = "";
            $data["REH"] = "";
            $data["S06"] = "";
            $data["SKY"] = "";
            $data["T3H"] = "";
            $data["TMN"] = "";
            $data["TMX"] = "";
   
            // Base_time  : 0200, 0500, 0800, 1100, 1400, 1700, 2000, 2300 (1일 8회)
            // fcstTime  :  0600, 0900, 1200, 1500, 1800, 2100, 0000, 0300 (1일 8회)
            for($i=0; $i<count($xml->body->items->item); $i++) 
        	{
        		$item = $xml->body->items->item[$i];
                // $baseDateTime = $item->baseDate.$item->baseTime;
                $fcstDateTime = $item->fcstDate.$item->fcstTime;
                if($i == 0) {
                    $data["adate"] = date("Y-m-d", strtotime($item->fcstDate));
                    $data["basedate"] = $fcstDateTime;
                } else {
                   if($fcstDateTime != $data["basedate"]) {
                        //var_dump($data);
                        //echo "<br>";
                        if($_pdoObject->update_kma_weather_week_info($data) == false)
                		{
                			$_tFail++;
                			$errorMsg = 'DB 추가 에러 ('.$data["nx"].', '.$data["ny"].', '.$data.')';
                			$_pdoObject->insert_sync_error_info($syncArray[0]["syncID"], $errorMsg);
                		}
                   }
                   $data["adate"] = date("Y-m-d", strtotime($item->fcstDate));
                   $data["basedate"] = $fcstDateTime;
                }

                if($item->category == "POP") { $data["POP"] = $item->fcstValue; }
                if($item->category == "PTY") { $data["PTY"] = $item->fcstValue; }
                if($item->category == "R06") { $data["R06"] = $item->fcstValue; }
                if($item->category == "REH") { $data["REH"] = $item->fcstValue; }
                if($item->category == "S06") { $data["S06"] = $item->fcstValue; }
                if($item->category == "SKY") { $data["SKY"] = $item->fcstValue; }
                if($item->category == "T3H") { $data["T3H"] = $item->fcstValue; }
                if($item->category == "TMN") { $data["TMN"] = $item->fcstValue; }
                if($item->category == "TMX") { $data["TMX"] = $item->fcstValue; }
        	}
        	
        	if($data["SKY"] != "" && $data["PTY"] != "") 
        	{	
            	if($_pdoObject->update_kma_weather_week_info($data) == true)
        		{
        			$_tSuccess++;
        		}
        		else
        		{
        			$_tFail++;
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
?>
