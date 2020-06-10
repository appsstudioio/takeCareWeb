<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
header("Content-Type: text/json; charset=utf-8;");
// https://appsstudio.site/apiV1/getHoliday.php?TODATE=2019-09-12&UUID=E6400132-1482-4962-849C-2F2CC8E684B7&VERSION=1.0.2
$TODATE     = $_REQUEST["TODATE"];
$UUID       = $_REQUEST["UUID"];
$PUSH_TOKEN = $_REQUEST["PUSH_TOKEN"];
$VERSION    = $_REQUEST["VERSION"];

if($PUSH_TOKEN == "") $PUSH_TOKEN = "TEST_TOKEN";
if($UUID == "") $UUID = "TEST_UUID";
if($TODATE == "") $TODATE = date("Y-m-d",time());

$StatusCode = "0";
$Holiday = array();
$_utilLibrary = new utilLibrary();

if ($PUSH_TOKEN != "" && $UUID != "" && $VERSION != "")
{
	$_pdoObject = new PDODatabase(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);

	// 사용자 정보 체크 
	$Sql = "
		SELECT *
		FROM
			USER_INFO
		WHERE
			UUID = :UUID
	";
	//echo $Sql;
	$stmt = $_pdoObject->_connection->prepare($Sql);
	$stmt->bindParam(":UUID", $UUID, PDO::PARAM_STR);
	$stmt->execute();

	$array = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if(count($array) > 0 )
	{
		// 로그인 시간 기록
		try
		{
			$UpdateSql = "
				UPDATE USER_INFO SET
				      `VERSION` = :VERSION
					, `LDATE`   = NOW()
				WHERE
					`UUID` =  :UUID
			";

			$stmt = $_pdoObject->_connection->prepare($UpdateSql);
			$_pdoObject->_connection->beginTransaction();
			$stmt->bindParam(":VERSION", $VERSION, PDO::PARAM_STR);
			$stmt->bindParam(":UUID",    $UUID, PDO::PARAM_STR);
			$stmt->execute();
			$_pdoObject->_connection->commit();
		}
		catch (Exception $e)
		{
			$_pdoObject->_connection->rollback();
			$_pdoObject->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
		}
	}
	else
	{
		// 기기등록
		try
		{
			$InsertSql = "
				INSERT INTO USER_INFO (`UUID`, `VERSION`, `LDATE`, `RDATE`)
				VALUES ( :UUID, :VERSION, NOW(), NOW())
			";

			$stmt = $_pdoObject->_connection->prepare($InsertSql);
			$_pdoObject->_connection->beginTransaction();
			$stmt->bindParam(":UUID",    $UUID, PDO::PARAM_STR);
			$stmt->bindParam(":VERSION", $VERSION, PDO::PARAM_STR);
			$stmt->execute();
			$_pdoObject->_connection->commit();
		}
		catch (Exception $e)
		{
			$_pdoObject->_connection->rollback();
			$_pdoObject->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
		}
	}
	// 방문로그
	try
	{
		$InsertSql = "
			INSERT INTO USER_VISIT_INFO (`UUID`, `RDATE`)
			VALUES ( :UUID, NOW())
		";

		$stmt = $_pdoObject->_connection->prepare($InsertSql);
		$_pdoObject->_connection->beginTransaction();
		$stmt->bindParam(":UUID",    $UUID, PDO::PARAM_STR);
		$stmt->execute();
		$_pdoObject->_connection->commit();
	}
	catch (Exception $e)
	{
		$_pdoObject->_connection->rollback();
		$_pdoObject->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
	}

	$Holiday = $_pdoObject->get_holiday_info($TODATE);
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
	, 'HOLI_INFO'    => $Holiday
);

if(count($json_array_result) != 0) echo json_encode($json_array_result);
$_utilLibrary = null;
?>