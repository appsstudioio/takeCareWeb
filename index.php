<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/include/mHead.php");
?>
<body id="page-top">
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container">
      <a class="navbar-brand js-scroll-trigger" href="/index.php">아이를 부탁해</a>
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        Menu
        <i class="fas fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link js-scroll-trigger" href="#download">다운로드</a>
          </li>
          <li class="nav-item">
            <a class="nav-link js-scroll-trigger" href="#features">기능</a>
          </li>
          <li class="nav-item">
            <a class="nav-link js-scroll-trigger" href="#contact">SNS</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <header class="masthead">
    <div class="container h-100">
      <div class="row h-100">
        <div class="col-lg-7 my-auto">
          <div class="header-content mx-auto">
            <h1 class="mb-5">영유아를 대상으로 병원 검색 및 육아 정보를 제공하는 앱입니다.<br/> 초보 엄마, 아빠라면 한 번쯤 써야 하는 "아이를 부탁해" </h1>
            <a href="#download" class="btn btn-outline btn-xl js-scroll-trigger">지금 다운로드 하세요!!</a>
          </div>
        </div>
        <div class="col-lg-5 my-auto">
          <div class="device-container">
            <div class="device-mockup">
              <div class="device"></div>
              <div class="screen" style="background-image: url(/images/resource/lanuchScreen.jpg);"> </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <section class="download bg-primary text-center" id="download">
    <div class="container">
      <div class="row">
        <div class="col-md-8 mx-auto">
          <h2 class="section-heading">지금 "아이를 부탁해"를 사용해보세요.</h2>
          <p>아이폰만 지원합니다.</p>
          <div class="badges">
            <a class="badge-link" href="https://apps.apple.com/kr/app/id1471938305" target="_blank"><img src="/images/resource/app-store-badge.svg" alt=""></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="features" id="features">
    <div class="container">
      <div class="section-heading text-center">
        <h2>아이 건강을 위해서 도움이 되고자 만든 기능들</h2>
        <p class="text-muted">필요한 기능들은 지속적으로 업데이트할 예정입니다.</p>
        <hr>
      </div>
      <div class="row">
        <div class="col-lg-4 my-auto">
          <div class="device-container">
            <div class="device-mockup">
              <div class="device"></div>
              <div class="screen" style="background-image: url(/images/resource/detailScreen.jpg);"> </div>
            </div>
          </div>
        </div>
        <div class="col-lg-8 my-auto">
          <div class="container-fluid">
            <div class="row">
              <div class="col-lg-6">
                <div class="feature-item">
                  <i class="fas fa-street-view"></i>
                  <h3>가까운 병의원 정보 제공</h3>
                  <p class="text-muted">지정한 위치 반경에 가까운 응급실, 병원, 의원, 약국 정보를 제공합니다.</p>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="feature-item">
                  <i class="fas fa-directions"></i>
                  <h3>네비게이션 기능</h3>
                  <p class="text-muted">티맵, 네이버 네비게이션과 연동 기능을 제공합니다. 간편하게 네비게이션을 사용하세요.</p>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-6">
                <div class="feature-item">
                  <i class="fas fa-search-location"></i>
                  <h3>길찾기 기능</h3>
                  <p class="text-muted">구글 지도, 네이버 지도, 카카오맵의 길찾기 기능을 간편하게 사용할 수 있습니다.</p>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="feature-item">
                  <i class="fas fa-share-alt"></i>
                  <h3>공유 기능</h3>
                  <p class="text-muted">보고 있는 정보를 필요한 사람에게 공유하세요.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="contact bg-primary" id="contact">
    <div class="container">
      <h2>여러분의 소중한 의견을 언제나 기다립니다.</h2>
      <ul class="list-inline list-social">
        <li class="list-inline-item social-twitter">
          <a href="https://twitter.com/AppsStudioKr" target="_blank">
            <i class="fab fa-twitter"></i>
          </a>
        </li>
        <li class="list-inline-item social-facebook">
          <a href="https://www.facebook.com/아이를-부탁해-103607051037990" target="_blank">
            <i class="fab fa-facebook"></i>
          </a>
        </li>
        <li class="list-inline-item social-instagram">
          <a href="https://www.instagram.com/appsstudiokr/?hl=ko" target="_blank">
            <i class="fab fa-instagram"></i>
          </a>
        </li>
      </ul>
    </div>
  </section>
 
<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/include/mFooter.php");
?>
</body>
</html>
