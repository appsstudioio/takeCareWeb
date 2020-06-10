<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
ini_set('display_errors','on');
ini_set('memory_limit', '-1');
set_time_limit(0);

$_utilLibrary = new utilLibrary();
$StatusCode = "0";
$sdate = "";
$syncArray = array();
$tSuccess = 0;
$tFail = 0;

$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);
$_utilLibrary = new utilLibrary();

if((int)date("H",time()) == 2) {
    try
    {
        $deleteSql = "DELETE FROM KMA_WEATHER_WEEK_INFO WHERE `adate` < DATE_ADD(NOW(), INTERVAL '-2' DAY) ";
    	$stmt = $_pdoObject->_connection->prepare($deleteSql);
    	$_pdoObject->_connection->beginTransaction();
		$stmt->execute();
		$_pdoObject->_connection->commit();
    }
    catch(Exception $e)
    {
        $_pdoObject->_connection->rollback();
    	$_pdoObject->error("Delete query error : [".$deleteSql."] ".$e->getMessage()."\n");
    }
}

// 시도 정보 가져오기
$query_notice = "
        SELECT 
            CASE WHEN AA.sido = '강원도' THEN '4200000000' 
            WHEN AA.sido = '경기도' THEN '4100000000' 
            WHEN AA.sido = '경상남도' THEN '4800000000' 
            WHEN AA.sido = '경상북도' THEN '4700000000' 
            WHEN AA.sido = '광주광역시' THEN '2900000000' 
            WHEN AA.sido = '대구광역시' THEN '2700000000' 
            WHEN AA.sido = '대전광역시' THEN '3000000000' 
            WHEN AA.sido = '부산광역시' THEN '2600000000' 
            WHEN AA.sido = '서울특별시' THEN '1100000000' 
            WHEN AA.sido = '세종특별자치시' THEN '3600000000' 
            WHEN AA.sido = '울산광역시' THEN '3100000000' 
            WHEN AA.sido = '인천광역시' THEN '2800000000' 
            WHEN AA.sido = '전라남도' THEN '4600000000' 
            WHEN AA.sido = '전라북도' THEN '4500000000' 
            WHEN AA.sido = '제주특별자치도' THEN '5000000000' 
            WHEN AA.sido = '충청남도' THEN '4400000000' 
            WHEN AA.sido = '충청북도' THEN '4300000000' 
            ELSE '' END AS areaNo, AA.sido, COUNT( * ) AS cnt 
        FROM
        (
        	SELECT
        	    areaNo,
        		sido,
        		areaX,
        		areaY 
        	FROM
        		locationInfo 
        	WHERE
        		sigungu <> '' 
        		AND dong <> '' 
        	GROUP BY
        		areaX,
        		areaY 
        ) AA 
        GROUP BY
        	AA.sido 
        ORDER BY
        	AA.sido ASC 
";
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

if(count($locationArray) > 0) {
    for($i=0; $i<count($locationArray); $i++) {
        $_syncTitleName = $locationArray[$i]["sido"]." 동네예보(주간) 날씨 정보 동기화 ";                
        $_tSuccess = 0;
    	$_tFail = 0;
    	$_totalCount = (int)$locationArray[$i]["cnt"];
    	$sdate = date("Y-m-d H:i:s", time());
    	// 동기화 상태 초기화..
    	$syncArray[0]["status_code"] = "START";
    	$syncArray[0]["tCount"] = $_totalCount;
    	$syncArray[0]["tSuccess"] = $_tSuccess;
    	$syncArray[0]["tFail"] = $_tFail;
    	$syncArray[0]["errorMsg"] = $_syncTitleName."시작";
    	$syncArray[0]["sdate"] = $sdate;
    	$syncArray[0]["edate"] = $sdate;
    	$syncArray[0]["syncID"] = "KMA_WEATHER_WEEK_".$locationArray[$i]["areaNo"];
    	$_pdoObject->update_sync_monitoring_info($syncArray[0]);
        
        // $ch = curl_init();
        $url = 'http:///localhost/syncAPI/kma_weather_week_info.php';
        $queryParams  = '?sido=' . urlencode($locationArray[$i]["sido"]);
        $queryParams .= '&areaNo=' . $locationArray[$i]["areaNo"];
        
        $post_data=array('sido' => urlencode($locationArray[$i]["sido"]), 'areaNo' => $locationArray[$i]["areaNo"]);
        $_utilLibrary->curl_post_async($url, $post_data);
        /*
        curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if((int)$http_code == 200)
		{
			echo $url.$queryParams."성공<br>";
		}
		else
		{
			echo $url.$queryParams."실패<br>";
			$syncArray[0]["status_code"] = "FAIL";
            $syncArray[0]["tSuccess"] = 0;
            $syncArray[0]["tFail"] = $_totalCount;
            $syncArray[0]["errorMsg"] = $_syncTitleName."실패";
            $syncArray[0]["edate"] = date("Y-m-d H:i:s", time());
            $_pdoObject->update_sync_monitoring_info($syncArray[0]);
		}
		curl_close($ch);
		*/
    }
}
$_utilLibrary = null;
$_pdoObject = null;
?>
