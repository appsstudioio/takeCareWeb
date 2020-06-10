<?php
/**
* PDO Database Connect
*/
class PDODatabase
{
    public $_DATABASE_NAME = "";
	public  $_connection = null;

	function __construct($_HOST, $_DB_NAME, $_DB_USER, $_DB_PASSWORD)
	{
    	$this->_DATABASE_NAME = $_DB_NAME;

		try
		{
			$this->_connection = new PDO( "mysql:host=".$_HOST.";dbname=".$_DB_NAME, $_DB_USER, $_DB_PASSWORD, array(PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

			// 에러 출력하지 않음
			//$_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			// Warning만 출력
			//$_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			// 에러 출력
			$this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (Exception $e )
		{
			echo $e->getMessage();
			$this->error("Connection a MySQL impossible : ".$e->getMessage()."\n");
			$this->_connection = null;
		}
	}

	function __destruct()
	{
		$this->_connection = null;
	}

	public function error($msg)
	{
		error_log(date('Y-m-d H:i:s')." : ".$msg, 3, $_SERVER["DOCUMENT_ROOT"]."/common/log/DBError.log");
	}

	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* TABLE CREATE FUNCTION */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */

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

	/**
	* 테이블 생성
	* @param $TableName 테이블 명칭
	* @returnValue ture or false
	*/
	public function CreateSyncMonitoringInfoTableFunc()
	{

		$CreateSql ="
            CREATE TABLE `".$this->_DATABASE_NAME."`.`sync_monitoring_info`  (
			  `syncID` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '동기화 아이디',
			  `status_code` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '동기화 상태',
			  `tCount` int(11) NULL DEFAULT 0 COMMENT '총 동기화 데이터 갯수',
			  `tSuccess` int(11) NULL DEFAULT 0 COMMENT '성공갯수',
			  `tFail` int(11) NULL DEFAULT 0 COMMENT '실패갯수',
			  `errorMsg` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '에러메세지',
			  `sdate` datetime NULL DEFAULT NULL COMMENT '동기화 시작 시간',
			  `edate` datetime NULL DEFAULT NULL COMMENT '동기화 끝난 시간',
			  `rdate` datetime NULL DEFAULT NULL COMMENT '등록일',
			  PRIMARY KEY (`syncID`) USING BTREE
			) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '동기화 상태 정보 테이블' ROW_FORMAT = Compact;
        ";
		try
		{
			$this->_connection->exec($CreateSql);
			return true;
	    }
	    catch (Exception $e)
	    {
			$this->error("Create Table query error : [".$CreateSql."] ".$e->getMessage()."\n");
			return false;
		}
	}

	/**
	* 테이블 생성
	* @param $TableName 테이블 명칭
	* @returnValue ture or false
	*/
	public function CreateSyncErrorInfoTableFunc()
	{

		$CreateSql ="
			CREATE TABLE `".$this->_DATABASE_NAME."`.`sync_error_info`  (
			  `Idx` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '인덱스',
			  `syncID` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '동기화 아이디',
			  `errorMsg` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '에러메세지',
			  `rdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '등록일시',
			  PRIMARY KEY (`syncID`, `rdate`) USING BTREE
			) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '동기화 에러 정보' ROW_FORMAT = Compact;
        ";
		try
		{
			$this->_connection->exec($CreateSql);
			return true;
	    }
	    catch (Exception $e)
	    {
			$this->error("Create Table query error : [".$CreateSql."] ".$e->getMessage()."\n");
			return false;
		}
	}

	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* TABLE INSERT FUNCTION */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */

	/**
	* 동기화 아이디 생성
	* @param $syncID 동기화 아이디

	* @returnValue ture or false
	*/
	public function insert_sync_monitoring_info($syncID)
	{
		try
		{
			$InsertSql = "
				INSERT INTO sync_monitoring_info
				(
                       `syncID`
                     , `status_code`
                     , `rdate`
				)
				VALUES
				(
    				  :syncID
    				, 'REG'
    				, NOW()
				)
			";

			$stmt = $this->_connection->prepare($InsertSql);
			$this->_connection->beginTransaction();

            $stmt->bindParam(":syncID", $syncID, PDO::PARAM_STR);

			$stmt->execute();
			$this->_connection->commit();

			return true;
		}
		catch (Exception $e)
		{
			$this->_connection->rollback();
			$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
			return false;
		}
	}

	/**
	* 동기화 에러로그 처리
	* @param $syncID 동기화 아이디
	* @param $errorMsg 에러메시지
	* @returnValue ture or false
	*/
	public function insert_sync_error_info($syncID, $errorMsg)
	{
		list($microtime,$timestamp) = explode(' ',microtime());
		$time = $timestamp.substr($microtime, 2, 3);
		$ns_idx = (string)$time;

		try
		{
			$InsertSql = "
				INSERT INTO sync_error_info
				(
					   `Idx`
                     , `syncID`
                     , `errorMsg`
                     , `rdate`
				)
				VALUES
				(
					  :Idx
    				, :syncID
    				, :errorMsg
    				, NOW()
				)
			";

			$stmt = $this->_connection->prepare($InsertSql);
			$this->_connection->beginTransaction();

            $stmt->bindParam(":Idx", $ns_idx, PDO::PARAM_STR);
            $stmt->bindParam(":syncID", $syncID, PDO::PARAM_STR);
			$stmt->bindParam(":errorMsg", $errorMsg, PDO::PARAM_STR);

			$stmt->execute();
			$this->_connection->commit();

			return true;
		}
		catch (Exception $e)
		{
			$this->_connection->rollback();
			$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
			return false;
		}
	}

	/**
	* 코드마스터 정보 추가
	* @param $code 코드
	* @returnValue ture or false
	*/
	public function insert_CodeMast($data)
	{
		try
		{
			$InsertSql = "
				INSERT INTO CodeMast
				(
					  `cmMid`
					, `cmMnm`
					, `cmSid`
					, `cmSnm`
					, `rdate`
				)
				VALUES
				(
    				  :cmMid
    				, :cmMnm
    				, :cmSid
    				, :cmSnm
    				, NOW()
				)
			";

			$stmt = $this->_connection->prepare($InsertSql);
			$this->_connection->beginTransaction();
			$stmt->bindParam(":cmMid", $data->cmMid, PDO::PARAM_STR);
			$stmt->bindParam(":cmMnm", $data->cmMnm, PDO::PARAM_STR);
			$stmt->bindParam(":cmSid", $data->cmSid, PDO::PARAM_STR);
			$stmt->bindParam(":cmSnm", $data->cmSnm, PDO::PARAM_STR);
			$stmt->execute();
			$this->_connection->commit();

			return true;
		}
		catch (Exception $e)
		{
			$this->_connection->rollback();
			$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
			return false;
		}
	}

	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* TABLE UPDATE FUNCTION */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */

	/**
	* 동기화 로그 처리..
	* @param $syncArray 동기화 정보
	* @param $PTYPE     SITE : 관리자 사이트, APP : 모바일
	* @returnValue ture or false
	*/
	public function update_sync_monitoring_info($syncArray)
	{
		$Sql = "
			SELECT
				COUNT(*) AS CNT
			FROM
				sync_monitoring_info
			WHERE
				`syncID` = :syncID
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":syncID", $syncArray["syncID"], PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if((int)$array[0]["CNT"] > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE sync_monitoring_info SET
						  `status_code` = :status_code
						, `tCount`      = :tCount
						, `tSuccess`    = :tSuccess
						, `tFail`       = :tFail
						, `errorMsg`    = :errorMsg
						, `sdate`       = STR_TO_DATE(:sdate,'%Y-%m-%d %H:%i:%s')
						, `edate`       = STR_TO_DATE(:edate,'%Y-%m-%d %H:%i:%s')
					WHERE
						`syncID` = :syncID
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":status_code", $syncArray["status_code"], PDO::PARAM_STR);
	            $stmt->bindParam(":tCount",      $syncArray["tCount"], PDO::PARAM_INT);
	            $stmt->bindParam(":tSuccess",    $syncArray["tSuccess"], PDO::PARAM_INT);
	            $stmt->bindParam(":tFail",       $syncArray["tFail"], PDO::PARAM_INT);
	            $stmt->bindParam(":errorMsg",    $syncArray["errorMsg"], PDO::PARAM_STR);
	            $stmt->bindParam(":sdate",       $syncArray["sdate"], PDO::PARAM_STR);
	            $stmt->bindParam(":edate",       $syncArray["edate"], PDO::PARAM_STR);

	            $stmt->bindParam(":syncID", $syncArray["syncID"], PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO sync_monitoring_info
					(
						  `syncID`
						, `status_code`
						, `tCount`
						, `tSuccess`
						, `tFail`
						, `errorMsg`
						, `sdate`
						, `edate`
						, `rdate`
					)
					VALUES
					(
						  :syncID
						, :status_code
						, :tCount
						, :tSuccess
						, :tFail
						, :errorMsg
						, STR_TO_DATE(:sdate,'%Y-%m-%d %H:%i:%s')
						, STR_TO_DATE(:edate,'%Y-%m-%d %H:%i:%s')
						, NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":syncID", $syncArray["syncID"], PDO::PARAM_STR);
				$stmt->bindParam(":status_code", $syncArray["status_code"], PDO::PARAM_STR);
				$stmt->bindParam(":tCount", $syncArray["tCount"], PDO::PARAM_INT);
				$stmt->bindParam(":tSuccess", $syncArray["tSuccess"], PDO::PARAM_INT);
				$stmt->bindParam(":tFail", $syncArray["tFail"], PDO::PARAM_INT);
				$stmt->bindParam(":errorMsg", $syncArray["errorMsg"], PDO::PARAM_STR);
				$stmt->bindParam(":sdate", $syncArray["sdate"], PDO::PARAM_STR);
				$stmt->bindParam(":edate", $syncArray["edate"], PDO::PARAM_STR);

				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}

	/**
	* 실시간 병상정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_hosp_max_var_info($data)
	{
		$hvidate = date("Y-m-d H:i:s", strtotime($data->hvidate));

		$Sql = "
			SELECT
				COUNT(*) AS CNT
			FROM
				HOSP_MAX_VAR_INFO
			WHERE
				`hpid` = :hpid
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if((int)$array[0]["CNT"] > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE HOSP_MAX_VAR_INFO SET
						  `phpid`		= :phpid
						, `hvidate`		= STR_TO_DATE(:hvidate,'%Y-%m-%d %H:%i:%s')
						, `hvec`		= :hvec
						, `hvoc`		= :hvoc
						, `hvcc`		= :hvcc
						, `hvncc`		= :hvncc
						, `hvccc`		= :hvccc
						, `hvicc`		= :hvicc
						, `hvgc`		= :hvgc
						, `hvdnm`		= :hvdnm
						, `hvctayn`	 	= :hvctayn
						, `hvmriayn`	= :hvmriayn
						, `hvangioayn` 	= :hvangioayn
						, `hvventiayn` 	= :hvventiayn
						, `hvamyn`		= :hvamyn
						, `hv1`			= :hv1
						, `hv2`			= :hv2
						, `hv3`			= :hv3
						, `hv4`			= :hv4
						, `hv5`			= :hv5
						, `hv6`			= :hv6
						, `hv7`			= :hv7
						, `hv8`			= :hv8
						, `hv9`			= :hv9
						, `hv10`		= :hv10
						, `hv11`		= :hv11
						, `hv12`		= :hv12
						, `dutyName`	= :dutyName
						, `dutyTel3`	= :dutyTel3
					WHERE
						`hpid` = :hpid
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":phpid"	  , $data->phpid , PDO::PARAM_STR);
				$stmt->bindParam(":hvidate"	  , $hvidate , PDO::PARAM_STR);
				$stmt->bindParam(":hvec"	  , $data->hvec , PDO::PARAM_STR);
				$stmt->bindParam(":hvoc"	  , $data->hvoc , PDO::PARAM_STR);
				$stmt->bindParam(":hvcc"	  , $data->hvcc , PDO::PARAM_STR);
				$stmt->bindParam(":hvncc"	  , $data->hvncc , PDO::PARAM_STR);
				$stmt->bindParam(":hvccc"	  , $data->hvccc , PDO::PARAM_STR);
				$stmt->bindParam(":hvicc"	  , $data->hvicc , PDO::PARAM_STR);
				$stmt->bindParam(":hvgc"	  , $data->hvgc , PDO::PARAM_STR);
				$stmt->bindParam(":hvdnm"	  , $data->hvdnm , PDO::PARAM_STR);
				$stmt->bindParam(":hvctayn"	  , $data->hvctayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvmriayn"  , $data->hvmriayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvangioayn", $data->hvangioayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvventiayn", $data->hvventiayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvamyn"	  , $data->hvamyn , PDO::PARAM_STR);
				$stmt->bindParam(":hv1"		  , $data->hv1 , PDO::PARAM_STR);
				$stmt->bindParam(":hv2"		  , $data->hv2 , PDO::PARAM_STR);
				$stmt->bindParam(":hv3"		  , $data->hv3 , PDO::PARAM_STR);
				$stmt->bindParam(":hv4"		  , $data->hv4 , PDO::PARAM_STR);
				$stmt->bindParam(":hv5"		  , $data->hv5 , PDO::PARAM_STR);
				$stmt->bindParam(":hv6"		  , $data->hv6 , PDO::PARAM_STR);
				$stmt->bindParam(":hv7"		  , $data->hv7 , PDO::PARAM_STR);
				$stmt->bindParam(":hv8"		  , $data->hv8 , PDO::PARAM_STR);
				$stmt->bindParam(":hv9"		  , $data->hv9 , PDO::PARAM_STR);
				$stmt->bindParam(":hv10"	  , $data->hv10 , PDO::PARAM_STR);
				$stmt->bindParam(":hv11"	  , $data->hv11 , PDO::PARAM_STR);
				$stmt->bindParam(":hv12"	  , $data->hv12 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyName"  , $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3"  , $data->dutyTel3 , PDO::PARAM_STR);

	      $stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO HOSP_MAX_VAR_INFO
					(
						  `hpid`
						, `phpid`
						, `hvidate`
						, `hvec`
						, `hvoc`
						, `hvcc`
						, `hvncc`
						, `hvccc`
						, `hvicc`
						, `hvgc`
						, `hvdnm`
						, `hvctayn`
						, `hvmriayn`
						, `hvangioayn`
						, `hvventiayn`
						, `hvamyn`
						, `hv1`
						, `hv2`
						, `hv3`
						, `hv4`
						, `hv5`
						, `hv6`
						, `hv7`
						, `hv8`
						, `hv9`
						, `hv10`
						, `hv11`
						, `hv12`
						, `dutyName`
						, `dutyTel3`
					)
					VALUES
					(
						  :hpid
						, :phpid
						, STR_TO_DATE(:hvidate,'%Y-%m-%d %H:%i:%s')
						, :hvec
						, :hvoc
						, :hvcc
						, :hvncc
						, :hvccc
						, :hvicc
						, :hvgc
						, :hvdnm
						, :hvctayn
						, :hvmriayn
						, :hvangioayn
						, :hvventiayn
						, :hvamyn
						, :hv1
						, :hv2
						, :hv3
						, :hv4
						, :hv5
						, :hv6
						, :hv7
						, :hv8
						, :hv9
						, :hv10
						, :hv11
						, :hv12
						, :dutyName
						, :dutyTel3
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":hpid"      , $data->hpid, PDO::PARAM_STR);
				$stmt->bindParam(":phpid"	  , $data->phpid , PDO::PARAM_STR);
				$stmt->bindParam(":hvidate"	  , $hvidate , PDO::PARAM_STR);
				$stmt->bindParam(":hvec"	  , $data->hvec , PDO::PARAM_STR);
				$stmt->bindParam(":hvoc"	  , $data->hvoc , PDO::PARAM_STR);
				$stmt->bindParam(":hvcc"	  , $data->hvcc , PDO::PARAM_STR);
				$stmt->bindParam(":hvncc"	  , $data->hvncc , PDO::PARAM_STR);
				$stmt->bindParam(":hvccc"	  , $data->hvccc , PDO::PARAM_STR);
				$stmt->bindParam(":hvicc"	  , $data->hvicc , PDO::PARAM_STR);
				$stmt->bindParam(":hvgc"	  , $data->hvgc , PDO::PARAM_STR);
				$stmt->bindParam(":hvdnm"	  , $data->hvdnm , PDO::PARAM_STR);
				$stmt->bindParam(":hvctayn"	  , $data->hvctayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvmriayn"  , $data->hvmriayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvangioayn", $data->hvangioayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvventiayn", $data->hvventiayn , PDO::PARAM_STR);
				$stmt->bindParam(":hvamyn"	  , $data->hvamyn , PDO::PARAM_STR);
				$stmt->bindParam(":hv1"		  , $data->hv1 , PDO::PARAM_STR);
				$stmt->bindParam(":hv2"		  , $data->hv2 , PDO::PARAM_STR);
				$stmt->bindParam(":hv3"		  , $data->hv3 , PDO::PARAM_STR);
				$stmt->bindParam(":hv4"		  , $data->hv4 , PDO::PARAM_STR);
				$stmt->bindParam(":hv5"		  , $data->hv5 , PDO::PARAM_STR);
				$stmt->bindParam(":hv6"		  , $data->hv6 , PDO::PARAM_STR);
				$stmt->bindParam(":hv7"		  , $data->hv7 , PDO::PARAM_STR);
				$stmt->bindParam(":hv8"		  , $data->hv8 , PDO::PARAM_STR);
				$stmt->bindParam(":hv9"		  , $data->hv9 , PDO::PARAM_STR);
				$stmt->bindParam(":hv10"	  , $data->hv10 , PDO::PARAM_STR);
				$stmt->bindParam(":hv11"	  , $data->hv11 , PDO::PARAM_STR);
				$stmt->bindParam(":hv12"	  , $data->hv12 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyName"  , $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3"  , $data->dutyTel3 , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}

	/**
	* 중증질환 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_kiosk_max_hosp_info($data)
	{
		$Sql = "
			SELECT
				COUNT(*) AS CNT
			FROM
				KIOSK_MAX_HOSP_INFO
			WHERE
				`hpid` = :hpid
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if((int)$array[0]["CNT"] > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE KIOSK_MAX_HOSP_INFO SET
						  `dutyName`   = :dutyName
						, `MKioskTy25` = :MKioskTy25
						, `MKioskTy1`  = :MKioskTy1
						, `MKioskTy2`  = :MKioskTy2
						, `MKioskTy3`  = :MKioskTy3
						, `MKioskTy4`  = :MKioskTy4
						, `MKioskTy5`  = :MKioskTy5
						, `MKioskTy6`  = :MKioskTy6
						, `MKioskTy7`  = :MKioskTy7
						, `MKioskTy8`  = :MKioskTy8
						, `MKioskTy9`  = :MKioskTy9
						, `MKioskTy10` = :MKioskTy10
						, `MKioskTy11` = :MKioskTy11
						, `rdate`	   = NOW()
					WHERE
						`hpid` = :hpid
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":dutyName" , $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy25" , $data->MKioskTy25 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy1" , $data->MKioskTy1 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy2" , $data->MKioskTy2 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy3" , $data->MKioskTy3 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy4" , $data->MKioskTy4 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy5" , $data->MKioskTy5 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy6" , $data->MKioskTy6 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy7" , $data->MKioskTy7 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy8" , $data->MKioskTy8 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy9" , $data->MKioskTy9 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy10", $data->MKioskTy10 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy11", $data->MKioskTy11 , PDO::PARAM_STR);
	            $stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO KIOSK_MAX_HOSP_INFO
					(
						  `hpid`
						, `dutyName`
						, `MKioskTy25`
						, `MKioskTy1`
						, `MKioskTy2`
						, `MKioskTy3`
						, `MKioskTy4`
						, `MKioskTy5`
						, `MKioskTy6`
						, `MKioskTy7`
						, `MKioskTy8`
						, `MKioskTy9`
						, `MKioskTy10`
						, `MKioskTy11`
						, `rdate`
					)
					VALUES
					(
						  :hpid
						, :dutyName
						, :MKioskTy25
						, :MKioskTy1
						, :MKioskTy2
						, :MKioskTy3
						, :MKioskTy4
						, :MKioskTy5
						, :MKioskTy6
						, :MKioskTy7
						, :MKioskTy8
						, :MKioskTy9
						, :MKioskTy10
						, :MKioskTy11
						, NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
				$stmt->bindParam(":dutyName" , $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy25" , $data->MKioskTy25 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy1" , $data->MKioskTy1 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy2" , $data->MKioskTy2 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy3" , $data->MKioskTy3 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy4" , $data->MKioskTy4 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy5" , $data->MKioskTy5 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy6" , $data->MKioskTy6 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy7" , $data->MKioskTy7 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy8" , $data->MKioskTy8 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy9" , $data->MKioskTy9 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy10", $data->MKioskTy10 , PDO::PARAM_STR);
				$stmt->bindParam(":MKioskTy11", $data->MKioskTy11 , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}

	/**
	* 병의원 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_hosp_res_mst($data)
	{
		$wgs84Lon = $data->wgs84Lon;
		if($data->wgs84Lon == "") $wgs84Lon = "0.0";
		$wgs84Lat = $data->wgs84Lat;
		if($data->wgs84Lat == "") $wgs84Lat = "0.0";

		$Sql = "
			SELECT
				* 
			FROM
				HOSP_RES_MST
			WHERE
				`hpid` = :hpid
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE HOSP_RES_MST SET
						  `dutyName`   = :dutyName
						, `postCdn1`   = :postCdn1
						, `postCdn2`   = :postCdn2
						, `dutyAddr`   = :dutyAddr
						, `dutyTel1`   = :dutyTel1
						, `dutyTel3`   = :dutyTel3
						, `dutyHayn`   = :dutyHayn
						, `dutyHano`   = :dutyHano
						, `dutyInf`	   = :dutyInf
						, `dutyMapimg` = :dutyMapimg
						, `dutyEryn`   = :dutyEryn
						, `dutyTime1s` = :dutyTime1s
						, `dutyTime2s` = :dutyTime2s
						, `dutyTime3s` = :dutyTime3s
						, `dutyTime4s` = :dutyTime4s
						, `dutyTime5s` = :dutyTime5s
						, `dutyTime6s` = :dutyTime6s
						, `dutyTime7s` = :dutyTime7s
						, `dutyTime8s` = :dutyTime8s
						, `dutyTime1c` = :dutyTime1c
						, `dutyTime2c` = :dutyTime2c
						, `dutyTime3c` = :dutyTime3c
						, `dutyTime4c` = :dutyTime4c
						, `dutyTime5c` = :dutyTime5c
						, `dutyTime6c` = :dutyTime6c
						, `dutyTime7c` = :dutyTime7c
						, `dutyTime8c` = :dutyTime8c
						, `wgs84Lon`   = :wgs84Lon
						, `wgs84Lat`   = :wgs84Lat
						, `dgidIdName` = :dgidIdName
						, `hpbdn`	   = :hpbdn
						, `hpccuyn`	   = :hpccuyn
						, `hpcuyn`	   = :hpcuyn
						, `hperyn`	   = :hperyn
						, `hpgryn`	   = :hpgryn
						, `hpicuyn`	   = :hpicuyn
						, `hpnicuyn`   = :hpnicuyn
						, `hpopyn`	   = :hpopyn
						, `rdate`	   = NOW()
					WHERE
						`hIdx` = :hIdx
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3", $data->dutyTel3 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyHayn", $data->dutyHayn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyHano", $data->dutyHano , PDO::PARAM_STR);
				$stmt->bindParam(":dutyInf", $data->dutyInf , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEryn", $data->dutyEryn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
				$stmt->bindParam(":dgidIdName", $data->dgidIdName , PDO::PARAM_STR);
				$stmt->bindParam(":hpbdn", $data->hpbdn , PDO::PARAM_STR);
				$stmt->bindParam(":hpccuyn", $data->hpccuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpcuyn", $data->hpcuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hperyn", $data->hperyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpgryn", $data->hpgryn , PDO::PARAM_STR);
				$stmt->bindParam(":hpicuyn", $data->hpicuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpnicuyn", $data->hpnicuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpopyn", $data->hpopyn , PDO::PARAM_STR);
	            $stmt->bindParam(":hIdx", $array[0]["hIdx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO HOSP_RES_MST
					(
						  `hpid`
						, `dutyName`
						, `postCdn1`
						, `postCdn2`
						, `dutyAddr`
						, `dutyTel1`
						, `dutyTel3`
						, `dutyHayn`
						, `dutyHano`
						, `dutyInf`
						, `dutyMapimg`
						, `dutyEryn`
						, `dutyTime1s`
						, `dutyTime2s`
						, `dutyTime3s`
						, `dutyTime4s`
						, `dutyTime5s`
						, `dutyTime6s`
						, `dutyTime7s`
						, `dutyTime8s`
						, `dutyTime1c`
						, `dutyTime2c`
						, `dutyTime3c`
						, `dutyTime4c`
						, `dutyTime5c`
						, `dutyTime6c`
						, `dutyTime7c`
						, `dutyTime8c`
						, `wgs84Lon`
						, `wgs84Lat`
						, `dgidIdName`
						, `hpbdn`
						, `hpccuyn`
						, `hpcuyn`
						, `hperyn`
						, `hpgryn`
						, `hpicuyn`
						, `hpnicuyn`
						, `hpopyn`
						, `rdate`
					)
					VALUES
					(
						  :hpid
						, :dutyName
						, :postCdn1
						, :postCdn2
						, :dutyAddr
						, :dutyTel1
						, :dutyTel3
						, :dutyHayn
						, :dutyHano
						, :dutyInf
						, :dutyMapimg
						, :dutyEryn
						, :dutyTime1s
						, :dutyTime2s
						, :dutyTime3s
						, :dutyTime4s
						, :dutyTime5s
						, :dutyTime6s
						, :dutyTime7s
						, :dutyTime8s
						, :dutyTime1c
						, :dutyTime2c
						, :dutyTime3c
						, :dutyTime4c
						, :dutyTime5c
						, :dutyTime6c
						, :dutyTime7c
						, :dutyTime8c
						, :wgs84Lon
						, :wgs84Lat
						, :dgidIdName
						, :hpbdn
						, :hpccuyn
						, :hpcuyn
						, :hperyn
						, :hpgryn
						, :hpicuyn
						, :hpnicuyn
						, :hpopyn
						, NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3", $data->dutyTel3 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyHayn", $data->dutyHayn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyHano", $data->dutyHano , PDO::PARAM_STR);
				$stmt->bindParam(":dutyInf", $data->dutyInf , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEryn", $data->dutyEryn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
				$stmt->bindParam(":dgidIdName", $data->dgidIdName , PDO::PARAM_STR);
				$stmt->bindParam(":hpbdn", $data->hpbdn , PDO::PARAM_STR);
				$stmt->bindParam(":hpccuyn", $data->hpccuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpcuyn", $data->hpcuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hperyn", $data->hperyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpgryn", $data->hpgryn , PDO::PARAM_STR);
				$stmt->bindParam(":hpicuyn", $data->hpicuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpnicuyn", $data->hpnicuyn , PDO::PARAM_STR);
				$stmt->bindParam(":hpopyn", $data->hpopyn , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}

	/**
	* 병의원 FULL DATA 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_hosp_res_mst_full_data($data)
	{
		$wgs84Lon = $data->wgs84Lon;
		if($data->wgs84Lon == "") $wgs84Lon = "0.0";
		$wgs84Lat = $data->wgs84Lat;
		if($data->wgs84Lat == "") $wgs84Lat = "0.0";

		$Sql = "
			SELECT
				* 
			FROM
				HOSP_RES_MST
			WHERE
				`hpid` = :hpid
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE HOSP_RES_MST SET
						  `dutyName`   = :dutyName
						, `postCdn1`   = :postCdn1
						, `postCdn2`   = :postCdn2
						, `dutyAddr`   = :dutyAddr
						, `dutyDiv`    = :dutyDiv
						, `dutyDivNam` = :dutyDivNam
						, `dutyEmcls`  = :dutyEmcls
						, `dutyEmclsName` = :dutyEmclsName
						, `dutyTel1`   = :dutyTel1
						, `dutyTel3`   = :dutyTel3
						, `dutyEtc`    = :dutyEtc
						, `dutyInf`	   = :dutyInf
						, `dutyMapimg` = :dutyMapimg
						, `dutyEryn`   = :dutyEryn
						, `dutyTime1s` = :dutyTime1s
						, `dutyTime2s` = :dutyTime2s
						, `dutyTime3s` = :dutyTime3s
						, `dutyTime4s` = :dutyTime4s
						, `dutyTime5s` = :dutyTime5s
						, `dutyTime6s` = :dutyTime6s
						, `dutyTime7s` = :dutyTime7s
						, `dutyTime8s` = :dutyTime8s
						, `dutyTime1c` = :dutyTime1c
						, `dutyTime2c` = :dutyTime2c
						, `dutyTime3c` = :dutyTime3c
						, `dutyTime4c` = :dutyTime4c
						, `dutyTime5c` = :dutyTime5c
						, `dutyTime6c` = :dutyTime6c
						, `dutyTime7c` = :dutyTime7c
						, `dutyTime8c` = :dutyTime8c
						, `wgs84Lon`   = :wgs84Lon
						, `wgs84Lat`   = :wgs84Lat
						, `rdate`	   = NOW()
					WHERE
						`hIdx` = :hIdx
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDiv", $data->dutyDiv , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDivNam", $data->dutyDivNam , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmcls", $data->dutyEmcls , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmclsName", $data->dutyEmclsName , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3", $data->dutyTel3 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEtc", $data->dutyEtc , PDO::PARAM_STR);
				$stmt->bindParam(":dutyInf", $data->dutyInf , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEryn", $data->dutyEryn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
	            $stmt->bindParam(":hIdx", $array[0]["hIdx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO HOSP_RES_MST
					(
						  `hpid`
						, `dutyName`
						, `postCdn1`
						, `postCdn2`
						, `dutyAddr`
						, `dutyDiv`
						, `dutyDivNam`
						, `dutyEmcls`
						, `dutyEmclsName`
						, `dutyTel1`
						, `dutyTel3`
						, `dutyEtc`
						, `dutyInf`
						, `dutyMapimg`
						, `dutyEryn`
						, `dutyTime1s`
						, `dutyTime2s`
						, `dutyTime3s`
						, `dutyTime4s`
						, `dutyTime5s`
						, `dutyTime6s`
						, `dutyTime7s`
						, `dutyTime8s`
						, `dutyTime1c`
						, `dutyTime2c`
						, `dutyTime3c`
						, `dutyTime4c`
						, `dutyTime5c`
						, `dutyTime6c`
						, `dutyTime7c`
						, `dutyTime8c`
						, `wgs84Lon`
						, `wgs84Lat`
						, `rdate`
					)
					VALUES
					(
						  :hpid
						, :dutyName
						, :postCdn1
						, :postCdn2
						, :dutyAddr
						, :dutyDiv
						, :dutyDivNam
						, :dutyEmcls
						, :dutyEmclsName
						, :dutyTel1
						, :dutyTel3
						, :dutyEtc
						, :dutyInf
						, :dutyMapimg
						, :dutyEryn
						, :dutyTime1s
						, :dutyTime2s
						, :dutyTime3s
						, :dutyTime4s
						, :dutyTime5s
						, :dutyTime6s
						, :dutyTime7s
						, :dutyTime8s
						, :dutyTime1c
						, :dutyTime2c
						, :dutyTime3c
						, :dutyTime4c
						, :dutyTime5c
						, :dutyTime6c
						, :dutyTime7c
						, :dutyTime8c
						, :wgs84Lon
						, :wgs84Lat
						, NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDiv", $data->dutyDiv , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDivNam", $data->dutyDivNam , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmcls", $data->dutyEmcls , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmclsName", $data->dutyEmclsName , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3", $data->dutyTel3 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEtc", $data->dutyEtc , PDO::PARAM_STR);
				$stmt->bindParam(":dutyInf", $data->dutyInf , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEryn", $data->dutyEryn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}

	/**
	* 달빛어린이병원 및 소아전문센터 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_hosp_res_mst_baby_info($data)
	{
		$wgs84Lon = $data->wgs84Lon;
		if($data->wgs84Lon == "") $wgs84Lon = "0.0";
		$wgs84Lat = $data->wgs84Lat;
		if($data->wgs84Lat == "") $wgs84Lat = "0.0";

		$Sql = "
			SELECT
				*
			FROM
				HOSP_RES_MST
			WHERE
				`hpid` = :hpid
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE HOSP_RES_MST SET
						  `rdate`	   = NOW()
						, `baby_flag`  = 'Y'
					WHERE
						`hIdx` = :hIdx
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				/*
				// 달빛어린이집 목록에서 정보가 일부 빠지거나 제대로 나오지 않음. 상태만 업데이트함.
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDiv", $data->dutyDiv , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDivNam", $data->dutyDivNam , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmcls", $data->dutyEmcls , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmclsName", $data->dutyEmclsName , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3", $data->dutyTel3 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEtc", $data->dutyEtc , PDO::PARAM_STR);
				$stmt->bindParam(":dutyInf", $data->dutyInf , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEryn", $data->dutyEryn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
				*/
	            $stmt->bindParam(":hIdx", $array[0]["hIdx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO HOSP_RES_MST
					(
						  `hpid`
						, `dutyName`
						, `postCdn1`
						, `postCdn2`
						, `dutyAddr`
						, `dutyDiv`
						, `dutyDivNam`
						, `dutyEmcls`
						, `dutyEmclsName`
						, `dutyTel1`
						, `dutyTel3`
						, `dutyEtc`
						, `dutyInf`
						, `dutyMapimg`
						, `dutyEryn`
						, `dutyTime1s`
						, `dutyTime2s`
						, `dutyTime3s`
						, `dutyTime4s`
						, `dutyTime5s`
						, `dutyTime6s`
						, `dutyTime7s`
						, `dutyTime8s`
						, `dutyTime1c`
						, `dutyTime2c`
						, `dutyTime3c`
						, `dutyTime4c`
						, `dutyTime5c`
						, `dutyTime6c`
						, `dutyTime7c`
						, `dutyTime8c`
						, `wgs84Lon`
						, `wgs84Lat`
						, `rdate`
						, `baby_flag`
					)
					VALUES
					(
						  :hpid
						, :dutyName
						, :postCdn1
						, :postCdn2
						, :dutyAddr
						, :dutyDiv
						, :dutyDivNam
						, :dutyEmcls
						, :dutyEmclsName
						, :dutyTel1
						, :dutyTel3
						, :dutyEtc
						, :dutyInf
						, :dutyMapimg
						, :dutyEryn
						, :dutyTime1s
						, :dutyTime2s
						, :dutyTime3s
						, :dutyTime4s
						, :dutyTime5s
						, :dutyTime6s
						, :dutyTime7s
						, :dutyTime8s
						, :dutyTime1c
						, :dutyTime2c
						, :dutyTime3c
						, :dutyTime4c
						, :dutyTime5c
						, :dutyTime6c
						, :dutyTime7c
						, :dutyTime8c
						, :wgs84Lon
						, :wgs84Lat
						, NOW()
						, 'Y'
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDiv", $data->dutyDiv , PDO::PARAM_STR);
				$stmt->bindParam(":dutyDivNam", $data->dutyDivNam , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmcls", $data->dutyEmcls , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEmclsName", $data->dutyEmclsName , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel3", $data->dutyTel3 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEtc", $data->dutyEtc , PDO::PARAM_STR);
				$stmt->bindParam(":dutyInf", $data->dutyInf , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyEryn", $data->dutyEryn , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}

	/**
	* 약국 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_pharmacy_info($data)
	{
		$wgs84Lon = $data->wgs84Lon;
		if($data->wgs84Lon == "") $wgs84Lon = "0.0";
		$wgs84Lat = $data->wgs84Lat;
		if($data->wgs84Lat == "") $wgs84Lat = "0.0";

		$Sql = "
			SELECT
				* 
			FROM
				PHARMACY_INFO
			WHERE
				`hpid` = :hpid
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE PHARMACY_INFO SET
						  `dutyName`   = :dutyName
						, `postCdn1`   = :postCdn1
						, `postCdn2`   = :postCdn2
						, `dutyAddr`   = :dutyAddr
						, `dutyTel1`   = :dutyTel1
						, `dutyMapimg` = :dutyMapimg
						, `dutyTime1s` = :dutyTime1s
						, `dutyTime2s` = :dutyTime2s
						, `dutyTime3s` = :dutyTime3s
						, `dutyTime4s` = :dutyTime4s
						, `dutyTime5s` = :dutyTime5s
						, `dutyTime6s` = :dutyTime6s
						, `dutyTime7s` = :dutyTime7s
						, `dutyTime8s` = :dutyTime8s
						, `dutyTime1c` = :dutyTime1c
						, `dutyTime2c` = :dutyTime2c
						, `dutyTime3c` = :dutyTime3c
						, `dutyTime4c` = :dutyTime4c
						, `dutyTime5c` = :dutyTime5c
						, `dutyTime6c` = :dutyTime6c
						, `dutyTime7c` = :dutyTime7c
						, `dutyTime8c` = :dutyTime8c
						, `wgs84Lon`   = :wgs84Lon
						, `wgs84Lat`   = :wgs84Lat
						, `rdate`	   = NOW()
					WHERE
						`pIdx` = :pIdx
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
	            $stmt->bindParam(":pIdx", $array[0]["pIdx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO PHARMACY_INFO
					(
						  `hpid`
						, `dutyName`
						, `postCdn1`
						, `postCdn2`
						, `dutyAddr`
						, `dutyTel1`
						, `dutyMapimg`
						, `dutyTime1s`
						, `dutyTime2s`
						, `dutyTime3s`
						, `dutyTime4s`
						, `dutyTime5s`
						, `dutyTime6s`
						, `dutyTime7s`
						, `dutyTime8s`
						, `dutyTime1c`
						, `dutyTime2c`
						, `dutyTime3c`
						, `dutyTime4c`
						, `dutyTime5c`
						, `dutyTime6c`
						, `dutyTime7c`
						, `dutyTime8c`
						, `wgs84Lon`
						, `wgs84Lat`
						, `rdate`
					)
					VALUES
					(
						  :hpid
						, :dutyName
						, :postCdn1
						, :postCdn2
						, :dutyAddr
						, :dutyTel1
						, :dutyMapimg
						, :dutyTime1s
						, :dutyTime2s
						, :dutyTime3s
						, :dutyTime4s
						, :dutyTime5s
						, :dutyTime6s
						, :dutyTime7s
						, :dutyTime8s
						, :dutyTime1c
						, :dutyTime2c
						, :dutyTime3c
						, :dutyTime4c
						, :dutyTime5c
						, :dutyTime6c
						, :dutyTime7c
						, :dutyTime8c
						, :wgs84Lon
						, :wgs84Lat
						, NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":hpid", $data->hpid, PDO::PARAM_STR);
				$stmt->bindParam(":dutyName", $data->dutyName , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn1", $data->postCdn1 , PDO::PARAM_STR);
				$stmt->bindParam(":postCdn2", $data->postCdn2 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyAddr", $data->dutyAddr , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTel1", $data->dutyTel1 , PDO::PARAM_STR);
				$stmt->bindParam(":dutyMapimg", $data->dutyMapimg , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1s", $data->dutyTime1s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2s", $data->dutyTime2s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3s", $data->dutyTime3s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4s", $data->dutyTime4s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5s", $data->dutyTime5s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6s", $data->dutyTime6s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7s", $data->dutyTime7s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8s", $data->dutyTime8s , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime1c", $data->dutyTime1c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime2c", $data->dutyTime2c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime3c", $data->dutyTime3c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime4c", $data->dutyTime4c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime5c", $data->dutyTime5c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime6c", $data->dutyTime6c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime7c", $data->dutyTime7c , PDO::PARAM_STR);
				$stmt->bindParam(":dutyTime8c", $data->dutyTime8c , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lon", $wgs84Lon , PDO::PARAM_STR);
				$stmt->bindParam(":wgs84Lat", $wgs84Lat , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}

	/**
	* 휴일 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_holiday_info($locdate, $isHoliday, $dateName)
	{
		$Sql = "
			SELECT
				* 
			FROM
				HOLIDAY_INFO
			WHERE
				`locdate`  = :locdate
			AND `dateName` = :dateName
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":locdate",  $locdate, PDO::PARAM_STR);
		$stmt->bindParam(":dateName", $dateName, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) == 0 )
		{
			try
			{
				$InsertSql = "
					INSERT INTO HOLIDAY_INFO
					(
						  `locdate`
						, `dateKind`
						, `dateName`
						, `rdate`
					)
					VALUES
					(
						  :locdate
						, :dateKind
						, :dateName
						, NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":locdate",  $locdate, PDO::PARAM_STR);
				if(urldecode($dateName) == "설날" || urldecode($dateName) == "추석")
				{
    				$isHoliday = "S";
    			}
    			$stmt->bindParam(":dateKind", $isHoliday , PDO::PARAM_STR);
				$stmt->bindParam(":dateName", $dateName , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		} else {
			return true;
		}
	}
	
	/**
	* 기상청 생활지수 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_kma_api_info($data)
	{
		$Sql = "
			SELECT
				* 
			FROM
				KMA_API_INFO
			WHERE
				`adate`  = :adate
			AND `areaNo` = :areaNo
			AND `code`   = :code
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":adate",    $data["adate"], PDO::PARAM_STR);
		$stmt->bindParam(":areaNo",   $data["areaNo"], PDO::PARAM_STR);
		$stmt->bindParam(":code",     $data["code"], PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) == 0 )
		{
			try
			{
				$InsertSql = "
					INSERT INTO KMA_API_INFO
					(
						  `adate`
                        , `areaNo`
                        , `code`
                        , `date`
                        , `v1`
                        , `v2`
                        , `v3`
                        , `v4`
                        , `v5`
                        , `v6`
                        , `v7`
                        , `v8`
                        , `v9`
                        , `v10`
                        , `v11`
                        , `v12`
                        , `v13`
                        , `v14`
                        , `v15`
                        , `v16`
					)
					VALUES
					(
						  :adate
                        , :areaNo
                        , :code
                        , :date
                        , :v1
                        , :v2
                        , :v3
                        , :v4
                        , :v5
                        , :v6
                        , :v7
                        , :v8
                        , :v9
                        , :v10
                        , :v11
                        , :v12
                        , :v13
                        , :v14
                        , :v15
                        , :v16
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();				
				$stmt->bindParam(":adate", $data["adate"], PDO::PARAM_STR);
                $stmt->bindParam(":areaNo", $data["areaNo"], PDO::PARAM_STR);
                $stmt->bindParam(":code", $data["code"], PDO::PARAM_STR);
                $stmt->bindParam(":date", $data["date"], PDO::PARAM_STR);
                $stmt->bindParam(":v1", $data["v1"], PDO::PARAM_STR);
                $stmt->bindParam(":v2", $data["v2"], PDO::PARAM_STR);
                $stmt->bindParam(":v3", $data["v3"], PDO::PARAM_STR);
                $stmt->bindParam(":v4", $data["v4"], PDO::PARAM_STR);
                $stmt->bindParam(":v5", $data["v5"], PDO::PARAM_STR);
                $stmt->bindParam(":v6", $data["v6"], PDO::PARAM_STR);
                $stmt->bindParam(":v7", $data["v7"], PDO::PARAM_STR);
                $stmt->bindParam(":v8", $data["v8"], PDO::PARAM_STR);
                $stmt->bindParam(":v9", $data["v9"], PDO::PARAM_STR);
                $stmt->bindParam(":v10", $data["v10"], PDO::PARAM_STR);
                $stmt->bindParam(":v11", $data["v11"], PDO::PARAM_STR);
                $stmt->bindParam(":v12", $data["v12"], PDO::PARAM_STR);
                $stmt->bindParam(":v13", $data["v13"], PDO::PARAM_STR);
                $stmt->bindParam(":v14", $data["v14"], PDO::PARAM_STR);
                $stmt->bindParam(":v15", $data["v15"], PDO::PARAM_STR);
                $stmt->bindParam(":v16", $data["v16"], PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		} else {
			try
			{
				$UpdateSql = "
					UPDATE KMA_API_INFO SET
						  `date` = :date 
				";
				if($data["v1"] != "") $UpdateSql .= " , `v1` = :v1 ";
				if($data["v2"] != "") $UpdateSql .= " , `v2` = :v2 ";
				if($data["v3"] != "") $UpdateSql .= " , `v3` = :v3 ";
				if($data["v4"] != "") $UpdateSql .= " , `v4` = :v4 ";
				if($data["v5"] != "") $UpdateSql .= " , `v5` = :v5 ";
				if($data["v6"] != "") $UpdateSql .= " , `v6` = :v6 ";
				if($data["v7"] != "") $UpdateSql .= " , `v7` = :v7 ";
				if($data["v8"] != "") $UpdateSql .= " , `v8` = :v8 ";
				if($data["v9"] != "") $UpdateSql .= " , `v9` = :v9 ";
				if($data["v10"] != "") $UpdateSql .= " , `v10` = :v10 ";
				if($data["v11"] != "") $UpdateSql .= " , `v11` = :v11 ";
				if($data["v12"] != "") $UpdateSql .= " , `v12` = :v12 ";
				if($data["v13"] != "") $UpdateSql .= " , `v13` = :v13 ";
				if($data["v14"] != "") $UpdateSql .= " , `v14` = :v14 ";
				if($data["v15"] != "") $UpdateSql .= " , `v15` = :v15 ";
				if($data["v16"] != "") $UpdateSql .= " , `v16` = :v16 ";
                $UpdateSql .= " WHERE `idx` = :idx ";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":date", $data["date"], PDO::PARAM_STR);
                if($data["v1"] != "")   $stmt->bindParam(":v1", $data["v1"], PDO::PARAM_STR);
                if($data["v2"] != "")   $stmt->bindParam(":v2", $data["v2"], PDO::PARAM_STR);
                if($data["v3"] != "")   $stmt->bindParam(":v3", $data["v3"], PDO::PARAM_STR);
                if($data["v4"] != "")   $stmt->bindParam(":v4", $data["v4"], PDO::PARAM_STR);
                if($data["v5"] != "")   $stmt->bindParam(":v5", $data["v5"], PDO::PARAM_STR);
                if($data["v6"] != "")   $stmt->bindParam(":v6", $data["v6"], PDO::PARAM_STR);
                if($data["v7"] != "")   $stmt->bindParam(":v7", $data["v7"], PDO::PARAM_STR);
                if($data["v8"] != "")   $stmt->bindParam(":v8", $data["v8"], PDO::PARAM_STR);
                if($data["v9"] != "")   $stmt->bindParam(":v9", $data["v9"], PDO::PARAM_STR);
                if($data["v10"] != "")  $stmt->bindParam(":v10", $data["v10"], PDO::PARAM_STR);
                if($data["v11"] != "")  $stmt->bindParam(":v11", $data["v11"], PDO::PARAM_STR);
                if($data["v12"] != "")  $stmt->bindParam(":v12", $data["v12"], PDO::PARAM_STR);
                if($data["v13"] != "")  $stmt->bindParam(":v13", $data["v13"], PDO::PARAM_STR);
                if($data["v14"] != "")  $stmt->bindParam(":v14", $data["v14"], PDO::PARAM_STR);
                if($data["v15"] != "")  $stmt->bindParam(":v15", $data["v15"], PDO::PARAM_STR);
                if($data["v16"] != "")  $stmt->bindParam(":v16", $data["v16"], PDO::PARAM_STR);
	            $stmt->bindParam(":idx", $array[0]["idx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}
	
	/**
	* 기상청 주간 날씨 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_kma_weather_week_info($data)
	{
		$Sql = "
			SELECT
				* 
			FROM
				KMA_WEATHER_WEEK_INFO
			WHERE
				`adate` = :adate
            AND `basedate` = :basedate
			AND `nx` = :nx
			AND `ny` = :ny
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":adate",    $data["adate"], PDO::PARAM_STR);
		$stmt->bindParam(":basedate", $data["basedate"], PDO::PARAM_STR);
		$stmt->bindParam(":nx",       $data["nx"], PDO::PARAM_STR);
		$stmt->bindParam(":ny",       $data["ny"], PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) == 0 )
		{
			try
			{
				$InsertSql = "
					INSERT INTO KMA_WEATHER_WEEK_INFO 
					(
						  `adate`
                        , `basedate`
                        , `nx`
                        , `ny`
                        , `POP` 
                        , `PTY`
                        , `R06` 
                        , `REH` 
                        , `S06` 
                        , `SKY` 
                        , `T3H` 
                        , `TMN` 
                        , `TMX` 
					)
					VALUES
					(
						  :adate
                        , :basedate
                        , :nx
                        , :ny
                        , :POP 
                        , :PTY 
                        , :R06 
                        , :REH 
                        , :S06 
                        , :SKY 
                        , :T3H 
                        , :TMN 
                        , :TMX 
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();				
				$stmt->bindParam(":adate", $data["adate"], PDO::PARAM_STR);
                $stmt->bindParam(":basedate", $data["basedate"], PDO::PARAM_STR);
                $stmt->bindParam(":nx", $data["nx"], PDO::PARAM_STR);
                $stmt->bindParam(":ny", $data["ny"], PDO::PARAM_STR);
                $stmt->bindParam(":POP", $data["POP"], PDO::PARAM_STR);
                $stmt->bindParam(":PTY", $data["PTY"], PDO::PARAM_STR);
                $stmt->bindParam(":R06", $data["R06"], PDO::PARAM_STR);
                $stmt->bindParam(":REH", $data["REH"], PDO::PARAM_STR);
                $stmt->bindParam(":S06", $data["S06"], PDO::PARAM_STR);
                $stmt->bindParam(":SKY", $data["SKY"], PDO::PARAM_STR);
                $stmt->bindParam(":T3H", $data["T3H"], PDO::PARAM_STR);
                $stmt->bindParam(":TMN", $data["TMN"], PDO::PARAM_STR);
                $stmt->bindParam(":TMX", $data["TMX"], PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		} else {
			try
			{
				$UpdateSql = "
					UPDATE KMA_WEATHER_WEEK_INFO SET
						  `adate` = :adate 
				";
				if($data["POP"] != "") $UpdateSql .= " , `POP` = :POP ";
				if($data["PTY"] != "") $UpdateSql .= " , `PTY` = :PTY ";
				if($data["R06"] != "") $UpdateSql .= " , `R06` = :R06 ";
				if($data["REH"] != "") $UpdateSql .= " , `REH` = :REH ";
				if($data["S06"] != "") $UpdateSql .= " , `S06` = :S06 ";
				if($data["SKY"] != "") $UpdateSql .= " , `SKY` = :SKY ";
				if($data["T3H"] != "") $UpdateSql .= " , `T3H` = :T3H ";
				if($data["TMN"] != "") $UpdateSql .= " , `TMN` = :TMN ";
				if($data["TMX"] != "") $UpdateSql .= " , `TMX` = :TMX ";
                $UpdateSql .= " WHERE `idx` = :idx ";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":adate", $data["adate"], PDO::PARAM_STR);
                if($data["POP"] != "")  $stmt->bindParam(":POP", $data["POP"], PDO::PARAM_STR);
                if($data["PTY"] != "")  $stmt->bindParam(":PTY", $data["PTY"], PDO::PARAM_STR);
                if($data["R06"] != "")  $stmt->bindParam(":R06", $data["R06"], PDO::PARAM_STR);
                if($data["REH"] != "")  $stmt->bindParam(":REH", $data["REH"], PDO::PARAM_STR);
                if($data["S06"] != "")  $stmt->bindParam(":S06", $data["S06"], PDO::PARAM_STR);
                if($data["SKY"] != "")  $stmt->bindParam(":SKY", $data["SKY"], PDO::PARAM_STR);
                if($data["T3H"] != "")  $stmt->bindParam(":T3H", $data["T3H"], PDO::PARAM_STR);
                if($data["TMN"] != "")  $stmt->bindParam(":TMN", $data["TMN"], PDO::PARAM_STR);
                if($data["TMX"] != "")  $stmt->bindParam(":TMX", $data["TMX"], PDO::PARAM_STR);
                
	            $stmt->bindParam(":idx", $array[0]["idx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}
	
	/**
	* 기상청 당일 날씨 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_kma_weather_day_info($data)
	{
		$Sql = "
			SELECT
				* 
			FROM
				KMA_WEATHER_DAY_INFO
			WHERE
				`adate` = :adate
            AND `basedate` = :basedate
			AND `nx` = :nx
			AND `ny` = :ny
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":adate",    $data["adate"], PDO::PARAM_STR);
		$stmt->bindParam(":basedate", $data["basedate"], PDO::PARAM_STR);
		$stmt->bindParam(":nx",       $data["nx"], PDO::PARAM_STR);
		$stmt->bindParam(":ny",       $data["ny"], PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) == 0 )
		{
			try
			{
				$InsertSql = "
					INSERT INTO KMA_WEATHER_DAY_INFO 
					(
						  `adate`
                        , `basedate`
                        , `nx`
                        , `ny`
                        , `RN1`
                        , `PTY`
                        , `UUU`
                        , `VVV`
                        , `REH`
                        , `SKY`
                        , `T1H`
                        , `VEC`
                        , `WSD`
                        , `LGT`
					)
					VALUES
					(
						  :adate
                        , :basedate
                        , :nx
                        , :ny
                        , :RN1
                        , :PTY
                        , :UUU
                        , :VVV
                        , :REH
                        , :SKY
                        , :T1H
                        , :VEC
                        , :WSD
                        , :LGT
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();				
				$stmt->bindParam(":adate", $data["adate"], PDO::PARAM_STR);
                $stmt->bindParam(":basedate", $data["basedate"], PDO::PARAM_STR);
                $stmt->bindParam(":nx", $data["nx"], PDO::PARAM_STR);
                $stmt->bindParam(":ny", $data["ny"], PDO::PARAM_STR);
                $stmt->bindParam(":RN1", $data["RN1"], PDO::PARAM_STR);
                $stmt->bindParam(":PTY", $data["PTY"], PDO::PARAM_STR);
                $stmt->bindParam(":UUU", $data["UUU"], PDO::PARAM_STR);
                $stmt->bindParam(":VVV", $data["VVV"], PDO::PARAM_STR);
                $stmt->bindParam(":REH", $data["REH"], PDO::PARAM_STR);
                $stmt->bindParam(":SKY", $data["SKY"], PDO::PARAM_STR);
                $stmt->bindParam(":T1H", $data["T1H"], PDO::PARAM_STR);
                $stmt->bindParam(":VEC", $data["VEC"], PDO::PARAM_STR);
                $stmt->bindParam(":WSD", $data["WSD"], PDO::PARAM_STR);
                $stmt->bindParam(":LGT", $data["LGT"], PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		} else {
			try
			{
				$UpdateSql = "
					UPDATE KMA_WEATHER_DAY_INFO SET
						  `adate` = :adate 
				";

				if($data["RN1"] != "") $UpdateSql .= " , `RN1` = :RN1 ";
				if($data["PTY"] != "") $UpdateSql .= " , `PTY` = :PTY ";
				if($data["UUU"] != "") $UpdateSql .= " , `UUU` = :UUU ";
				if($data["VVV"] != "") $UpdateSql .= " , `VVV` = :VVV ";
				if($data["REH"] != "") $UpdateSql .= " , `REH` = :REH ";
				if($data["SKY"] != "") $UpdateSql .= " , `SKY` = :SKY ";
				if($data["T1H"] != "") $UpdateSql .= " , `T1H` = :T1H ";
				if($data["VEC"] != "") $UpdateSql .= " , `VEC` = :VEC ";
				if($data["WSD"] != "") $UpdateSql .= " , `WSD` = :WSD ";
				if($data["LGT"] != "") $UpdateSql .= " , `LGT` = :LGT ";
                $UpdateSql .= " WHERE `idx` = :idx ";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":adate", $data["adate"], PDO::PARAM_STR);
                if($data["RN1"] != "")  $stmt->bindParam(":RN1", $data["RN1"], PDO::PARAM_STR);
                if($data["PTY"] != "")  $stmt->bindParam(":PTY", $data["PTY"], PDO::PARAM_STR);
                if($data["UUU"] != "")  $stmt->bindParam(":UUU", $data["UUU"], PDO::PARAM_STR);
                if($data["VVV"] != "")  $stmt->bindParam(":VVV", $data["VVV"], PDO::PARAM_STR);
                if($data["REH"] != "")  $stmt->bindParam(":REH", $data["REH"], PDO::PARAM_STR);
                if($data["SKY"] != "")  $stmt->bindParam(":SKY", $data["SKY"], PDO::PARAM_STR);
                if($data["T1H"] != "")  $stmt->bindParam(":T1H", $data["T1H"], PDO::PARAM_STR);
                if($data["VEC"] != "")  $stmt->bindParam(":VEC", $data["VEC"], PDO::PARAM_STR);
                if($data["WSD"] != "")  $stmt->bindParam(":WSD", $data["WSD"], PDO::PARAM_STR);
                if($data["LGT"] != "")  $stmt->bindParam(":LGT", $data["LGT"], PDO::PARAM_STR);
                
	            $stmt->bindParam(":idx", $array[0]["idx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}
	
	/**
	* 기상청 당일 실황 날씨 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_kma_weather_new_day_info($data)
	{
		$Sql = "
			SELECT
				* 
			FROM
				KMA_WEATHER_NEW_DAY_INFO
			WHERE
				`adate` = :adate
            AND `basedate` = :basedate
			AND `nx` = :nx
			AND `ny` = :ny
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":adate",    $data["adate"], PDO::PARAM_STR);
		$stmt->bindParam(":basedate", $data["basedate"], PDO::PARAM_STR);
		$stmt->bindParam(":nx",       $data["nx"], PDO::PARAM_STR);
		$stmt->bindParam(":ny",       $data["ny"], PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) == 0 )
		{
			try
			{
				$InsertSql = "
					INSERT INTO KMA_WEATHER_NEW_DAY_INFO 
					(
						  `adate`
                        , `basedate`
                        , `nx`
                        , `ny`
                        , `RN1`
                        , `PTY`
                        , `UUU`
                        , `VVV`
                        , `REH`
                        , `SKY`
                        , `T1H`
                        , `VEC`
                        , `WSD`
                        , `LGT`
					)
					VALUES
					(
						  :adate
                        , :basedate
                        , :nx
                        , :ny
                        , :RN1
                        , :PTY
                        , :UUU
                        , :VVV
                        , :REH
                        , :SKY
                        , :T1H
                        , :VEC
                        , :WSD
                        , :LGT
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();				
				$stmt->bindParam(":adate", $data["adate"], PDO::PARAM_STR);
                $stmt->bindParam(":basedate", $data["basedate"], PDO::PARAM_STR);
                $stmt->bindParam(":nx", $data["nx"], PDO::PARAM_STR);
                $stmt->bindParam(":ny", $data["ny"], PDO::PARAM_STR);
                $stmt->bindParam(":RN1", $data["RN1"], PDO::PARAM_STR);
                $stmt->bindParam(":PTY", $data["PTY"], PDO::PARAM_STR);
                $stmt->bindParam(":UUU", $data["UUU"], PDO::PARAM_STR);
                $stmt->bindParam(":VVV", $data["VVV"], PDO::PARAM_STR);
                $stmt->bindParam(":REH", $data["REH"], PDO::PARAM_STR);
                $stmt->bindParam(":SKY", $data["SKY"], PDO::PARAM_STR);
                $stmt->bindParam(":T1H", $data["T1H"], PDO::PARAM_STR);
                $stmt->bindParam(":VEC", $data["VEC"], PDO::PARAM_STR);
                $stmt->bindParam(":WSD", $data["WSD"], PDO::PARAM_STR);
                $stmt->bindParam(":LGT", $data["LGT"], PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		} else {
			try
			{
				$UpdateSql = "
					UPDATE KMA_WEATHER_NEW_DAY_INFO SET
						  `adate` = :adate 
				";

				if($data["RN1"] != "") $UpdateSql .= " , `RN1` = :RN1 ";
				if($data["PTY"] != "") $UpdateSql .= " , `PTY` = :PTY ";
				if($data["UUU"] != "") $UpdateSql .= " , `UUU` = :UUU ";
				if($data["VVV"] != "") $UpdateSql .= " , `VVV` = :VVV ";
				if($data["REH"] != "") $UpdateSql .= " , `REH` = :REH ";
				if($data["SKY"] != "") $UpdateSql .= " , `SKY` = :SKY ";
				if($data["T1H"] != "") $UpdateSql .= " , `T1H` = :T1H ";
				if($data["VEC"] != "") $UpdateSql .= " , `VEC` = :VEC ";
				if($data["WSD"] != "") $UpdateSql .= " , `WSD` = :WSD ";
				if($data["LGT"] != "") $UpdateSql .= " , `LGT` = :LGT ";
                $UpdateSql .= " WHERE `idx` = :idx ";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":adate", $data["adate"], PDO::PARAM_STR);
                if($data["RN1"] != "")  $stmt->bindParam(":RN1", $data["RN1"], PDO::PARAM_STR);
                if($data["PTY"] != "")  $stmt->bindParam(":PTY", $data["PTY"], PDO::PARAM_STR);
                if($data["UUU"] != "")  $stmt->bindParam(":UUU", $data["UUU"], PDO::PARAM_STR);
                if($data["VVV"] != "")  $stmt->bindParam(":VVV", $data["VVV"], PDO::PARAM_STR);
                if($data["REH"] != "")  $stmt->bindParam(":REH", $data["REH"], PDO::PARAM_STR);
                if($data["SKY"] != "")  $stmt->bindParam(":SKY", $data["SKY"], PDO::PARAM_STR);
                if($data["T1H"] != "")  $stmt->bindParam(":T1H", $data["T1H"], PDO::PARAM_STR);
                if($data["VEC"] != "")  $stmt->bindParam(":VEC", $data["VEC"], PDO::PARAM_STR);
                if($data["WSD"] != "")  $stmt->bindParam(":WSD", $data["WSD"], PDO::PARAM_STR);
                if($data["LGT"] != "")  $stmt->bindParam(":LGT", $data["LGT"], PDO::PARAM_STR);
                
	            $stmt->bindParam(":idx", $array[0]["idx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}
	
	/**
	* 미세먼지 측정소 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_airkorea_station_info($data)
	{
    	$dmX = $data->dmX;
		$dmY = $data->dmY;
		
		$Sql = "
			SELECT
				* 
			FROM
				AIRKOREA_STATION_INFO
			WHERE
				`stationName` = :stationName
        ";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":stationName", $data->stationName, PDO::PARAM_STR);
		$stmt->execute();
		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE AIRKOREA_STATION_INFO SET
                          `addr` = :addr 
                        , `oper` = :oper
                        , `year` = :year 
                        , `photo` = :photo 
                        , `vrml` = :vrml 
                        , `map` = :map 
                        , `mangName` = :mangName 
                        , `item` = :item 
                ";
                if($data->dmX != "") $UpdateSql .= " , `dmX` = :dmX ";
                if($data->dmY != "") $UpdateSql .= " , `dmY` = :dmY ";
				$UpdateSql .= " 
				        , `udate` = NOW()
					WHERE
						`idx` = :idx
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":addr", $data->addr , PDO::PARAM_STR);
				$stmt->bindParam(":oper", $data->oper , PDO::PARAM_STR);
				$stmt->bindParam(":year", $data->year , PDO::PARAM_STR);
				$stmt->bindParam(":photo", $data->photo , PDO::PARAM_STR);
				$stmt->bindParam(":vrml", $data->vrml , PDO::PARAM_STR);
				$stmt->bindParam(":map", $data->map , PDO::PARAM_STR);
				$stmt->bindParam(":mangName", $data->mangName , PDO::PARAM_STR);
				$stmt->bindParam(":item", $data->item , PDO::PARAM_STR);
				if($data->dmX != "") $stmt->bindParam(":dmX", $dmX , PDO::PARAM_STR);
				if($data->dmY != "") $stmt->bindParam(":dmY", $dmY , PDO::PARAM_STR);
				
				$stmt->bindParam(":idx", $array[0]["idx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO AIRKOREA_STATION_INFO
					(
						  `stationName`
                        , `addr` 
                        , `oper`
                        , `year`
                        , `photo`
                        , `vrml`
                        , `map`
                        , `mangName`
                        , `item`
                        , `dmX`
                        , `dmY`
                        , `udate`
					)
					VALUES
					(
						  :stationName
                        , :addr 
                        , :oper 
                        , :year
                        , :photo
                        , :vrml
                        , :map
                        , :mangName
                        , :item
                        , :dmX
                        , :dmY
                        , NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":stationName", $data->stationName, PDO::PARAM_STR);
				$stmt->bindParam(":addr", $data->addr , PDO::PARAM_STR);
				$stmt->bindParam(":oper", $data->oper , PDO::PARAM_STR);
				$stmt->bindParam(":year", $data->year , PDO::PARAM_STR);
				$stmt->bindParam(":photo", $data->photo , PDO::PARAM_STR);
				$stmt->bindParam(":vrml", $data->vrml , PDO::PARAM_STR);
				$stmt->bindParam(":map", $data->map , PDO::PARAM_STR);
				$stmt->bindParam(":mangName", $data->mangName , PDO::PARAM_STR);
				$stmt->bindParam(":item", $data->item , PDO::PARAM_STR);
				$stmt->bindParam(":dmX", $dmX , PDO::PARAM_STR);
				$stmt->bindParam(":dmY", $dmY , PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}
	
	/**
	* 미세먼지 측정 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_airkorea_api_data_info($data)
	{
    	$dataTime = date('Y-m-d H:i', strtotime($data->dataTime));
    	
		$Sql = "
			SELECT
				* 
			FROM
				AIRKOREA_API_DATA_INFO
			WHERE
				`stationName` = :stationName
            AND `dataTime`    = STR_TO_DATE(:dataTime,'%Y-%m-%d %H:%i')
        ";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":stationName", $data->stationName, PDO::PARAM_STR);
		$stmt->bindParam(":dataTime",    $dataTime, PDO::PARAM_STR);
		$stmt->execute();
		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) > 0 )
		{
			try
			{
				$UpdateSql = "
					UPDATE AIRKOREA_API_DATA_INFO SET
                          `mangName`    = :mangName
                        , `dataTime`    = STR_TO_DATE(:dataTime,'%Y-%m-%d %H:%i')
                        , `so2Value`    = :so2Value
                        , `coValue`     = :coValue
                        , `o3Value`     = :o3Value
                        , `no2Value`    = :no2Value
                        , `pm10Value`   = :pm10Value
                        , `pm10Value24` = :pm10Value24
                        , `pm25Value`   = :pm25Value
                        , `pm25Value24` = :pm25Value24
                        , `khaiValue`   = :khaiValue
                        , `khaiGrade`   = :khaiGrade
                        , `so2Grade`    = :so2Grade
                        , `coGrade`     = :coGrade
                        , `o3Grade`     = :o3Grade
                        , `no2Grade`    = :no2Grade
                        , `pm10Grade`   = :pm10Grade
                        , `pm25Grade`   = :pm25Grade
                        , `pm10Grade1h` = :pm10Grade1h
                        , `pm25Grade1h` = :pm25Grade1h
				        , `udate`       = NOW()
					WHERE
						`idx` = :idx
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":mangName", $data->mangName, PDO::PARAM_STR);
                $stmt->bindParam(":dataTime", $dataTime, PDO::PARAM_STR);
                $stmt->bindParam(":so2Value", $data->so2Value, PDO::PARAM_STR); 
                $stmt->bindParam(":coValue", $data->coValue, PDO::PARAM_STR); 
                $stmt->bindParam(":o3Value", $data->o3Value, PDO::PARAM_STR); 
                $stmt->bindParam(":no2Value", $data->no2Value, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Value", $data->pm10Value, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Value24", $data->pm10Value24, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Value", $data->pm25Value, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Value24", $data->pm25Value24, PDO::PARAM_STR);
                $stmt->bindParam(":khaiValue", $data->khaiValue, PDO::PARAM_STR);
                $stmt->bindParam(":khaiGrade", $data->khaiGrade, PDO::PARAM_STR); 
                $stmt->bindParam(":so2Grade", $data->so2Grade, PDO::PARAM_STR); 
                $stmt->bindParam(":coGrade", $data->coGrade, PDO::PARAM_STR); 
                $stmt->bindParam(":o3Grade", $data->o3Grade, PDO::PARAM_STR); 
                $stmt->bindParam(":no2Grade", $data->no2Grade, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Grade", $data->pm10Grade, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Grade", $data->pm25Grade, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Grade1h", $data->pm10Grade1h, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Grade1h", $data->pm25Grade1h, PDO::PARAM_STR);
				
				$stmt->bindParam(":idx", $array[0]["idx"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
		else
		{
			try
			{
				$InsertSql = "
					INSERT INTO AIRKOREA_API_DATA_INFO
					(
                          `stationName`
                        , `mangName`
                        , `dataTime` 
                        , `so2Value` 
                        , `coValue` 
                        , `o3Value` 
                        , `no2Value`
                        , `pm10Value`
                        , `pm10Value24`
                        , `pm25Value`
                        , `pm25Value24`
                        , `khaiValue` 
                        , `khaiGrade` 
                        , `so2Grade` 
                        , `coGrade` 
                        , `o3Grade` 
                        , `no2Grade`
                        , `pm10Grade`
                        , `pm25Grade`
                        , `pm10Grade1h`
                        , `pm25Grade1h`
                        , `udate`
					)
					VALUES
					(
                          :stationName
                        , :mangName
                        , STR_TO_DATE(:dataTime,'%Y-%m-%d %H:%i')
                        , :so2Value 
                        , :coValue 
                        , :o3Value 
                        , :no2Value
                        , :pm10Value
                        , :pm10Value24
                        , :pm25Value
                        , :pm25Value24
                        , :khaiValue 
                        , :khaiGrade 
                        , :so2Grade 
                        , :coGrade 
                        , :o3Grade 
                        , :no2Grade
                        , :pm10Grade
                        , :pm25Grade
                        , :pm10Grade1h
                        , :pm25Grade1h 
                        , NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();
                $stmt->bindParam(":stationName", $data->stationName, PDO::PARAM_STR);
                $stmt->bindParam(":mangName", $data->mangName, PDO::PARAM_STR);
                $stmt->bindParam(":dataTime", $dataTime, PDO::PARAM_STR);
                $stmt->bindParam(":so2Value", $data->so2Value, PDO::PARAM_STR); 
                $stmt->bindParam(":coValue", $data->coValue, PDO::PARAM_STR); 
                $stmt->bindParam(":o3Value", $data->o3Value, PDO::PARAM_STR); 
                $stmt->bindParam(":no2Value", $data->no2Value, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Value", $data->pm10Value, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Value24", $data->pm10Value24, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Value", $data->pm25Value, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Value24", $data->pm25Value24, PDO::PARAM_STR);
                $stmt->bindParam(":khaiValue", $data->khaiValue, PDO::PARAM_STR);
                $stmt->bindParam(":khaiGrade", $data->khaiGrade, PDO::PARAM_STR); 
                $stmt->bindParam(":so2Grade", $data->so2Grade, PDO::PARAM_STR); 
                $stmt->bindParam(":coGrade", $data->coGrade, PDO::PARAM_STR); 
                $stmt->bindParam(":o3Grade", $data->o3Grade, PDO::PARAM_STR); 
                $stmt->bindParam(":no2Grade", $data->no2Grade, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Grade", $data->pm10Grade, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Grade", $data->pm25Grade, PDO::PARAM_STR);
                $stmt->bindParam(":pm10Grade1h", $data->pm10Grade1h, PDO::PARAM_STR);
                $stmt->bindParam(":pm25Grade1h", $data->pm25Grade1h, PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}
	
	/**
	* 국가예방접종 정보 업데이트.
	* @returnValue ture or false
	*/
	public function update_vcn_schedule_info($VCNCD, $TITLE, $MESSAGE)
	{
		$Sql = "
			SELECT
				* 
			FROM
				SCHEDULE_INFO
			WHERE
				`VCNCD` = :VCNCD
			AND `TYPE` = 'VCN'
			";

		//echo $Sql;
		$stmt = $this->_connection->prepare($Sql);
		$stmt->bindParam(":VCNCD", $VCNCD, PDO::PARAM_STR);
		$stmt->execute();

		$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($array) == 0 )
		{
			try
			{
				$InsertSql = "
					INSERT INTO SCHEDULE_INFO 
					(
						  `VCNCD`
                        , `TITLE`
                        , `MESSAGE`
                        , `TYPE`
                        , `MDATE` 
                        , `RDATE`
					)
					VALUES
					(
						  :VCNCD
                        , :TITLE
                        , :MESSAGE
                        , 'VCN'
                        , NULL
                        , NOW()
					)
				";

				$stmt = $this->_connection->prepare($InsertSql);
				$this->_connection->beginTransaction();				
				$stmt->bindParam(":VCNCD", $VCNCD, PDO::PARAM_STR);
                $stmt->bindParam(":TITLE", $TITLE, PDO::PARAM_STR);
                $stmt->bindParam(":MESSAGE", $MESSAGE, PDO::PARAM_STR);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Insert query error : [".$InsertSql."] ".$e->getMessage()."\n");
				return false;
			}
		} else {
			try
			{
				$UpdateSql = "
					UPDATE SCHEDULE_INFO SET
						  `TITLE` = :TITLE 
						, `MESSAGE` = :MESSAGE 
						, `MDATE` = NOW()
					WHERE `IDX` = :IDX 
				";

				$stmt = $this->_connection->prepare($UpdateSql);
				$this->_connection->beginTransaction();
				$stmt->bindParam(":TITLE", $TITLE, PDO::PARAM_STR);
				$stmt->bindParam(":MESSAGE", $MESSAGE, PDO::PARAM_STR);          
	            $stmt->bindParam(":IDX", $array[0]["IDX"], PDO::PARAM_INT);
				$stmt->execute();
				$this->_connection->commit();

				return true;
			}
			catch (Exception $e)
			{
				$this->_connection->rollback();
				$this->error("Update query error : [".$UpdateSql."] ".$e->getMessage()."\n");
				return false;
			}
		}
	}
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* TABLE DELETE FUNCTION */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */

	/**
	* 코드마스터 정보 삭제.
	* @returnValue ture or false
	*/
	public function delete_CodeMast()
	{
		try
		{
			$DeleteSql = "
				DELETE FROM CodeMast
			";
			$stmt = $this->_connection->prepare($DeleteSql);
			$this->_connection->beginTransaction();

			$stmt->execute();
			$this->_connection->commit();

			return true;
		}
		catch (Exception $e)
		{
			$this->_connection->rollback();
			$this->error("Delete query error : [".$DeleteSql."] ".$e->getMessage()."\n");
			return false;
		}
	}


	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* TABLE SELECT FUNCTION */
	/* ------------------------------------------------------------------------------------------------------------------------------ */
	/* ------------------------------------------------------------------------------------------------------------------------------ */

	/**
	* HOSP_MAX_VAR_INFO 실시간 병상 정보
	* @param $hpid 병원 아이디
	* @returnValue array or null
	*/
	public function get_hosp_max_var_info($hpid)
	{
		try
		{
			$selectSql = "
				SELECT
					HOSP_MAX_VAR_INFO.*
				FROM
					HOSP_MAX_VAR_INFO
				WHERE
					hpid = :hpid
			";

			$stmt = $this->_connection->prepare($selectSql);
      $stmt->bindParam(":hpid", $hpid, PDO::PARAM_STR);
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (Exception $e)
		{
			$this->error("Select query error : [".$selectSql."] ".$e->getMessage()."\n");
			return null;
		}
	}

  	/**
	* KIOSK_MAX_HOSP_INFO 중증 질환 정보
	* @param $hpid 병원 아이디
	* @returnValue array or null
	*/
	public function get_kiosk_max_hosp_info($hpid)
	{
		try
		{
			$selectSql = "
				SELECT
					KIOSK_MAX_HOSP_INFO.*
				FROM
					KIOSK_MAX_HOSP_INFO
				WHERE
					hpid = :hpid
			";

			$stmt = $this->_connection->prepare($selectSql);
      $stmt->bindParam(":hpid", $hpid, PDO::PARAM_STR);
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (Exception $e)
		{
			$this->error("Select query error : [".$selectSql."] ".$e->getMessage()."\n");
			return null;
		}
	}

	/**
	* HOLIDAY_INFO 공휴일 정보
	* @param $locdate 날짜정보
	* @returnValue array or null
	*/
	public function get_holiday_info($locdate)
	{
		try
		{
			$selectSql = "
				SELECT
					*
				FROM
					HOLIDAY_INFO
				WHERE
					`locdate` = :locdate
			";

			$stmt = $this->_connection->prepare($selectSql);
      		$stmt->bindParam(":locdate", $locdate, PDO::PARAM_STR);
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (Exception $e)
		{
			$this->error("Select query error : [".$selectSql."] ".$e->getMessage()."\n");
			return null;
		}
	}

}


?>
