<?php // $Id: checkemail.php 4 2012-04-28 18:19:08Z yama $
/**
 * checkemail.php - test.
 *
 * @author Narumi Sekiya
 * @version $Id: checkemail.php 4 2012-04-28 18:19:08Z yama $
 * @package quickmailjpn
 **/

require_once('../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once("./jphpmailer.php");
require_once("./lib.php");
require_once './constants.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/locallib.php';

use ver2\quickmailjpn\quickmailjpn as qm;

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

$confirmResult = "";

$mobileemail = ''; // checkemail.html
if (!empty($userdata_email)) {
    $mobileemail = $userdata_email->data;
}

if ($form = data_submitted()) do {
        //data submitted
	$userid = $USER->id;
	qm::set_user_field($userid, 'mobileemail', $form->mobileemail);

        $mobileemail = $form->mobileemail;

        if (empty($mobileemail)) {
            // 入力欄を空で送信するとメール設定解除
            qm::set_user_field($userid, 'mobileemailstatus', qm::STATUS_NOT_SET);
        } else {
            $confirmResult = get_string('sentcheckemail', 'block_quickmailjpn');

            //send e-mail to mobile phone
            mb_language("ja");
            $orgEncoding = mb_internal_encoding();
            mb_internal_encoding("UTF-8");

            //prepare e-mail data

            $key = makePassword(7);
            $encKey = md5($key);

            $text = $CFG->block_quickmailjpn_email_message."\n"
            	.(new moodle_url('/blocks/quickmailjpn/confirm.php', [
            			'id' => $userid,
            			'key' => $encKey
        		]))->out(false);

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
            qm::set_user([
            	'userid' => $userid,
            	'mobileemailauthkey' => $key,
            	'mobileemailstatus' => qm::STATUS_CHECKING
        	]);

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
$userid = $USER->id;
// $email_status = $DB->get_field('user_info_data', 'data', array('userid' => $USER->id, 'fieldid' => $userfield_status_id));
$email_status = qm::get_user_field($userid, 'mobileemailstatus');
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
