<?php 
require_once("../../config.php");
require_once("$CFG->dirroot/course/lib.php");
require_once './constants.php';
	
$id  = required_param('id', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);
	
$mes = '�m�F�����ُ�ł��B�S���̐搶�֘A�����Ă��������B';
	
$user = $DB->get_record("user", array("id" => $id));
	
$username = mb_convert_encoding(fullname($user), 'SJIS', 'UTF-8');
$mes = '�u'.$username.'�v����̌g�т̃��[���A�h���X�����������Ƃ��m�F����܂����B';
	
$block_quickmailjpn_key = $DB->get_record("block_quickmailjpn_key", array("userid" => $id));
	
$user_info_field = $DB->get_record('user_info_field', array('shortname' => 'quickmailJPNmobilestatus'));
$user_info_data = $DB->get_record('user_info_data', array('userid' => $id, 'fieldid' => $user_info_field->id));
	
$err = false;
if ( !$user || !$block_quickmailjpn_key || !$user_info_data ) {
    $err = true;
    $mes = '�m�F�����ɂ����Ė�肪�������܂����B�S���̐搶�֘A�����Ă��������B(�񑶍�)';
} else {
    if ( md5($block_quickmailjpn_key->email_key) != $key ) {
        $err = true;
        $mes = '�m�F�����ɂ����Ė�肪�������܂����B�S���̐搶�֘A�����Ă��������B(�L�[�s��v)';
    } else {
        if ( $user_info_data->data != QuickMailJPN_State::CHECKING ) {
            $err = true;
            $mes = '�m�F�����͂P�x�����s���Ζ�肠��܂���B';
        }
    }
}
	
if ( !$err ) {
    $user_info_data->data = QuickMailJPN_State::CONFIRMED;
    $result = $DB->update_record('user_info_data', $user_info_data);
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
�g�у��[���A�h���X�̃`�F�b�N<br>
<?php echo $mes; ?><br>
�����s���Ȃ��Ƃ�����܂�����A�S���̐搶�֐q�˂ĉ������B 
</body>
</html>
