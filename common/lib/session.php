<?php
class SysSession implements SessionHandlerInterface
{
    public $_DATABASE_NAME = "";
	public  $_connection = null;
    private $_HOST = "";
    private $_DB_USER = "";
    private $_DB_PASSWORD = "";
    
    function __construct($_HOST, $_DB_NAME, $_DB_USER, $_DB_PASSWORD)
	{
    	$this->_HOST = $_HOST;
    	$this->_DATABASE_NAME = $_DB_NAME;
    	$this->_DB_USER = $_DB_USER;
    	$this->_DB_PASSWORD = $_DB_PASSWORD;
	}

	function __destruct()
	{
		$this->_connection = null;
	}
    
    public function error($msg)
	{
		error_log(date('Y-m-d H:i:s')." : ".$msg, 3, $_SERVER["DOCUMENT_ROOT"]."/common/log/DBError.log");
	}
	
	/**
	* 테이블 체크
	* @param $TableName 테이블 명칭
	* @returnValue ture or false
	*/
	public function CheckTableInfo($TableName)
	{
		$Sql = "
			SELECT *
			FROM
				information_schema.tables
			WHERE
				table_schema = '".$this->_DATABASE_NAME."'
			AND table_name = :TableName
		";
		//echo $Sql;

		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":TableName", $TableName, PDO::PARAM_STR, 1000);

		$stmt->execute();
		$Array = $stmt->fetchAll(PDO::FETCH_ASSOC);

		//echo "[".count($Array)."]";

		if(count($Array) > 0)
		{
			return true;
		}

		return false;
	}
	
	/**
	* 테이블 정보
	* @param $_DATABASE_NAME 데이터베이스 명칭
	* @returnValue ture or false
	*/
	public function TableInfoArray($_DATABASE_NAME)
	{
		$Sql = "
			SELECT `TABLE_NAME` 
			FROM information_schema.tables
			WHERE
				table_schema = '".$_DATABASE_NAME."'
		";
		//echo $Sql;

		$stmt = $this->_connection->prepare($Sql);
		$stmt->execute();
		$Array = $stmt->fetchAll(PDO::FETCH_ASSOC);

		//echo "[".count($Array)."]";

		if(count($Array) > 0)
		{
			return $Array;
		}

		return null;
	}
	
    public function open($savePath, $sessionName)
    {
        try
		{
			$this->_connection = new PDO( "mysql:host=".$this->_HOST.";", $this->_DB_USER, $this->_DB_PASSWORD, array(PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

			// 에러 출력하지 않음
			//$_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			// Warning만 출력
			//$_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			// 에러 출력
			$this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			$_NUTRI_INFO_ARRAY = $this->TableInfoArray($this->_DATABASE_NAME);

			if(count($_NUTRI_INFO_ARRAY) <= 0)
			{
				$CreateDatabaseSql =" 	
					DROP DATABASE IF EXISTS ".$this->_DATABASE_NAME.";
					CREATE DATABASE ".$this->_DATABASE_NAME.";
					USE ".$this->_DATABASE_NAME.";
			    ";
				try
				{
					$this->_connection->exec($CreateDatabaseSql);
			    }
			    catch (Exception $e)
			    {
					$this->error("Create Database query error : [".$CreateDatabaseSql."] ".$e->getMessage()."\n");
				}
			}
			else
			{
				$CreateDatabaseSql =" 	
					USE ".$this->_DATABASE_NAME.";
			    ";
				try
				{
					$this->_connection->exec($CreateDatabaseSql);
			    }
			    catch (Exception $e)
			    {
					$this->error("Create Database query error : [".$CreateDatabaseSql."] ".$e->getMessage()."\n");
				}
			}
			
			/* --------------------------------------
			-- Table structure for nutri_session
			-- -------------------------------------- */
			if($this->CheckTableInfo("nutri_session") == false)
			{
				$CreateSql =" 	
			       CREATE TABLE `nutri_session` (
					  `session_Id` varchar(255) COLLATE utf8_general_ci NOT NULL,
					  `session_Expires` datetime NOT NULL,
					  `session_Data` text COLLATE utf8_general_ci,
					  PRIMARY KEY (`session_Id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
			    ";
				try
				{
					$this->_connection->exec($CreateSql);
			    }
			    catch (Exception $e)
			    {
					$this->error("Create nutri_session Table query error : [".$CreateSql."] ".$e->getMessage()."\n");
				}
			}
			return true;
		}
		catch (Exception $e )
		{
			$this->error("Connection a MySQL impossible : ".$e->getMessage()."\n");
			$this->_connection = null;
			return false;
		}
    }
    
    public function close()
    {
        $this->_connection = null;
        return true;
    }
    
    public function read($id)
    {
	    $expiresDate = date('Y-m-d H:i:s',time());
	    $Sql = "
			SELECT 
				session_Data 
			FROM 
				nutri_session 
			WHERE
				session_Id = :session_Id
			AND session_Expires > :session_Expires
		";
		//echo $Sql;

		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":session_Id", $id, PDO::PARAM_STR);
		$stmt->bindParam(":session_Expires", $expiresDate, PDO::PARAM_STR);
		$stmt->execute();
		$Array = $stmt->fetchAll(PDO::FETCH_ASSOC);

		//echo "[".count($Array)."]";

		if(count($Array) > 0)
		{
			return $Array[0]["session_Data"];
		}

		return "";
    }
    
    public function write($id, $data)
    {
	    $DateTime = date('Y-m-d H:i:s', time());
        $NewDateTime = date('Y-m-d H:i:s',strtotime($DateTime.' + 1 hour'));
        
	    try
		{
			$InsertSql = "
				REPLACE INTO nutri_session SET 
					  session_Id = :session_Id 
					, session_Expires = :session_Expires
			";
			if($data != "")
			{
				$InsertSql .= " , session_Data = :session_Data ";
			}

			$stmt = $this->_connection->prepare($InsertSql);
			$this->_connection->beginTransaction();
            $stmt->bindParam(":session_Id", $id, PDO::PARAM_STR);
			$stmt->bindParam(":session_Expires", $NewDateTime, PDO::PARAM_STR);
			if($data != "")
			{
				
				$stmt->bindParam(":session_Data", $data, PDO::PARAM_STR);
			}
			$stmt->execute();
			$this->_connection->commit();

			return true;
		}
		catch (Exception $e)
		{
			$this->_connection->rollback();
			$this->error("REPLACE INTO nutri_session query error : [".$InsertSql."] ".$e->getMessage()."\n");
			return false;
		}
    }
    
    public function destroy($id)
    {
	    try
		{
			$InsertSql = "
				DELETE FROM nutri_session WHERE session_Id = :session_Id
			";

			$stmt = $this->_connection->prepare($InsertSql);
			$this->_connection->beginTransaction();
            $stmt->bindParam(":session_Id", $id, PDO::PARAM_STR);
			$stmt->execute();
			$this->_connection->commit();

			return true;
		}
		catch (Exception $e)
		{
			$this->_connection->rollback();
			$this->error("DELETE FROM nutri_session query error : [".$InsertSql."] ".$e->getMessage()."\n");
			return false;
		}
    }
    
    public function gc($maxlifetime)
    {
	    try
		{
			$InsertSql = "
				DELETE FROM nutri_session WHERE ((UNIX_TIMESTAMP(session_Expires) + :session_Expires1) < :session_Expires2)
			";

			$stmt = $this->_connection->prepare($InsertSql);
			$this->_connection->beginTransaction();
            $stmt->bindParam(":session_Expires1", $maxlifetime, PDO::PARAM_INT);
            $stmt->bindParam(":session_Expires2", $maxlifetime, PDO::PARAM_INT);
			$stmt->execute();
			$this->_connection->commit();

			return true;
		}
		catch (Exception $e)
		{
			$this->_connection->rollback();
			$this->error("DELETE FROM nutri_session query error : [".$InsertSql."] ".$e->getMessage()."\n");
			return false;
		}
    }
}
$handler = new SysSession(_DB_HOST, _DB_NAME, _DB_USER, _DB_PASSWORD);
session_set_save_handler($handler, true);
?>