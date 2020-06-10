<?php
// ErrorMsg Define
define("_SUCCESS",0,true);
define("_ERROR01",1,true);
define("_ERROR02",2,true);
define("_ERROR03",3,true);
define("_ERROR04",4,true);
define("_ERROR05",5,true);
define("_ERROR06",6,true);
define("_ERROR07",7,true);
define("_ERROR08",8,true);
define("_ERROR09",9,true);
define("_ERROR10",10,true);
define("_ERROR11",11,true);
define("_ERROR12",12,true);
define("_ERROR13",13,true);
define("_ERROR14",14,true);
define("_ERROR15",15,true);
define("_ERROR16",16,true);
define("_ERROR17",17,true);

class utilLibrary
{
	function __construct()
	{

	}

	function __destruct()
	{

	}

	public function error($msg, $logFile)
	{
		error_log(date('Y-m-d H:i:s')." : ".$msg, 3, $_SERVER["DOCUMENT_ROOT"]."/common/log/".$logFile.".log");
	}
	
	public function create_idx()
	{
		list($microtime,$timestamp) = explode(' ',microtime());
		$time = $timestamp.substr($microtime, 2, 3);
		$ns_idx = (string)$time;
		
		return $ns_idx;
	}

	/**
	* 푸쉬 인덱스 생성 (단말ID_푸쉬타입_년월일시분초)
	* @param $DEVICE_ID  단말 아이디
	* @param $PUSH_TYPE  푸쉬 타입
	* @returnValue Idx 인덱스
	*/
	public function create_idx_gem_push_msg_mst($DEVICE_ID, $PUSH_TYPE)
	{
		return $DEVICE_ID."_".$PUSH_TYPE."_".date('YmdHis',time());
	}

	/**
	* 시간연산
	* @param $sTime  시작시간
	* @param $nTime  분초
	* @returnValue 다음시간
	*/
	public function return_startTime($sTime, $nTime)
	{
		$toDate = date("Y-m-d",time())." ".$sTime;
		$tempM = (int)substr($nTime, 0, 2);
		$tempS = (int)substr($nTime, 3, 2);
		$nextSecond = ($tempM * 60) + $tempS;
		
		return date("H:i:s",strtotime($toDate.'+'.$nextSecond.' seconds'));
	}
	
	/**
	* 에러 메시지 출력 (앱 JSON 리턴 메시지)
	* @param $ErrorCode 에러코드
	* @returnValue 파일 데이터
	*/
	public function errorCheckReturnMsg($ErrorCode)
	{
		switch((int)$ErrorCode)
		{
			case _SUCCESS: return "성공"; break;
			case _ERROR01: return "Request Parameter 값이 없습니다."; break;
			case _ERROR02: return "해당하는 데이터가 없습니다."; break;
			case _ERROR03: return "비밀번호가 일치하지 않습니다."; break;
			case _ERROR04: return "DB처리 오류!!"; break;
			case _ERROR05: return "동일한 아이디가 있습니다."; break;
			case _ERROR06: return "아이디가 존재하지 않습니다."; break;
			case _ERROR07: return "인증키가 등록되어 있습니다. 해제 후 사용하세요."; break;
			case _ERROR08: return "인증키가 일치하지 않습니다."; break;
			case _ERROR011: return "동기화 오류"; break;
		}
	}

	/**
	* utf8 to euc-kr
	* @param $str    변경할 문자

	* @returnValue euc-kr string
	*/
	public function utf2euc($str)
	{
		return iconv("UTF-8","cp949//IGNORE", $str);
	}

	/**
	* check IE
	* @returnValue true and false
	*/
	public function is_ie()
	{
		if(!isset($_SERVER['HTTP_USER_AGENT']))return false;
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) return true; // IE8
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'Windows NT 6.1') !== false) return true; // IE11
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) return true; // IE11

		return false;
	}

	/**
	* 백그라운드 php 스크립트 실행
	* @param $uri URL 주소
	* @param $params 파라미터
	* @returnValue none
	*/
	public function curl_post_async($uri, $params)
	{
	    $command = "curl ";
	    foreach ($params as $key => &$val)
	            $command .= "-F '$key=$val' ";

	    $command .= "$uri -s > /dev/null 2>&1 &";

	    passthru($command);
	}
	
	/**
	* Http Request 요청
	* @param $_ip URL 주소
	* @param $_array 전송데이터
	*/
	public function requestCallAPI($_ip, $fields)
	{
		$headers = array(
			'Content-Type: application/json'
		);

		// Open connection
		$ch = curl_init();

		// Set the url, number of POST vars, POST data
		curl_setopt( $ch, CURLOPT_URL, $_ip );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		// Avoids problem with https certificate
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );

		// Execute post
		$result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if((int)$http_code == 200)
		{
			return json_decode($result);
		}
		else
		{
			return null;
		}
	}
	
	/**
	* 동기화 데이터 처리 요청
	* @param $_ip URL 주소
	* @param $_array 전송데이터
	*/
	public function syncDataProcess($_pdoObject, $_url, $_infoArray, $_ID, $_syncID, $_APIKEY)
	{
		$sdate = date("Y-m-d H:i:s", time());
		$syncArray = array();
		
		if(count($_infoArray) > 0)
		{
			$syncArray["status_code"] = "START";
		    $syncArray["tCount"] = count($_infoArray);
		    $syncArray["tSuccess"] = 0;
		    $syncArray["tFail"] = 0;
		    $syncArray["errorMsg"] = "";
		    $syncArray["sdate"] = $sdate;
		    $syncArray["edate"] = $sdate;
		    $syncArray["syncID"] = $_syncID;
			$_pdoObject->update_sync_monitoring_info($syncArray);
			
			$_array = array('ID' => $_ID, 'data' => $_infoArray, 'APIKEY' => $_APIKEY);
			$resultArray = $this->requestCallAPI($_url, $_array);
			if($resultArray != null)
			{
				if($resultArray->STATUSCODE == "0")
				{
					$syncArray["status_code"] = "SUCCESS";
				    $syncArray["tSuccess"] = (int)$resultArray->TSUCCESS;
				    $syncArray["tFail"] = (int)$resultArray->TFAIL;
				    $syncArray["errorMsg"] = "";
				    $syncArray["edate"] = date("Y-m-d H:i:s", time());
					$_pdoObject->update_sync_monitoring_info($syncArray);
				}
				else
				{
					$StatusCode = $resultArray->STATUSCODE;
					$syncArray["status_code"] = "FAIL (".$StatusCode.")";
			        $syncArray["tSuccess"] = (int)$resultArray->TSUCCESS;
			        $syncArray["tFail"] = (int)$resultArray->TFAIL;
			        $syncArray["errorMsg"] = $resultArray->STATUSMSG;
			        $syncArray["edate"] = date("Y-m-d H:i:s", time());
					$_pdoObject->update_sync_monitoring_info($syncArray);
					
					$_pdoObject->insert_sync_error_info($_syncID, $resultArray->STATUSMSG);
				}
			}
			else
			{
				$syncArray["status_code"] = "FAIL";
		        $syncArray["tSuccess"] = 0;
		        $syncArray["tFail"] = 0;
		        $syncArray["errorMsg"] = "네트워크 접속이 원활하지 않습니다.";
		        $syncArray["edate"] = date("Y-m-d H:i:s", time());
				$_pdoObject->update_sync_monitoring_info($syncArray);
				
				$_pdoObject->insert_sync_error_info($_syncID, "네트워크 접속이 원활하지 않습니다.");
			}
			
		}
		else
		{
			$syncArray["status_code"] = "WAIT";
		    $syncArray["tCount"] = 0;
		    $syncArray["tSuccess"] = 0;
		    $syncArray["tFail"] = 0;
		    $syncArray["errorMsg"] = "정보가 없어 동기화 하지 못했습니다.";
		    $syncArray["sdate"] = date("Y-m-d H:i:s", time());
		    $syncArray["edate"] = date("Y-m-d H:i:s", time());
		    $syncArray["syncID"] = $_syncID;
			$_pdoObject->update_sync_monitoring_info($syncArray);
		}
	}
	
	
	/**
	* 파일 데이터 읽어오기
	* @param $source_path 제목
	* @returnValue 파일 데이터
	*/
	public function getFileRead($source_path)
	{
		if(file_exists($source_path))
		{
			$fp = fopen($source_path,"r");
			$source_data = fread($fp,filesize($source_path));
			fclose($fp);

			return $source_data;
		}

		return "";
	}

	/**
	* 파일 데이터 쓰기
	* @param $path             파일경로
	* @param $source_file_name 파일이름
	* @param $data             파일데이터
	* @returnValue true or fasle
	*/
	public function setFileWrite($path, $source_file_name, $data)
	{
		// 경로 유무 체크
		if(!is_dir($path))
		{
			// 폴더가 없으면 경로 생성
			mkdir($path, 0777, true);
		}
		$source_path = $path.$source_file_name;
		/*
	      읽기 전용일 때는 'rb'를, 쓰기 전용일 때는 'wb'를, 데이터를 누적시킬 때는 'ab'를 사용
		*/
		$fp = fopen($source_path,"wb");
		fwrite($fp, $data);
		fclose($fp);
	}

	/**
	* 파일 확장자 구하기
	* @param $file 파일명
	* @returnValue 임시비밀번호
	*/
	public function getExt($file)
	{
		$needle = strrpos($file, ".") + 1; // 파일 마지막의 "." 문자의 위치를 반환한다.
		$slice = substr($file, $needle); // 확장자 문자를 반환한다.
		$ext = strtolower($slice); // 반환된 확장자를 소문자로 바꾼다.
		return $ext;
	}

	/**
	* 임시 비밀번호
	* @param $length 암호길이 1은 최대 3글자임.
	* @returnValue 임시비밀번호
	*/
	public function createPassword($length)
	{
		$sArray = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
		$returnValue = "";

		for($i=0; $i<(int)$length; $i++)
		{
			$indexValue = rand(0,(count($sArray)-1));

			if($i%2 == 0)
			{
				$returnValue .= $indexValue.$sArray[$indexValue];
			}
			else
			{
				$returnValue .= $indexValue.strtoupper($sArray[$indexValue]);
			}
		}

		return $returnValue;
	}
	
	/**
	* 인증키 생성
	* @returnValue 인증키
	*/
	public function createAuth()
	{
		$sArray = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
		$returnValue = "";
		$length = (int)rand(6,10);
		
		for($i=0; $i<$length; $i++)
		{
			$indexValue = rand(0,(count($sArray)-1));

			if($i%2 == 0)
			{
				$returnValue .= $indexValue.$sArray[$indexValue];
			}
			else
			{
				$returnValue .= $indexValue.strtoupper($sArray[$indexValue]);
			}
		}

		return date('YmdHis',time()).$returnValue;
	}
	
	/**
	* 일몰 일출 시간 구하기
	* @param $GET_DATE       조회일자
	* @param $SUNRISE_FLAG   Sunrise : true, Sunset : false
	* @param $FARM_LAT       위치 LAT
	* @param $FARM_LON       위치 LON
	* @param $_ZENITH        0(일출일몰), 1(시민박명), 2(항해박명), 3(천문박명)
	* @param $_LOCAL_OFFSET  로컬 타임
	* @returnValue String Date
	*/
	public function get_sunrise_sunset_time($GET_DATE, $SUNRISE_FLAG, $FARM_LAT, $FARM_LON, $_ZENITH, $_LOCAL_OFFSET)
	{
		if($SUNRISE_FLAG == true)
		{
			return date_sunrise(strtotime($GET_DATE), SUNFUNCS_RET_STRING, $FARM_LAT, $FARM_LON, $_ZENITH, $_LOCAL_OFFSET).":00";
		}
		else
		{
			return date_sunset(strtotime($GET_DATE), SUNFUNCS_RET_STRING, $FARM_LAT, $FARM_LON, $_ZENITH, $_LOCAL_OFFSET).":00";
		}	
	}
	
	/**
	* 날짜 정보 가져오기
	* @param  $type 구분
	* @returnValue 날짜정보
	*/
	public function getNowDate($type)
	{		
		$strCurDate = date('Ymd',time());
		if($type == "ND")
		{
			$strCurDate = date('Ymd',time());
		}
		else if($type == "YD")
		{
			$strCurDate = date('Y-m-d',time());
		}
		else if($type == "NY")
		{
			$strCurDate = date('Y',time());
		}
		else if($type == "NM")
		{
			$strCurDate = date('Ym',time());
		}

		return $strCurDate;
	}
	
	/**
	* 시간비교
	* @param  $stime 시작시간
	* @returnValue true or false
	*/
	public function chekeTimeValue($stime, $minutes)
	{
		$tempSdate = strtotime(date("Y-m-d", time()).' '.$stime);
		$tempMinute = (int)str_replace("분", "", $minutes);
		if(date("Y-m-d H:i", strtotime('+'.$tempMinute.' minutes', $tempSdate)) > date("Y-m-d H:i", time())) return true;
		else return false;
	}
	
	/**
	* 요일 정보 가져오기
	* @param  $dow_number 요일 인덱스 0~6
	* @returnValue 요일
	*/
	public function dow_KO($dow_number) {
		$dow_array_KO = array("일", "월", "화", "수", "목", "금", "토");
		$dow_KO = $dow_array_KO[$dow_number];
		return $dow_KO;
	}

	/**
	* 날짜 포맷 지정
	* @param $dateStr    날짜
	* @param $dateFormat 변형 날짜 포멧
	* @param $wFlag      요일 표시 여부
	* @returnValue 날짜
	*/
	public function MakeDateFormat($dateStr, $dateFormat, $wFlag)
	{
		$ReturnString = "";
		if($dateStr != "" && substr($dateStr, 0,4) != "0000")
		{
			$dow_array_KO = array("일", "월", "화", "수", "목", "금", "토");
			$dow_KO = "(".$this->dow_KO(date("w", strtotime($dateStr))).")";

			$ReturnString = date($dateFormat, strtotime($dateStr));
			if($wFlag)
			{
				return $ReturnString." ".$dow_KO;
			}
		}

		return $ReturnString;
	}


	/**
	* SELECT BOX Create
	* @param $val
	* @param $optionList
	* @param $valueList
	* @param $name
	* @param $delimiter
	* @param $style

	* @returnValue select box html
	*/
	public function makeSelect($val, $optionList, $valueList, $name, $delimiter, $style) {
		$selectList = "<select name='$name' $style>";
		$options = explode($delimiter, $optionList);
		$values = explode($delimiter, $valueList);
		for ( $i=0; $i<count($values); $i++ ) {
			if ( $val == trim($values[$i]) ) {
				$selectList .= "<option value='".trim($values[$i])."' selected='selected'>".trim($options[$i])."</option>";
			} else {
				$selectList .= "<option value='".trim($values[$i])."'>".trim($options[$i])."</option>";
			}
		}
		$selectList .="</select>";

		return($selectList);
	}


	/**
	* 게시판 페이지 리스트 생성
	* @param $iPage       현재페이지
	* @param $iTotal      총 갯수
	* @param $iPageSize   페이지당 리스트 갯수
	* @param $strBUrl     링크주소

	* @returnValue 리스트 바
	*/
	public function boardPaging($iPage, $iTotal, $iPageSize, $strBUrl) {
		$strRet = "";
		$iTPage = (($iTotal-1)/$iPageSize)+1; // 총페이지
		setType($iTPage,"integer");
		$iTSection = (($iTPage-1)/$iPageSize)+1; // 총 섹션
		setType($iTSection,"integer");
		$iCSection = (($iPage-1)/$iPageSize)+1; // 현재 섹션
		setType($iCSection,"integer");
		$iSPage = (($iCSection-1)*$iPageSize)+1; // 현 섹션의 시작 페이지
		setType($iSPage,"integer");
		$iPPage = 0; // 이전 섹션 시작 페이지
		$iNPage = 0; // 다음 섹션 시작 페이지

		$strBUrl = ereg_replace(" ","+",$strBUrl);

		if (strstr($strBUrl,"?"))
		{
			$strMark = "&";
		}
		else
		{
			$strMark = "?";
		}

		$strRet = $strRet."";
		if ($iPage > 1)
		{
			$strRet = $strRet."<a href=".$strBUrl.$strMark."page=".(1)." class='page' title='맨 처음 페이지'><<</span></a>";
			$strRet = $strRet."<a href=".$strBUrl.$strMark."page=".($iPage-1)." class='page' title='이전 페이지'><</a>";
		}
		else
		{

		}
		for ($i=$iSPage,$j=0 ; $i<=$iTPage&&$j<10 ; $i++,$j++)
		{
			if ($i==$iPage)
			{
				$strRet = $strRet."<span class='page active' title='현재 페이지'>".$i."</span>";
			}
			else
			{
				$strRet = $strRet."<a href=".$strBUrl.$strMark."page=".$i."  class='page' title='".$i."번째 페이지'>".$i."</a>";
			}
		}

		if ($iPage < $iTPage)
		{
			$strRet = $strRet."<a href=".$strBUrl.$strMark."page=".($iPage+1)." class='page' title='다음 페이지'>></a>";
			$strRet = $strRet."<a href=".$strBUrl.$strMark."page=".($iTPage)."  class='page' title='맨 나중 페이지'>>></a>";
		}
		else
		{

		}

		if ($strRet=="")
		{
		}

		return($strRet);
	}
}

?>