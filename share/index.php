<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/common/lib/phpLibrary.php");
ini_set('display_errors','off');

include_once($_SERVER["DOCUMENT_ROOT"]."/include/mHead.php");
$hpid = $_REQUEST["hpid"];
$type = $_REQUEST["type"];
$dataRSArray = array();

if($hpid == "" || $type == "") {
  header('Location: /index.php');
} else {
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
  } else {
      header('Location: /index.php');
  }

   $_pdoObject = null;
}

function returnTimeString($stime, $etime) {
  $returnString = "";

  if($stime != "" && $etime != "") {
    $returnString = substr($stime,0,2).":".substr($stime,2,2)." ~ ".substr($etime,0,2).":".substr($etime,2,2);
  } else {
    $returnString = "휴무";
  }

  return $returnString;
}

?>
<body id="page-top">
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container">
      <a class="navbar-brand js-scroll-trigger" href="/index.php">아이를 부탁해</a>
    </div>
  </nav>

  <section class="share">
    <div class="container">
      <div class="section-heading text-center">
        <h2><?=$dataRSArray[0]["dutyName"]?></h2>
        <p class="text-muted">상세 정보</p>
      </div>
      <div class="row" style="margin-top: -50px;">
        <div class="col-lg-12 my-auto">

          <div class="card">
            <div class="card-header bg-light text-sucess">
              기본정보
            </div>
            <div class="card-body bg-white text-secondary">
              주소 : <?=$dataRSArray[0]["dutyAddr"]?> <br />
              전화번호 : <?=$dataRSArray[0]["dutyTel1"]?> <br /><br />
              <p class="border-top" style="padding: 10px 0px 0px 0px;">
                <?=$dataRSArray[0]["dutyInf"]?> <?=$dataRSArray[0]["dutyMapimg"]?> <?=$dataRSArray[0]["dutyEtc"]?>
              </p>
            </div>
          </div>
          <br />

          <div class="card">
            <div class="card-header bg-light text-sucess">
              운영시간
            </div>
            <div class="card-body bg-white text-secondary">

              <table class="table border-0">
                <tbody>
                  <tr>
                    <th scope="row">월요일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime1s"], $dataRSArray[0]["dutyTime1c"])?></td>
                    <th scope="row">화요일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime2s"], $dataRSArray[0]["dutyTime2c"])?></td>
                  </tr>
                   <tr>
                    <th scope="row">수요일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime3s"], $dataRSArray[0]["dutyTime3c"])?></td>
                    <th scope="row">목요일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime4s"], $dataRSArray[0]["dutyTime4c"])?></td>
                  </tr>
                   <tr>
                    <th scope="row">금요일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime5s"], $dataRSArray[0]["dutyTime5c"])?></td>
                    <th scope="row">토요일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime6s"], $dataRSArray[0]["dutyTime6c"])?></td>
                  </tr>
                   <tr>
                    <th scope="row">일요일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime7s"], $dataRSArray[0]["dutyTime7c"])?></td>
                    <th scope="row">공휴일</th>
                    <td><?=returnTimeString($dataRSArray[0]["dutyTime8s"], $dataRSArray[0]["dutyTime8c"])?></td>
                  </tr>
                </tbody>
              </table>

            </div>
          </div>
          <br />

          <div class="card">
            <div class="card-header bg-light text-sucess">
              진료과목
            </div>
            <div class="card-body bg-white text-secondary">
              <?
              if($dataRSArray[0]["dgidIdName"] != "") {
                $array = explode(',', $dataRSArray[0]["dgidIdName"]);

                for($i=0; $i<count($array); $i++) {
                ?>
                <span class="badge badge-pill badge-success" style="font-size: 12px; padding: 5px 10px; margin:5px;"><?=$array[$i]?></span>
                <?
                }
              }
              ?>
            </div>
          </div>
          <br />

      </div>
    </div>
  </section>

<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/include/mFooter.php");
?>
</body>
</html>