<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
header("Content-Type: text/json; charset=utf-8;");
// http://13.125.129.82/apiV1/getLocationWeatherInfo.php?wgs84Lon=128.9152527&wgs84Lat=37.7679253&type=weather&distance=10
$wgs84Lon = $_REQUEST["wgs84Lon"];
$wgs84Lat = $_REQUEST["wgs84Lat"];
$type     = $_REQUEST["type"];
$distance = $_REQUEST["distance"];
// 페이지당 리스트 갯수
$per_page = $_REQUEST["per_page"];
if($distance == "") $distance = 10;
if($per_page == "") $per_page = 20;
else $per_page = (int)$per_page;

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
if ($wgs84Lon != "" && $wgs84Lat != "" && $type != "")
{
	$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);
	
	if($type == "weather") {
    	try
    	{
        	$query_notice = "
                SELECT AA.*, BB.*
                FROM
                (
                  SELECT *,
                    ROUND(6371 * acos(cos(radians(:wgs84Lat_1)) * cos(radians(`lat`)) * cos(radians(`lon`) - radians(:wgs84Lon_1)) + sin(radians(:wgs84Lat_2)) * sin(radians(`lat`))), 2) AS distance
                  FROM
                    locationInfo
                  WHERE sigungu <> '' AND dong <> '' 
                ) AA INNER JOIN (
                    SELECT 
            		    KMA_WEATHER_DAY_INFO.*,
                        ( SELECT MAX(TMN) FROM KMA_WEATHER_WEEK_INFO WHERE adate = KMA_WEATHER_DAY_INFO.adate AND `nx` = KMA_WEATHER_DAY_INFO.nx AND `ny` = KMA_WEATHER_DAY_INFO.ny ) AS TMN,
                        ( SELECT MAX(TMX) FROM KMA_WEATHER_WEEK_INFO WHERE adate = KMA_WEATHER_DAY_INFO.adate AND `nx` = KMA_WEATHER_DAY_INFO.nx AND `ny` = KMA_WEATHER_DAY_INFO.ny ) AS TMX 
                    FROM KMA_WEATHER_DAY_INFO 
                    WHERE 
                        `basedate` = :basedate
                ) BB ON AA.`areaX` = BB.nx AND AA.`areaY` = BB.ny 
                WHERE AA.distance <= :distance
              	ORDER BY AA.distance ASC 
                LIMIT 0, :LIMITCNT 
            ";
    		$stmt = $_pdoObject->_connection->prepare($query_notice);
    		$stmt->bindParam(":wgs84Lat_1", $wgs84Lat, PDO::PARAM_STR);
    		$stmt->bindParam(":wgs84Lon_1", $wgs84Lon, PDO::PARAM_STR);
    		$stmt->bindParam(":wgs84Lat_2", $wgs84Lat, PDO::PARAM_STR);
    		$stmt->bindParam(":basedate", $basedate, PDO::PARAM_STR);
    		$stmt->bindParam(":distance", $distance, PDO::PARAM_INT);
            $stmt->bindParam(":LIMITCNT",  $per_page, PDO::PARAM_INT);
    		$stmt->execute();
    		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
    	}
    	catch(Exception $e)
    	{
    		$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
    	}
	} else if($type == "dust") {
    	try
    	{
        	$query_notice = "
                SELECT AA.*, BB.*
                FROM
                (
                    SELECT *,
                        ROUND(6371 * acos(cos(radians(:wgs84Lat_1)) * cos(radians(`dmX`)) * cos(radians(`dmY`) - radians(:wgs84Lon_1)) + sin(radians(:wgs84Lat_2)) * sin(radians(`dmX`))), 2) AS distance,
                        ( SELECT MIN(pm25Value) FROM AIRKOREA_API_DATA_INFO WHERE `dataTime` = STR_TO_DATE(:dataTime1,'%Y%m%d%H%i') AND `stationName` = AIRKOREA_STATION_INFO.stationName ) AS pm25Value
                    FROM
                        AIRKOREA_STATION_INFO 
                ) AA INNER JOIN (
                    SELECT 
            		    *
                    FROM 
                        AIRKOREA_API_DATA_INFO 
                    WHERE 
                        `dataTime` = STR_TO_DATE(:dataTime2,'%Y%m%d%H%i')
                ) BB ON AA.pm25Value <> '-' AND AA.`stationName` = BB.`stationName`
                WHERE
                    AA.distance <= :distance
              	ORDER BY AA.distance ASC 
                LIMIT 0, :LIMITCNT 
            ";
    		$stmt = $_pdoObject->_connection->prepare($query_notice);
    		$stmt->bindParam(":wgs84Lat_1", $wgs84Lat, PDO::PARAM_STR);
    		$stmt->bindParam(":wgs84Lon_1", $wgs84Lon, PDO::PARAM_STR);
    		$stmt->bindParam(":wgs84Lat_2", $wgs84Lat, PDO::PARAM_STR);
    		$abasedate = date("YmdH", strtotime($basedate." -1 hour"))."00";
    		$stmt->bindParam(":dataTime1", $abasedate, PDO::PARAM_STR);
    		$stmt->bindParam(":dataTime2", $abasedate, PDO::PARAM_STR);
    		$stmt->bindParam(":distance", $distance, PDO::PARAM_INT);
            $stmt->bindParam(":LIMITCNT",  $per_page, PDO::PARAM_INT);
    		$stmt->execute();
    		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
    	}
    	catch(Exception $e)
    	{
    		$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
    	}
    } else {
		$StatusCode = "1";
	}
	
	if($StatusCode == "0" && count($array) < 1) {
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
	  'STATUSCODE'  => (string)$StatusCode
	, 'STATUSMSG'   => (string)$_utilLibrary->errorCheckReturnMsg($StatusCode)
	, 'LIST'        => $array
);

if(count($json_array_result) != 0) echo json_encode($json_array_result);
$_utilLibrary = null;
?>
