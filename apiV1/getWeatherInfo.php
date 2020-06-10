<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
header("Content-Type: text/json; charset=utf-8;");
// http://13.125.129.82/apiV1/getWeatherInfo.php?wgs84Lon=128.9152527&wgs84Lat=37.7679253
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
	try
	{
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
    if(count($localArray) > 0)
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
		try
		{
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

		// 주간 날씨정보 AND `basedate` >= :basedate
		$query_notice = "SELECT * FROM KMA_WEATHER_WEEK_INFO WHERE `basedate` >= :basedate AND `nx` = :nx AND `ny` = :ny ";
		try
		{
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
		try
		{
			$stmt = $_pdoObject->_connection->prepare($query_notice);

			// $stmt->bindParam(":adate", $todate, PDO::PARAM_STR);
			$stmt->bindParam(":adate", $todate, PDO::PARAM_STR);
			$stmt->bindParam(":nx", $localArray[0]["areaX"], PDO::PARAM_STR);
			$stmt->bindParam(":ny", $localArray[0]["areaY"], PDO::PARAM_STR);
			$stmt->execute();
			$daylistArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e)
		{
			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
		}

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
		try
		{
			$stmt = $_pdoObject->_connection->prepare($query_notice);

			$atodate = date("Y-m-d", strtotime($todate."-1 days"));
			$stmt->bindParam(":adate", $atodate, PDO::PARAM_STR);
			$stmt->bindParam(":nx", $localArray[0]["areaX"], PDO::PARAM_STR);
			$stmt->bindParam(":ny", $localArray[0]["areaY"], PDO::PARAM_STR);
			$stmt->execute();
			$prevDaylistArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch(Exception $e)
		{
			$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
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
);

if(count($json_array_result) != 0) echo json_encode($json_array_result);
$_utilLibrary = null;
?>
