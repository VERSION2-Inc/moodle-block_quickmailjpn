<?php
require_once '../../config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/locallib.php';

use ver2\quickmailjpn\quickmailjpn as qm;

$id  = required_param('id', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);

$mes = '�m�F�����ُ�ł��B�S���̐搶�֘A�����Ă��������B';

$user = $DB->get_record("user", array("id" => $id));

$username = mb_convert_encoding(fullname($user), 'SJIS', 'UTF-8');
$mes = '�u'.$username.'�v����̌g�т̃��[���A�h���X�����������Ƃ��m�F����܂����B';

$qmuser = qm::get_user($user->id);

$err = false;
if (!$qmuser) {
    $err = true;
    $mes = '�m�F�����ɂ����Ė�肪�������܂����B�S���̐搶�֘A�����Ă��������B(�񑶍�)';
} else {
    if ( md5($qmuser->mobileemailauthkey) != $key ) {
        $err = true;
        $mes = '�m�F�����ɂ����Ė�肪�������܂����B�S���̐搶�֘A�����Ă��������B(�L�[�s��v)';
    } else {
        if ( $qmuser->mobileemailstatus != QuickMailJPN_State::CHECKING ) {
            $err = true;
            $mes = '�m�F�����͂P�x�����s���Ζ�肠��܂���B';
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
<title>���[���m�F</title>
</head>

<body>
�g�у��[���A�h���X�̃`�F�b�N���������܂����B<br>
<?php echo $mes; ?><br>
�����s���Ȃ��Ƃ�����܂�����A�S���̐搶�֐q�˂ĉ������B
</body>
</html>
