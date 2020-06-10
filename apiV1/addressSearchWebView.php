<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>우편번호 검색</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha256-pasqAKBDmFT4eHoN2ndd6lN370kFiGUFyTiUHWhU7k8=" crossorigin="anonymous"></script>
  <style type="text/css">
    .searchView {display:block; position:absolute; overflow:hidden; z-index:1; -webkit-overflow-scrolling:touch;}
  </style>
</head>
<body>
  <div class="searchView" id="wrap"></div>
  <script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
  <script type="text/javascript">
  function postMessageToiOS(postData) {
      window.webkit.messageHandlers.callBackHandler.postMessage(postData);
  }

  var element_wrap = document.getElementById('wrap');

  $( document ).ready(function() {
      searchAddress();
  });

  function searchAddress() {
    
    //load함수를 이용하여 core스크립트의 로딩이 완료된 후, 우편번호 서비스를 실행합니다.
    new daum.Postcode({
      oncomplete: function(data) {
      // 팝업에서 검색결과 항목을 클릭했을때 실행할 코드를 작성하는 부분입니다.
      // 예제를 참고하여 다양한 활용법을 확인해 보세요.
        alert(data);

         var addr = ''; // 주소 변수
         var extraAddr = ''; // 참고항목 변수

         //사용자가 선택한 주소 타입에 따라 해당 주소 값을 가져온다.
         if (data.userSelectedType === 'R') { // 사용자가 도로명 주소를 선택했을 경우
             addr = data.roadAddress;
             // 법정동명이 있을 경우 추가한다. (법정리는 제외)
             // 법정동의 경우 마지막 문자가 "동/로/가"로 끝난다.
             if(data.bname !== '' && /[동|로|가]$/g.test(data.bname)){
                 extraAddr += data.bname;
             }
             // 건물명이 있고, 공동주택일 경우 추가한다.
             if(data.buildingName !== '' && data.apartment === 'Y'){
                 extraAddr += (extraAddr !== '' ? ', ' + data.buildingName : data.buildingName);
             }
             // 표시할 참고항목이 있을 경우, 괄호까지 추가한 최종 문자열을 만든다.
             if(extraAddr !== ''){
                 extraAddr = ' (' + extraAddr + ')';
             }
         } else { // 사용자가 지번 주소를 선택했을 경우(J)
             addr = data.jibunAddress;
         }
          var postData = {
              postcode : data.zonecode,
              addr : addr,
              extraAddr: extraAddr,
              searchKeyword: data.query
          };
          postMessageToiOS(postData);
      },
       width : '100%',
       height : '100%'
    }).embed(element_wrap);
    element_wrap.style.display = 'block';
    initLayerPosition();
  }

  function initLayerPosition(){
    var width = (window.innerWidth || document.documentElement.clientWidth); //우편번호서비스가 들어갈 element의 width
    var height = (window.innerHeight || document.documentElement.clientHeight); //우편번호서비스가 들어갈 element의 height
    element_wrap.style.width = width + 'px';
    element_wrap.style.height = height + 'px';
    element_wrap.style.left = '0px';
    element_wrap.style.top = '0px';
  }

  //       
</script>
</body>
</html>
