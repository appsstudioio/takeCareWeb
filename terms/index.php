<?php 
$hiddenFlag = $_REQUEST["titleHidden"];
// echo '['.$hiddenFlag.']';
include_once($_SERVER["DOCUMENT_ROOT"]."/include/mHead.php");
?>
<body id="page-top">
<? if($hiddenFlag == "") { ?>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container">
      <a class="navbar-brand js-scroll-trigger" href="/index.php">아이를 부탁해</a>
    </div>
  </nav>
<? } ?>
  <section class="terms">
    <div class="container">
<? if($hiddenFlag == "") { ?>
      <div class="section-heading text-center">
        <h2>개인정보 처리방침</h2>
        <p class="text-muted">Privacy Policy</p>
        <hr>
      </div>
<? } ?>
      <div class="row" <? if($hiddenFlag == "Y") { ?>style="margin-top: -70px;"<? } ?>>
        <div class="col-lg-12 my-auto">
          <h4>기본원칙</h4>
          <p>
            "아이를 부탁해"는 개인정보보호법에 따라 이용자의 개인정보 보호 및 권익을 보호하고 개인정보와 관련한 이용자의 고충을 원할하게 처리할 수 있도록 다음과 같은 처리방침을 두고 있으며, 개인정보처리방침을 개정하는 경우 웹사이트를 통하여 공지할 것입니다. 본 방침은 2019년 7월 22일부터 시행됩니다.
          </p>
          <div style="margin: 10px 0px; border-bottom: 1px solid #dedede;"></div><br>

          <h4>개인정보 처리방침</h4><br>
          <h5>1. 아이를 부탁해는 서비스 제공에 필요한 최소한의 개인정보를 수집하고 있습니다.</h5>
          <p>
            아이를 부탁해는 앱 사용에 필요한 범용 고유 식별자(universally unique identifier, UUID)를 서버에 수집하고 있습니다. 그외 사용자가 설정한 위치 정보 및 검색 설정값 정보는 서버에 수집하지 않으며, 앱 내 iCloud 및 로컬 저장소에 저장되어 있습니다. 추가적으로 수집할 항목이 있을 경우 반드시 사전에 사용자에게 해당 사실을 알리고 동의를 거치겠습니다.
          </p>
          <h5>2. 아이를 부탁해는 민감 정보를 수집하지 않습니다.</h5>
          <p>
            아이를 부탁해는 이용자의 소중한 인권을 침해할 우려가 있는 민감한 정보(인종, 사상 및 신조, 정치적 성향 이나 범죄기록, 의료정보 등)는 어떠한 경우에도 수집하지 않으며, 만약 법령에서 정한 의무에 따라 불가피하게 수집하는 경우에는 반드시 이용자에게 사전 동의를 거치겠습니다.
          </p>

          <h5>3. 개인정보 열람청구</h5>
          <p>
            개인정보 처리방침에 불만이 있을실 경우 아래 기관에 문의하여 주시길 바랍니다. <br><br>
            개인정보침해 신고센터 (국번 없이) 118 https://privacy.kisa.or.kr<br>
            정보보호마크인증위원회 02-550-9531~2 http://www.eprivacy.or.kr<br>
            대검찰청 사이버수사과 (국번 없이) 1301 cid@spo.go.kr<br>
            경찰청 사이버안전국 (국번 없이) 182 https://cyberbureau.police.go.kr<br>
          </p>

          <h5>4. 개인정보 처리 방침 변경</h5>
          <p>
            개인정보처리방침은 시행일로부터 적용되며, 법령 및 방침에 따른 변경내용의 추가, 삭제 및 정정이 있는 경우에는 변경사항의 시행 7일 전부터 홈페이지를 통하여 고지할 것입니다.
          </p>
        </div>
      </div>
    </div>
  </section>

<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/include/mFooter.php");
?>
</body>
</html>
