<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
header("Content-Type: text/json; charset=utf-8;");
// http://13.125.129.82/apiV1/getHospInfo.php?hpid=A2800449&type=clinic
$hpid = $_REQUEST["hpid"];
$type = $_REQUEST["type"];

$StatusCode = "0";
$listArray = array();
$dataRSArray = array();

$_utilLibrary = new utilLibrary();

if ($hpid != "" && $type != "")
{
	$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);
	$WhereSql = " WHERE status_code = '1' AND hpid = :hpid ";
	
	if($type == "emergency" || $type == "babyHosp" || $type == "clinic") {
    	$query_notice = "
            SELECT *
            FROM
                HOSP_RES_MST
            ".$WhereSql."
    	";
    	try
    	{
    		$stmt = $_pdoObject->_connection->prepare($query_notice);
    	    $stmt->bindParam(":hpid", $hpid, PDO::PARAM_STR);
    		$stmt->execute();
    		$dataRSArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
    	}
    	catch(Exception $e)
    	{
    		$_pdoObject->error("Select query error : [".$query_notice."] ".$e->getMessage()."\n");
    	}
    
        if(count($dataRSArray) > 0)
    	{   
    		$listArray = $dataRSArray[0];
    		if($type != "clinic") {
        		$tmpArray1 = $_pdoObject->get_hosp_max_var_info($hpid);
                $tmpArray2 = $_pdoObject->get_kiosk_max_hosp_info($hpid);
                $listArray["SUBLIST1"] = $tmpArray1[0];
                $listArray["SUBLIST2"] = $tmpArray2[0];
    		}
    	}
    	else
    	{
    		$StatusCode = "2";
    	}
	} else if($type == "pharmacy") {
        $query_notice = "
              SELECT *
              FROM
                PHARMACY_INFO
              ".$WhereSql."
    	";
    	try
    	{
    		$stmt = $_pdoObject->_connection->prepare($query_notice);
    	    $stmt->bindParam(":hpid", $hpid, PDO::PARAM_STR);
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
    		$listArray = $dataRSArray[0];
    	}
    	else
    	{
    		$StatusCode = "2";
    	}
    } else {
        $StatusCode = "1";
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
	, 'STATUSMSG'    => (string)$_utilLibrary->errorCheckReturnMsg($StatusCode)
	, 'LIST'         => $listArray
);

if(count($json_array_result) != 0) echo json_encode($json_array_result);
$_utilLibrary = null;
?>
