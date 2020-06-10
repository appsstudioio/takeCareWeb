<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
header("Content-Type: text/json; charset=utf-8;");
// http://13.125.129.82/apiV1/getWeatherInfoV2.php?wgs84Lon=128.9152527&wgs84Lat=37.7679253
$wgs84Lon = $_REQUEST["wgs84Lon"];
$wgs84Lat = $_REQUEST["wgs84Lat"];

// 반경 10 = 10km
$distance = 20;

$startrow = ($page-1)*$per_page;

$StatusCode = "0";
$listArray  = array();
$prevDaylistArray = array();
$daylistArray = array();
$weeklistArray = array();
$mesurelistArray = array();
$localArray = array();

$currentTime = date("Hi",time());
$_utilLibrary = new utilLibrary();

$todate = date("Y-m-d", time());
$basedate = date("YmdH", time())."00";

// echo $todate.'<br>';
// echo $basedate.'<br>';
$TPage = 0;
if ($wgs84Lon != "" && $wgs84Lat != "")
{
	$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);
	$WhereSql = " WHERE sigungu <> '' AND dong <> '' ";
	
	try
	{
    	$query_notice = "
            SELECT AA.*
            FROM
            (
              SELECT *,
                ROUND(6371 * acos(cos(radians(:wgs84Lat_1)) * cos(radians(`lat`)) * cos(radians(`lon`) - radians(:wgs84Lon_1)) + sin(radians(:wgs84Lat_2)) * sin(radians(`lat`))), 2) AS distance
              FROM
                locationInfo
              ".$WhereSql."
            ) AA
          	ORDER BY AA.distance ASC
          	LIMIT 0, 1
        ";
		$stmt = $_pdoObject->_connection->prepare($query_notice);
		$stmt->bindParam(":wgs84Lat_1", $wgs84Lat, PDO::PARAM_STR);
		$stmt->bindParam(":wgs84Lon_1", $wgs84Lon, PDO::PARAM_STR);
		$stmt->bindParam(":wgs84Lat_2", $wgs84Lat, PDO::PARAM_STR);
		$stmt->execute();
		$localArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	catch(Exception $e)
	{
		$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
	}
	
	try
	{
    	$query_notice = "
            SELECT AA.*
            FROM
            (
                SELECT *,
                    ROUND(6371 * acos(cos(radians(:wgs84Lat_1)) * cos(radians(`dmX`)) * cos(radians(`dmY`) - radians(:wgs84Lon_1)) + sin(radians(:wgs84Lat_2)) * sin(radians(`dmX`))), 2) AS distance,
                    ( SELECT MIN(pm25Value) FROM AIRKOREA_API_DATA_INFO WHERE `dataTime` >= STR_TO_DATE(:dataTime,'%Y%m%d%H%i') AND `stationName` = AIRKOREA_STATION_INFO.stationName ) AS pm25Value
                FROM
                    AIRKOREA_STATION_INFO 
            ) AA
            WHERE
                AA.pm25Value <> '-'
          	ORDER BY AA.distance ASC
            LIMIT 0, 1
        ";
		$stmt = $_pdoObject->_connection->prepare($query_notice);
		$abasedate = date("YmdH", strtotime($basedate." -1 hour"))."00";
		$stmt->bindParam(":dataTime", $abasedate, PDO::PARAM_STR);
		$stmt->bindParam(":wgs84Lat_1", $wgs84Lat, PDO::PARAM_STR);
		$stmt->bindParam(":wgs84Lon_1", $wgs84Lon, PDO::PARAM_STR);
		$stmt->bindParam(":wgs84Lat_2", $wgs84Lat, PDO::PARAM_STR);
		$stmt->execute();
		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	catch(Exception $e)
	{
		$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
	}
	
    if(count($localArray) > 0)
	{
		if(count($array) > 0) {
            $localArray[0]["stationName"] = $array[0]["stationName"];
            $localArray[0]["addr"]        = $array[0]["addr"];
            $localArray[0]["mangName"]    = $array[0]["mangName"];
            $localArray[0]["item"]        = $array[0]["item"];
            $localArray[0]["dmX"]         = $array[0]["dmX"];
            $localArray[0]["dmY"]         = $array[0]["dmY"];
            $localArray[0]["dmDistance"]  = $array[0]["distance"];
            try
    		{
        		// 당일 미세먼지 정보 AND `basedate` >= :basedate
        		$query_notice = "
        		    SELECT 
            		    *
                    FROM 
                        AIRKOREA_API_DATA_INFO 
                    WHERE 
                        `dataTime` >= STR_TO_DATE(:dataTime,'%Y%m%d%H%i')
                    AND `stationName` = :stationName
                    ORDER BY dataTime ASC 
                ";
    			$stmt = $_pdoObject->_connection->prepare($query_notice);
                $abasedate = date("YmdH", strtotime($basedate." -1 hour"))."00";
    			$stmt->bindParam(":dataTime",    $abasedate, PDO::PARAM_STR);
    			$stmt->bindParam(":stationName", $localArray[0]["stationName"], PDO::PARAM_STR);
    			$stmt->execute();
    			$mesurelistArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
    		}
    		catch(Exception $e)
    		{
    			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
    		}
		} else {
    		$localArray[0]["stationName"] = "";
            $localArray[0]["addr"]        = "";
            $localArray[0]["mangName"]    = "";
            $localArray[0]["item"]        = "";
            $localArray[0]["dmX"]         = "";
            $localArray[0]["dmY"]         = "";
            $localArray[0]["dmDistance"]  = "";
		}
		
		try
		{
    		// 생활 및 보건 지수
    		$query_notice = "
    			SELECT KMA_API_INFO.*,
    			(SELECT getIdName FROM KMA_API_LIST_INFO WHERE `code` = KMA_API_INFO.`code` ) as code_name,
    			(SELECT apiType FROM KMA_API_LIST_INFO WHERE `code` = KMA_API_INFO.`code` ) as apiType
    			FROM 
    				KMA_API_INFO 
    			WHERE 
    				`adate` = :adate 
    			AND `areaNo` = :areaNo 
    		";
			$stmt = $_pdoObject->_connection->prepare($query_notice);
            
            if( ((int)date("H",time())) < 2 ) {
                $atodate = date("Y-m-d", strtotime($todate."-1 days"));
                $stmt->bindParam(":adate", $atodate, PDO::PARAM_STR);
            } else {
                $stmt->bindParam(":adate", $todate, PDO::PARAM_STR);
            }
			$stmt->bindParam(":areaNo", $localArray[0]["areaNo"], PDO::PARAM_STR);
			$stmt->execute();
			$listArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e)
		{
			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
		}
		
		try
		{
    		// 주간 날씨정보 AND `basedate` >= :basedate
            $query_notice = "SELECT * FROM KMA_WEATHER_WEEK_INFO WHERE `basedate` > :basedate AND `nx` = :nx AND `ny` = :ny ";
			$stmt = $_pdoObject->_connection->prepare($query_notice);

			// $stmt->bindParam(":adate", $todate, PDO::PARAM_STR);
			$stmt->bindParam(":basedate", $basedate, PDO::PARAM_STR);
			$stmt->bindParam(":nx", $localArray[0]["areaX"], PDO::PARAM_STR);
			$stmt->bindParam(":ny", $localArray[0]["areaY"], PDO::PARAM_STR);
			$stmt->execute();
			$weeklistArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e)
		{
			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
		}
		
		try
		{
    		// 당일 날씨정보 AND `basedate` >= :basedate
    		$query_notice = "
    		    SELECT 
        		    KMA_WEATHER_DAY_INFO.*,
                    ( SELECT MAX(TMN) FROM KMA_WEATHER_WEEK_INFO WHERE adate = KMA_WEATHER_DAY_INFO.adate AND `nx` = KMA_WEATHER_DAY_INFO.nx AND `ny` = KMA_WEATHER_DAY_INFO.ny ) AS TMN,
                    ( SELECT MAX(TMX) FROM KMA_WEATHER_WEEK_INFO WHERE adate = KMA_WEATHER_DAY_INFO.adate AND `nx` = KMA_WEATHER_DAY_INFO.nx AND `ny` = KMA_WEATHER_DAY_INFO.ny ) AS TMX 
                FROM KMA_WEATHER_DAY_INFO 
                WHERE 
                    `adate` = :adate
                AND `nx` = :nx 
                AND `ny` = :ny 
                ORDER BY basedate ASC ";
			$stmt = $_pdoObject->_connection->prepare($query_notice);

			$stmt->bindParam(":adate", $todate, PDO::PARAM_STR);
			// $stmt->bindParam(":basedate", $basedate, PDO::PARAM_STR);
			$stmt->bindParam(":nx", $localArray[0]["areaX"], PDO::PARAM_STR);
			$stmt->bindParam(":ny", $localArray[0]["areaY"], PDO::PARAM_STR);
			$stmt->execute();
			$daylistArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e)
		{
			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
		}

		try
		{
    		// 하루전 날씨정보 AND `basedate` >= :basedate
            $query_notice = "
    		    SELECT 
        		    KMA_WEATHER_DAY_INFO.*,
                    ( SELECT MAX(TMN) FROM KMA_WEATHER_WEEK_INFO WHERE adate = KMA_WEATHER_DAY_INFO.adate AND `nx` = KMA_WEATHER_DAY_INFO.nx AND `ny` = KMA_WEATHER_DAY_INFO.ny ) AS TMN,
                    ( SELECT MAX(TMX) FROM KMA_WEATHER_WEEK_INFO WHERE adate = KMA_WEATHER_DAY_INFO.adate AND `nx` = KMA_WEATHER_DAY_INFO.nx AND `ny` = KMA_WEATHER_DAY_INFO.ny ) AS TMX 
                FROM KMA_WEATHER_DAY_INFO 
                WHERE 
                    `adate` = :adate
                AND `nx` = :nx 
                AND `ny` = :ny 
                ORDER BY basedate ASC ";
			$stmt = $_pdoObject->_connection->prepare($query_notice);

			$abasedate = date("Y-m-d", strtotime($todate."-1 days"));
			$stmt->bindParam(":adate", $abasedate, PDO::PARAM_STR);
			$stmt->bindParam(":nx", $localArray[0]["areaX"], PDO::PARAM_STR);
			$stmt->bindParam(":ny", $localArray[0]["areaY"], PDO::PARAM_STR);
			$stmt->execute();
			$prevDaylistArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e)
		{
			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
		}
		
		// --------------------------------------------------------------------------------------------
		// 최신 실황정보 업데이트, 실황정보는 대략 1시간 정도의 오차가 발생한다. API 업데이트가 매시간 40분에 업데이트 되기 때문에
		// 오차가 발생하는 부분에 대해서 최신 정보에 강제로 반영하는 코드를 추가하여 정확도를 높인다...
		// --------------------------------------------------------------------------------------------
		try
		{
            $query_notice = "
    		    SELECT * 
    		    FROM KMA_WEATHER_NEW_DAY_INFO 
                WHERE `nx` = :nx  AND `ny` = :ny 
                ORDER BY basedate DESC
                LIMIT 0,1 
            ";
			$stmt = $_pdoObject->_connection->prepare($query_notice);
			$stmt->bindParam(":nx", $localArray[0]["areaX"], PDO::PARAM_STR);
			$stmt->bindParam(":ny", $localArray[0]["areaY"], PDO::PARAM_STR);
			$stmt->execute();
			$newvDayArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e)
		{
			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
		}
		
		if(count($newvDayArray) > 0) 
		{
    		$currentBaseTime = date("YmdH")."00";
    		for($i=0; $i<count($daylistArray); $i++) 
    		{
        	    if($daylistArray[$i]["basedate"] == $currentBaseTime)
        	    {
            	    $daylistArray[$i]["RN1"] = $newvDayArray[0]["RN1"];
                    $daylistArray[$i]["PTY"] = $newvDayArray[0]["PTY"];
                    $daylistArray[$i]["UUU"] = $newvDayArray[0]["UUU"];
                    $daylistArray[$i]["VVV"] = $newvDayArray[0]["VVV"];
                    $daylistArray[$i]["REH"] = $newvDayArray[0]["REH"];
                    $daylistArray[$i]["T1H"] = $newvDayArray[0]["T1H"];
                    $daylistArray[$i]["VEC"] = $newvDayArray[0]["VEC"];
                    $daylistArray[$i]["WSD"] = $newvDayArray[0]["WSD"];
        	    }
    		}
		}
	}
	else
	{
		$StatusCode = "2";
	}

	$_pdoObject = null;
}
else
{
	// 비정상 요청
	$StatusCode = "1";
}


$json_array_result = array(
	  'STATUSCODE' 	       => (string)$StatusCode
	, 'STATUSMSG'          => (string)$_utilLibrary->errorCheckReturnMsg($StatusCode)
	, 'LOCALINFO'          => $localArray[0]
	, 'LIST'               => $listArray
	, 'LIST_YESTERDAY'     => $prevDaylistArray
	, 'LIST_DAY'           => $daylistArray
	, 'LIST_WEEK'          => $weeklistArray
	, 'LIST_MESURE'        => $mesurelistArray
);

if(count($json_array_result) != 0) echo json_encode($json_array_result);
$_utilLibrary = null;
?>
