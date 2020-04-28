<?php
require_once '../../config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/locallib.php';

use ver2\quickmailjpn\quickmailjpn as qm;

$id  = required_param('id', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);

$mes = '確認処理異常です。担当の先生へ連絡してください。';

$user = $DB->get_record("user", array("id" => $id));

$username = mb_convert_encoding(fullname($user), 'SJIS', 'UTF-8');
$mes = '「'.$username.'」さんの携帯のメールアドレスが正しいことが確認されました。';

$qmuser = qm::get_user($user->id);

$err = false;
if (!$qmuser) {
    $err = true;
    $mes = '確認処理において問題が発生しました。担当の先生へ連絡してください。(非存在)';
} else {
    if ( md5($qmuser->mobileemailauthkey) != $key ) {
        $err = true;
        $mes = '確認処理において問題が発生しました。担当の先生へ連絡してください。(キー不一致)';
    } else {
        if ( $qmuser->mobileemailstatus != QuickMailJPN_State::CHECKING ) {
            $err = true;
            $mes = '確認処理は１度だけ行えば問題ありません。';
        }
    }
}

if ( !$err ) {
	qm::set_user(array(
		'userid' => $user->id,
		'mobileemailstatus' => qm::STATUS_CONFIRMED,
		'mobileemailauthkey' => null
	));
}

header('Content-type: text/html; charset=Shift_JIS');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<title>メール確認</title>
</head>

<body>
携帯メールアドレスのチェックが完了しました。<br>
<?php echo $mes; ?><br>
何か不明なことがありましたら、担当の先生へ尋ねて下さい。
</body>
</html>
