<?php
require_once("./jphpmailer.php");
	
function makePassword($len) {
    srand((double)microtime() * 54234853);
	
    $pstr = "abcdefghkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ2345679";
    $pary = preg_split("//", $pstr, 0, PREG_SPLIT_NO_EMPTY);
	
    $pw = "";
    for($i=0; $i<$len; $i++ ) {
        // パスワード文字列を生成
        $pw .= $pary[array_rand($pary, 1)];
    }
    return $pw;
}
function jis_email_to_user($touser, $to, $from, $subject, $messagetext) {
		
    return "OK";
}
