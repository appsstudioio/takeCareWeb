<?php
// 에러 출력 유무
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT );
ini_set('display_errors','off');

date_default_timezone_set('Asia/Seoul');

//ini_set('session.save_handler', 'user');
ini_set('session.use_trans_sid', 0);    // PHPSESSID를 자동으로 넘기지 않음
ini_set('url_rewriter.tags','');        // 링크에 PHPSESSID가 따라다니는것을 무력화함
//ini_set('session.cookie_domain', '.weact.co.kr');	//-< 꼭 수정
//session_save_path($_SERVER ['DOCUMENT_ROOT'].'/_tmp/_session');
ini_set("session.cache_expire", 86400);      // 세션 유효시간 :180 (3분)
ini_set("session.gc_maxlifetime", 86400);  // 세션 가비지 컬렉션(로그인시 세션지속 시간) : 초
ini_set("session.cookie_lifetime", 0);

/* Define */
/* IMAGE PATH */
define("_PATH_appIcon", "/path/", true);

// 소스 이전시 반드시 수정해야 할것들...

define("_WEBSITE_URL", $_SERVER['HTTP_HOST'].'/' , true);
define("_WEB_MASTER_EMAIL", "webmaster@mail.com", true);

// DATABASE DEFINE
define("_DB_HOST", "", true);
define("_DB_USER", "", true);
define("_DB_PASSWORD", "", true);
define("_DB_NAME", "takeCare", true);
define("_SERVICE_KEY", "", true);
define("_SERVICE_KEY_NEW", urlencode(""), true);
?>
