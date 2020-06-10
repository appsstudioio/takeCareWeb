<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
header("Content-Type: text/json; charset=utf-8;");
// http://13.125.129.82/apiV1/getWeatherInfoV3.php?wgs84Lon=128.9152527&wgs84Lat=37.7679253
$wgs84Lon = $_REQUEST["wgs84Lon"];
$wgs84Lat = $_REQUEST["wgs84Lat"];

// 반경 10 = 10km
$distance = 20;

$startrow = ($page-1)*$per_page;

$StatusCode = "0";
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
		if(count($array) > 0) 
		{
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
	, 'LIST_MESURE'        => $mesurelistArray
);

if(count($json_array_result) != 0) echo json_encode($json_array_result);
$_utilLibrary = null;
?>
