<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
header("Content-Type: text/json; charset=utf-8;");
// http://13.125.129.82/apiV1/clinicList.php?wgs84Lon=128.9152527&wgs84Lat=37.7679253&page=1&per_page=50&distance=10&openFlag=ON
$wgs84Lon = $_REQUEST["wgs84Lon"];
$wgs84Lat = $_REQUEST["wgs84Lat"];
$page     = $_REQUEST["page"];
// 페이지당 리스트 갯수
$per_page = $_REQUEST["per_page"];
$distance = $_REQUEST["distance"];
$openFlag = $_REQUEST["openFlag"];
if($openFlag == "") $openFlag = "OFF";

if($page == "") $page = 1;
else $page = (int)$page;

if($per_page == "") $per_page = 50;
else $per_page = (int)$per_page;
// 반경 10 = 10km
if($distance == "") $distance = 10;
else $distance = (int)$distance;

$startrow = ($page-1)*$per_page;

$StatusCode = "0";
$listArray = array();
$dataRSArray = array();

$currentTime = date("Hi",time());
$_utilLibrary = new utilLibrary();
$TPage = 0;
if ($wgs84Lon != "" && $wgs84Lat != "")
{
	$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);
	// 달빛어린이병원(Y), 병의원,치과(C), 보건소(CR), 한의원(G)은 제외
	$WhereSql = " WHERE status_code = '1' AND dutyEmcls IN ('G009', 'G099') AND baby_flag IN ('Y', 'C', 'CR') ";
	
	if($openFlag == "ON") {
		$weekday = date("w", time());
		$TODATE = date("Y-m-d",time());
		$Holiday = $_pdoObject->get_holiday_info($TODATE);
		if(count($Holiday) > 0) {
			if($Holiday[0]["dateKind"] != "N") {
				$weekday = 7;
			}
		}
		// $dow_array_KO = array("일", "월", "화", "수", "목", "금", "토");
		switch ($weekday) {
			case 0: $WhereSql .= " AND dutyTime7s <= :STIME AND dutyTime7c >= :ETIME ";
			case 1: $WhereSql .= " AND dutyTime1s <= :STIME AND dutyTime1c >= :ETIME ";
			case 2: $WhereSql .= " AND dutyTime2s <= :STIME AND dutyTime2c >= :ETIME ";
			case 3: $WhereSql .= " AND dutyTime3s <= :STIME AND dutyTime3c >= :ETIME ";
			case 4: $WhereSql .= " AND dutyTime4s <= :STIME AND dutyTime4c >= :ETIME ";
			case 5: $WhereSql .= " AND dutyTime5s <= :STIME AND dutyTime5c >= :ETIME ";
			case 6: $WhereSql .= " AND dutyTime6s <= :STIME AND dutyTime6c >= :ETIME ";
			case 7: $WhereSql .= " AND dutyTime8s <= :STIME AND dutyTime8c >= :ETIME ";
		}
	}
	
	$query_listCount = "
	SELECT COUNT(AA.hpid) AS CNT
	FROM
    (
      SELECT *,
        (6371 * acos(cos(radians(:wgs84Lat_1)) * cos(radians(wgs84Lat)) * cos(radians(wgs84Lon) - radians(:wgs84Lon_1)) + sin(radians(:wgs84Lat_2)) * sin(radians(wgs84Lat)))) AS distance
      FROM
        HOSP_RES_MST
      ".$WhereSql."
    ) AA
    WHERE AA.distance <= :distance
		";

	//echo $query_listCount;
	$stmt = $_pdoObject->_connection->prepare($query_listCount);
	$stmt->bindParam(":wgs84Lat_1", $wgs84Lat, PDO::PARAM_STR);
	$stmt->bindParam(":wgs84Lon_1", $wgs84Lon, PDO::PARAM_STR);
	$stmt->bindParam(":wgs84Lat_2", $wgs84Lat, PDO::PARAM_STR);
	if($openFlag == "ON") {
		$stmt->bindParam(":STIME", $currentTime, PDO::PARAM_STR);
		$stmt->bindParam(":ETIME", $currentTime, PDO::PARAM_STR);
	}
	$stmt->bindParam(":distance", $distance, PDO::PARAM_INT);
	$stmt->execute();
	$rs_array = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ptotal = 0;
	if(count($rs_array)>0)
	{
		$ptotal = (int)$rs_array[0]["CNT"];
	}

	$query_notice = "
    SELECT AA.*
    FROM
    (
      SELECT *,
        ROUND(6371 * acos(cos(radians(:wgs84Lat_1)) * cos(radians(wgs84Lat)) * cos(radians(wgs84Lon) - radians(:wgs84Lon_1)) + sin(radians(:wgs84Lat_2)) * sin(radians(wgs84Lat))), 2) AS distance
      FROM
        HOSP_RES_MST
      ".$WhereSql."
    ) AA
    WHERE AA.distance <= :distance
  	ORDER BY AA.distance ASC
  	LIMIT :STARTROW, :LIMITCNT
	";

  // echo '['.$ptotal.']('.$rs_array[0]["CNT"].')'.$query_notice;
	//echo "STARTROW [".$startrow."]<BR>";
	//echo "LIMITCNT [".$per_page."]<BR>";
	try
	{
		$stmt = $_pdoObject->_connection->prepare($query_notice);
		$stmt->bindParam(":wgs84Lat_1", $wgs84Lat, PDO::PARAM_STR);
		$stmt->bindParam(":wgs84Lon_1", $wgs84Lon, PDO::PARAM_STR);
		$stmt->bindParam(":wgs84Lat_2", $wgs84Lat, PDO::PARAM_STR);
		if($openFlag == "ON") {
			$stmt->bindParam(":STIME", $currentTime, PDO::PARAM_STR);
			$stmt->bindParam(":ETIME", $currentTime, PDO::PARAM_STR);
		}
		$stmt->bindParam(":distance", $distance, PDO::PARAM_INT);
		
		$stmt->bindParam(":STARTROW",  $startrow, PDO::PARAM_INT);
		$stmt->bindParam(":LIMITCNT",  $per_page, PDO::PARAM_INT);

		$stmt->execute();
		$dataRSArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	catch(Exception $e)
	{
		$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
	}

	//echo count($dataRSArray);

	if(count($dataRSArray) > 0)
	{
		$TPage = (int)(($ptotal-1)/$per_page)+1; // 총페이지
		$listArray = $dataRSArray;
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
	  'STATUSCODE' 	 => (string)$StatusCode
	, 'STATUSMSG'    => (string)($StatusCode == "2" ? "반경 내 해당하는 병의원이 없습니다." : $_utilLibrary->errorCheckReturnMsg($StatusCode) )
	, 'TPAGE' 	     => (string)$TPage
	, 'LIST'         => $listArray
);

if(count($json_array_result) != 0) echo json_encode($json_array_result);
$_utilLibrary = null;
?>
