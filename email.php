<?php //$Id: email.php 4 2012-04-28 18:19:08Z yama $
/**
 * email.php - Used by Quickmail for sending emails to users enrolled in a specific course.
 *      Calls email.hmtl at the end.
 *
 * @author Mark Nielsen (co-maintained by Wen Hao Chuang)
 * @special thanks for Neil Streeter to provide patches for GROUPS
 * @package quickmail
 **/

require_once('../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once('./lib.php');
require_once './constants.php';

$id         = required_param('id', PARAM_INT);  // course ID
$instanceid = optional_param('instanceid', 0, PARAM_INT);
$action     = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url('/blocks/quickmailjpn/email.php');

$instance = new stdClass();

if (!$course = $DB->get_record('course', array('id' => $id))) {
    error('Course ID was incorrect');
}

require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

if ($instanceid) {
    $instance = $DB->get_record('block_instances', array('id' => $instanceid));
} else {
    if ($quickmailjpnblock = $DB->get_record('block', array('name' => 'quickmailjpn'))) {
        $instance = $DB->get_record('block_instances', array('blockid' => $quickmailjpnblock->id, 'pageid' => $course->id));
    }
}

/// This block of code ensures that QuickmailJPN will run
///     whether it is in the course or not
if (empty($instance)) {
    $groupmode = groupmode($course);
    if (has_capability('block/quickmailjpn:cansend', get_context_instance(CONTEXT_BLOCK, $instanceid))) {
        $haspermission = true;
    } else {
        $haspermission = false;
    }
} else {
    // create a quickmailjpn block instance
    $quickmailjpn = block_instance('quickmailjpn', $instance);

    $groupmode     = $quickmailjpn->groupmode();
    $haspermission = $quickmailjpn->check_permission();
}

if (!$haspermission) {
    error('Sorry, you do not have the correct permissions to use QuickmailJPN.');
}

if (!$courseusers = get_users_by_capability($context, 'moodle/grade:view', 'u.*', 'u.lastname, u.firstname', '', '', '', '', false)) {
    error('No course users found to email');
}


// カスタムフィールドIDを逆引き
$userfield_email_id  = $DB->get_field('user_info_field', 'id', array('shortname' => QuickMailJPN_FieldName::EMAIL));
$userfield_status_id = $DB->get_field('user_info_field', 'id', array('shortname' => QuickMailJPN_FieldName::STATUS));

if ($action == 'view') {
    // viewing an old email.  Hitting the db and puting it into the object $form
    $emailid = required_param('emailid', PARAM_INT);
    $form = $DB->get_record('block_quickmailjpn_log', array('id' => $emailid));
    $form->mailto = explode(',', $form->mailto); // convert mailto back to an array

} else if ($form = data_submitted()) {   // data was submitted to be mailed
    confirm_sesskey();

    if (!empty($form->cancel)) {
        // cancel button was hit...
        redirect("$CFG->wwwroot/course/view.php?id=$course->id");
    }

    // prepare variables for email
    $form->subject = stripslashes($form->subject);
    $form->subject = clean_param(strip_tags($form->subject, '<lang><span>'), PARAM_RAW); // Strip all tags except multilang
    $form->message = clean_param($form->message, PARAM_CLEANHTML);

    // make sure the user didn't miss anything
    if (!isset($form->mailto)) {
        $form->error = get_string('toerror', 'block_quickmailjpn');
    } else if (!$form->mailfrom) {
        $form->error = get_string('fromerror', 'block_quickmailjpn');
    } else if (!$form->subject) {
        $form->error = get_string('subjecterror', 'block_quickmailjpn');
    } else if (!$form->message) {
        $form->error = get_string('messageerror', 'block_quickmailjpn');
    }

    // no errors, then email
    if(!isset($form->error)) {
        $mailedto = array(); // holds all the userid of successful emails

        // get the correct formating for the emails
        $form->plaintxt = format_text_email($form->message, $form->format); // plain text
        $form->html = format_text($form->message, $form->format);        // html

        //$mail = new JPHPMailer();

        // run through each user id and send a copy of the email to him/her
        // not sending 1 email with CC to all user ids because emails were required to be kept private
        foreach ($form->mailto as $userid) {
            // 携帯メールはMoodle本体のメールとは別機能なのでブロックしない
            //if (!$courseusers[$userid]->emailstop) {

            $email = $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => $userfield_email_id));
            if (empty($email)) {
                // 未設定
                continue;
            }

            $status = $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => $userfield_status_id));
            if ($status != QuickMailJPN_State::CONFIRMED) {
                // 未チェック
                continue;
            }

            //send e-mail by JPHPMailer via PHPMailer
            $mail = new JPHPMailer();
            $mail->addTo($email);
            $mail->setFrom($form->mailfrom, fullname($USER));
            $mail->setSubject($form->subject);
            $bodyText  = $courseusers[$userid]->username.' '.fullname($courseusers[$userid]).get_string('san', 'block_quickmailjpn')."\n\n";
            $bodyText .= $form->plaintxt;

            $mail->setBody($bodyText);
            $mailresult = $mail->send();

            // checking for errors, if there is an error, store the name
            if (!$mailresult || (string) $mailresult == 'emailstop') {
                $form->error = get_string('emailfailerror', 'block_quickmailjpn');
                $form->usersfail['emailfail'][] = $courseusers[$userid]->lastname.', '.$courseusers[$userid]->firstname;
            } else {
                // success
                $mailedto[] = $userid;
            }
            //} else {
            //    // blocked email
            //    $form->error = get_string('emailfailerror', 'block_quickmailjpn');
            //    $form->usersfail['emailstop'][] = $courseusers[$userid]->lastname.', '.$courseusers[$userid]->firstname;
            //}
        }

        // cleanup - delete the uploaded file
        if (isset($um) and file_exists($um->get_new_filepath())) {
            unlink($um->get_new_filepath());
        }

        // prepare an object for the insert_record function
        $log = new stdClass;
        $log->courseid   = $course->id;
        $log->userid     = $USER->id;
        $log->mailto     = implode(',', $mailedto);
        $log->subject    = addslashes($form->subject);
        $log->message    = addslashes($form->message);
        $log->mailfrom   = addslashes($form->mailfrom);
        $log->timesent   = time();

        if (!$DB->insert_record('block_quickmailjpn_log', $log)) {
            error('Email not logged.');
        }

        if (!isset($form->error)) {  // if no emailing errors, we are done
            // inform of success and continue
            redirect("$CFG->wwwroot/course/view.php?id=$course->id", get_string('successfulemail', 'block_quickmailjpn'));
        }
    }
    // so people can use quotes.  It will display correctly in the subject input text box
    $form->subject = s($form->subject);

} else {
    // set them as blank
    $form = new \stdClass();
    $form->subject = $form->message = $form->format = $form->attachment = '';
    $form->mailfrom = $USER->email;
}

/// Create the table object for holding course users in the To section of email.html


$tblStr  = "<table border='0' cellspacing='2' cellpadding='2'>\n";
$tblStr .= "<tr>";
$tblStr .= "<th colspan='2' align='left'>".get_string('select', 'block_quickmailjpn')."</th>";
$tblStr .= "<th>".get_string('name', 'block_quickmailjpn')."</th>";
$tblStr .= "<th>".get_string('mobilephone', 'block_quickmailjpn')."</th>";
$tblStr .= "<th>".get_string('status', 'block_quickmailjpn')."</th>";
$tblStr .= "</tr>\n";

// フルネーム順にソートするために先にフルネームを取得してプロパティ追加
array_walk($courseusers, create_function('$u', '
        $u->fullname = fullname($u);
    '));

// 設定に従ってソート
// TODO: 拡張フィールドによるソートにも対応させる
switch ($CFG->block_quickmailjpn_sortorder) {
case 'firstname':
case 'lastname':
case 'fullname':
    $order = $CFG->block_quickmailjpn_sortorder;
    break;
default:
    $order = 'fullname';
}
$prev_encoding = mb_internal_encoding();
{
    mb_internal_encoding('utf-8'); // ソート順のエンコーディングを指定

    uasort($courseusers, create_function('$lhs,$rhs', '
            return strnatcasecmp($lhs->'.$order.', $rhs->'.$order.');
        '));
}
mb_internal_encoding($prev_encoding);

$i = 0;
foreach ($courseusers as $user) {
    $i++;
    $email  = $DB->get_field('user_info_data', 'data', array('userid' => $user->id, 'fieldid' => $userfield_email_id));
    $status = $DB->get_field('user_info_data', 'data', array('userid' => $user->id, 'fieldid' => $userfield_status_id));
    if (!$status) {
        $status = QuickMailJPN_State::NOT_SET;
    }
    if (isset($form->mailto) && in_array($user->id, $form->mailto)) {
        $checked = 'checked="checked"';
    } else {
        $checked = '';
    }
    switch ($status) {
    case QuickMailJPN_State::NOT_SET:
        $str_status = '<font color="blue">'.get_string($status, 'block_quickmailjpn').'</font>';
        break;
    case QuickMailJPN_State::CHECKING:
        $str_status = '<font color="red">'.get_string($status, 'block_quickmailjpn').'</font>';
        break;
    case QuickMailJPN_State::CONFIRMED:
        $str_status = '<font color="green">'.get_string($status, 'block_quickmailjpn').'</font>';
        break;
    default:
        $str_status = '';
    }
    if ($status == QuickMailJPN_State::CONFIRMED) {
        $disabled = '';
    } else {
        $disabled = 'disabled="disabled"';
    }
    $tblStr .= "<tr>\n";
    $tblStr .= "<td width='30'><input type='checkbox' $checked $disabled id='mailto$i' value='$user->id' name='mailto[]' /></td>\n";
    $tblStr .= "<td width='30'><label for='mailto$i'>$i</label></td>";
    $tblStr .= "<td><label for='mailto$i'>".htmlspecialchars($user->fullname)."</label></td>";
    $tblStr .= "<td><label for='mailto$i'>".htmlspecialchars($email)."</label></td>";
    $tblStr .= "<td><label for='mailto$i'>$str_status</label></td>";
    $tblStr .= "</tr>\n";
}

$tblStr .= "</table>\n";

// set up some strings
$readonly        = '';
$strchooseafile  = get_string('chooseafile', 'block_quickmailjpn');
$strquickmailjpn = get_string('blockname', 'block_quickmailjpn');

/// Header setup
$PAGE->set_title($course->fullname.': '.$strquickmailjpn);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strquickmailjpn);

echo $OUTPUT->header();

// print the email form START
echo $OUTPUT->heading($strquickmailjpn);

// error printing
if (isset($form->error)) {
    notify($form->error);
    if (isset($form->usersfail)) {
        $errorstring = '';

        if (isset($form->usersfail['emailfail'])) {
            $errorstring .= get_string('emailfail', 'block_quickmailjpn').'<br />';
            foreach($form->usersfail['emailfail'] as $user) {
                $errorstring .= $user.'<br />';
            }
        }

        if (isset($form->usersfail['emailstop'])) {
            $errorstring .= get_string('emailstop', 'block_quickmailjpn').'<br />';
            foreach($form->usersfail['emailstop'] as $user) {
                $errorstring .= $user.'<br />';
            }
        }
        notice($errorstring, "$CFG->wwwroot/course/view.php?id=$course->id", $course);
    }
}

$currenttab = 'compose';
include($CFG->dirroot.'/blocks/quickmailjpn/tabs.php');

echo $OUTPUT->box_start('center');
require($CFG->dirroot.'/blocks/quickmailjpn/email.html');
echo $OUTPUT->box_end();

if ($usehtmleditor) {
    use_html_editor('message');
}

echo $OUTPUT->footer($course);
