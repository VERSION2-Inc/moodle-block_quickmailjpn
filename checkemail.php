<?php // $Id: checkemail.php 4 2012-04-28 18:19:08Z yama $
/**
 * checkemail.php - test.
 *
 * @todo 
 * @author Narumi Sekiya
 * @version $Id: checkemail.php 4 2012-04-28 18:19:08Z yama $
 * @package quickmailjpn
 **/
require_once('../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once("./jphpmailer.php");
require_once("./lib.php");
require_once './constants.php';
	
$id = required_param('id', PARAM_INT);    // course id
$action = optional_param('action', '', PARAM_ALPHA);
$instanceid = optional_param('instanceid', 0, PARAM_INT);

$PAGE->set_url('/blocks/quickmailjpn/checkemail.php');
	
$instance = new stdClass();
	
if (!$course = $DB->get_record('course', array('id' => $id))) {
    error('Course ID was incorrect');
}
	
require_login($course->id);
	
if ($instanceid) {
    $instance = $DB->get_record('block_instances', array('id' => $instanceid));
} else {
    if ($quickmailjpnblock = $DB->get_record('block', array('name' => 'quickmailjpn'))) {
        $instance = $DB->get_record('block_instances', array('blockid' => $quickmailjpnblock->id, 'pageid' => $course->id));
    }
}

/// This block of code ensures that QuickmailJPN will run 
///     whether it is in the course or not
/*
  if (empty($instance)) {
  if (has_capability('block/quickmailjpn:view', get_context_instance(CONTEXT_BLOCK, $instanceid))) {
  $haspermission = true;
  } else {
  $haspermission = false;
  }
  } else {
  // create a quickmailjpn block instance
  $quickmailjpn = block_instance('quickmailjpn', $instance);
  $haspermission = $quickmailjpn->check_permission();
  }
	
  if (!$haspermission) {
  error('Sorry, you do not have the correct permissions to use QuickmailJPN.');
  }
*/
	
// カスタムフィールドIDを逆引き
$userfield_email_id  = $DB->get_field('user_info_field', 'id', array('shortname' => QuickMailJPN_FieldName::EMAIL));
$userfield_status_id = $DB->get_field('user_info_field', 'id', array('shortname' => QuickMailJPN_FieldName::STATUS));
	
//get USER's mobile e-mail address from user_info_data
$userdata_email = $DB->get_record('user_info_data', array('userid' => $USER->id, 'fieldid' => $userfield_email_id));
	
$confirmResult = "";
	
$mobileemail = ''; // checkemail.html
if (!empty($userdata_email)) {
    $mobileemail = $userdata_email->data;
}
	
if ($form = data_submitted()) do {
        //data submitted
        //save mobile e-mail address into user_info_data
        if (empty($userdata_email)) {
            // 未登録 → 作成
            $userdata_email = new stdClass();
            $userdata_email->userid  = $USER->id;
            $userdata_email->fieldid = $userfield_email_id;
            $userdata_email->data    = $form->mobileemail;
            if (!$DB->insert_record("user_info_data", $userdata_email)) {
                print_error("Could not insert your user_info_data");
            }
        } else {
            // 登録済 → 更新
            if ($userdata_email->data == $form->mobileemail) {
                // 二重送信 → スキップ
                //break;
                redirect($CFG->wwwroot.'/blocks/quickmailjpn/checkemail.php?id='.$id.'&instanceid='.$instanceid);
            }
            $userdata_email->data = $form->mobileemail;
            if (!$DB->update_record("user_info_data", $userdata_email)) {
                print_error("Could not update your user_info_data");
            }
        }
        $mobileemail = $form->mobileemail;
		
        if (empty($mobileemail)) {
            // 入力欄を空で送信するとメール設定解除
            $DB->set_field('user_info_data', 'data', QuickMailJPN_State::NOT_SET,
                           array('userid' => $USER->id, 'fieldid' => $userfield_status_id));
        } else {
            $confirmResult = get_string('sentcheckemail', 'block_quickmailjpn');
			
            //send e-mail to mobile phone
            mb_language("ja");
            $orgEncoding = mb_internal_encoding();
            mb_internal_encoding("UTF-8");
			
            //prepare e-mail data
            $svr = $_SERVER["SERVER_NAME"];
            $pth = dirname($_SERVER["SCRIPT_NAME"]);
			
            $key = makePassword(7);
            $encKey = md5($key);
			
            $text = $CFG->block_quickmailjpn_email_message."\n"
                . "http://".$svr.$pth."/confirm.php?id=".$USER->id."&key=".$encKey;
			
            //from address
            $from = $CFG->block_quickmailjpn_email;
            if (!$from) {
                $from = "noreply";
            }
			
            //send e-mail by JPHPMailer via PHPMailer
            $mail = new JPHPMailer();
			
            $mail->addTo($mobileemail);
            $mail->setFrom($from, $from);
            $mail->setSubject($CFG->block_quickmailjpn_email_subject);
            $mail->setBody($text);
            $mail->send();
			
            // save status to block_quickmailjpn_status
            if ($key_status = $DB->get_record('block_quickmailjpn_key', array('userid' => $USER->id))) {
                $key_status->email_key = $key;
                $DB->update_record('block_quickmailjpn_key', $key_status);
            } else {
                $key_status = new stdClass();
                $key_status->userid    = $USER->id;
                $key_status->email_key = $key;
                $DB->insert_record('block_quickmailjpn_key', $key_status);
            }
			
            if ($userdata_status = $DB->get_record('user_info_data',
                                                   array('userid' => $USER->id, 'fieldid' => $userfield_status_id)))
            {
                $userdata_status->data = QuickMailJPN_State::CHECKING;
                $DB->update_record('user_info_data', $userdata_status);
            } else {
                $userdata_status = new stdClass();
                $userdata_status->userid  = $USER->id;
                $userdata_status->fieldid = $userfield_status_id;
                $userdata_status->data    = QuickMailJPN_State::CHECKING;
                $DB->insert_record('user_info_data', $userdata_status);
            }
			
            //return to original internal_encoding
            mb_internal_encoding($orgEncoding);
			
            // F5で再送信しないようにリダイレクト
            redirect($CFG->wwwroot.'/blocks/quickmailjpn/checkemail.php?id='.$id.'&instanceid='.$instanceid);
        }
    } while (false);
	
/// Start printing everyting
$strquickmailjpn = get_string('mymobilephone', 'block_quickmailjpn');
$disabled = '';
	
/// Header setup
$PAGE->set_title($course->fullname.': '.$strquickmailjpn);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strquickmailjpn);
	
echo $OUTPUT->header();
	
echo $OUTPUT->heading($strquickmailjpn);
	
echo "<center>\n";
	
//present status
$email_status = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => $userfield_status_id));
if (!$email_status) {
    // 未設定
    $email_status = QuickMailJPN_State::NOT_SET;
}
echo get_string("presentstatus", 'block_quickmailjpn');
switch ($email_status) {
case QuickMailJPN_State::NOT_SET:
    echo '<font color="blue">'.get_string("user-$email_status", 'block_quickmailjpn').'</font>';
    break;
case QuickMailJPN_State::CHECKING:
    echo '<font color="red">'.get_string("user-$email_status", 'block_quickmailjpn').'</font>';
    break;
case QuickMailJPN_State::CONFIRMED:
    echo '<font color="green">'.get_string("user-$email_status", 'block_quickmailjpn').'</font>';
    break;
}
echo "<br /><br />";
	
if ($action == 'confirm') {
    echo $confirmResult;
}
echo '<div id="tablecontainer">';
require($CFG->dirroot.'/blocks/quickmailjpn/checkemail.html');
echo '</div>';
echo '<table cellpadding="15" cellspacing="0"><tr><td align="left" bgcolor="#DDFFFF">';
echo get_string("statusexplanation", 'block_quickmailjpn', $CFG->block_quickmailjpn_email);
echo '</td></tr></table>';
echo "</center>\n";

echo $OUTPUT->footer();
