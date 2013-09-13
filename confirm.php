<?php
require_once("../../config.php");
require_once("$CFG->dirroot/course/lib.php");
require_once './constants.php';

require_once $CFG->dirroot . '/blocks/quickmailjpn/locallib.php';
use ver2\quickmailjpn\quickmailjpn as qm;

$id  = required_param('id', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);

$mes = '確認処理異常です。担当の先生へ連絡してください。';

$user = $DB->get_record("user", array("id" => $id));

$username = mb_convert_encoding(fullname($user), 'SJIS', 'UTF-8');
$mes = '「'.$username.'」さんの携帯のメールアドレスが正しいことが確認されました。';

// $block_quickmailjpn_key = $DB->get_record("block_quickmailjpn_key", array("userid" => $id));
// $block_quickmailjpn_key = qm::get_user_field($user->id, 'mobileemailauthkey');
$qmuser = qm::get_user($user->id);

// $user_info_field = $DB->get_record('user_info_field', array('shortname' => 'quickmailJPNmobilestatus'));
// $user_info_data = $DB->get_record('user_info_data', array('userid' => $id, 'fieldid' => $user_info_field->id));

$err = false;
// if ( !$user || !$block_quickmailjpn_key || !$user_info_data ) {
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
//     $user_info_data->data = QuickMailJPN_State::CONFIRMED;
//     $result = $DB->update_record('user_info_data', $user_info_data);
	qm::set_user([
		'userid' => $user->id,
		'mobileemailstatus' => qm::STATUS_CONFIRMED,
		'mobileemailauthkey' => null
	]);
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
携帯メールアドレスのチェック<br>
<?php echo $mes; ?><br>
何か不明なことがありましたら、担当の先生へ尋ねて下さい。
</body>
</html>
