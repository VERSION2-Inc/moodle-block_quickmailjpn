<?php // $Id: emaillog.php 4 2012-04-28 18:19:08Z yama $
/**
 * emaillog.php - displays a log (or history) of all emails sent by
 *      a specific in a specific course.  Each email log can be viewed
 *      or deleted.
 *
 * @todo Add a print option?
 * @author Mark Nielsen
 * @version $Id: emaillog.php 4 2012-04-28 18:19:08Z yama $
 * @package quickmailjpn
 **/
    
require_once('../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/tablelib.php');
    
$id = required_param('id', PARAM_INT);    // course id
$action = optional_param('action', '', PARAM_ALPHA);
$instanceid = optional_param('instanceid', 0, PARAM_INT);

$PAGE->set_url('/blocks/quickmailjpn/emaillog.php');

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
if (empty($instance)) {
    if (has_capability('block/quickmailjpn:cansend', get_context_instance(CONTEXT_BLOCK, $instanceid))) {
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
    
// log deleting happens here (NOTE: reporting is handled below)
$dumpresult = false;
if ($action == 'dump') {
    confirm_sesskey();
        
    // delete a single log or all of them
    if ($emailid = optional_param('emailid', 0, PARAM_INT)) {
        $dumpresult = $DB->delete_records('block_quickmailjpn_log', array('id' => $emailid));
    } else {
        $dumpresult = $DB->delete_records('block_quickmailjpn_log', array('userid' => $USER->id));
    }
}

/// set table columns and headers
$tablecolumns = array('timesent', 'subject', 'action');
$tableheaders = array(get_string('date', 'block_quickmailjpn'), get_string('subject', 'forum'),
                      get_string('action', 'block_quickmailjpn'));

$table = new flexible_table('bocks-quickmailjpn-emaillog');

/// define table columns, headers, and base url
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($CFG->wwwroot.'/blocks/quickmailjpn/emaillog.php?id='.$course->id.'&amp;instanceid='.$instanceid);

/// table settings
$table->sortable(true, 'timesent', SORT_DESC);
$table->no_sorting('action');
$table->collapsible(true);
$table->initialbars(false);
$table->pageable(true);

/// column styles (make sure date does not wrap) NOTE: More table styles in styles.php
$table->column_style('timesent', 'width', '40%');
$table->column_style('timesent', 'white-space', 'nowrap');

/// set attributes in the table tag
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'emaillog');
$table->set_attribute('class', 'generaltable generalbox');
$table->set_attribute('align', 'center');
$table->set_attribute('width', '80%');

$table->setup();  
    
/// SQL
$sql = "SELECT * 
              FROM {block_quickmailjpn_log}
             WHERE courseid = :courseid 
               AND userid = :userid ";
$params = array('courseid' => $course->id, 'userid' => $USER->id);

$where = $table->get_sql_where();
if ($where[0]) {
    $sql .= 'AND '.$where[0];
    $params += $where[1];
}

$sql .= ' ORDER BY '. $table->get_sql_sort();

/// set page size
$total = $DB->count_records('block_quickmailjpn_log', array('courseid' => $course->id, 'userid' => $USER->id));
$table->pagesize(10, $total);

if ($pastemails = $DB->get_records_sql($sql, $params, $table->get_page_start(), $table->get_page_size())) {
    foreach ($pastemails as $pastemail) {
        $table->add_data( array(userdate($pastemail->timesent),
                                s($pastemail->subject),
                                "<a href=\"email.php?id=$course->id&amp;instanceid=$instanceid&amp;emailid=$pastemail->id&amp;action=view\">".
                                $OUTPUT->pix_icon('i/search', get_string('view')).
                                "<a href=\"emaillog.php?id=$course->id&amp;instanceid=$instanceid&amp;sesskey=$USER->sesskey&amp;action=dump&amp;emailid=$pastemail->id\"".
                                ' onclick="return confirm(\''.get_string('confirmdelete', 'block_quickmailjpn').'\');" />'.
                                $OUTPUT->pix_icon('t/delete', get_string('delete')).
                                '</a>'));
        // TODO: ↑JavaScriptオフの場合用に確認ページをはさむ
    }
}
    
/// Start printing everyting
$strquickmailjpn = get_string('blockname', 'block_quickmailjpn');
if (empty($pastemails)) {
    $disabled = 'disabled="disabled" ';
} else {
    $disabled = '';
}
$button = "<form method=\"post\" action=\"$CFG->wwwroot/blocks/quickmailjpn/emaillog.php\">
               <input type=\"hidden\" name=\"id\" value=\"$course->id\" />
               <input type=\"hidden\" name=\"instanceid\" value=\"$instanceid\" />
               <input type=\"hidden\" name=\"sesskey\" value=\"".sesskey().'" />
               <input type="hidden" name="action" value="confirm" />
               <input type="submit" name="submit" value="'.get_string('clearhistory', 'block_quickmailjpn')."\" $disabled/>
               </form>";
    
/// Header setup
$PAGE->set_title($course->fullname.': '.$strquickmailjpn);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strquickmailjpn);

echo $OUTPUT->header();

echo $OUTPUT->heading($strquickmailjpn);
    
$currenttab = 'history';
include($CFG->dirroot.'/blocks/quickmailjpn/tabs.php');
    
/// delete reporting happens here
if ($action == 'dump') {
    if ($dumpresult) {
        notify(get_string('deletesuccess', 'block_quickmailjpn'), 'notifysuccess');
    } else {
        notify(get_string('deletefail', 'block_quickmailjpn'));
    }
}

if ($action == 'confirm') {
    notice_yesno(get_string('areyousure', 'block_quickmailjpn'), 
                 "$CFG->wwwroot/blocks/quickmailjpn/emaillog.php?id=$course->id&amp;instanceid=$instanceid&amp;sesskey=".sesskey()."&amp;action=dump",
                 "$CFG->wwwroot/blocks/quickmailjpn/emaillog.php?id=$course->id&amp;instanceid=$instanceid");
} else {
    echo '<div id="tablecontainer">';
    $table->print_html();
    echo '</div>';
}

echo $OUTPUT->footer();
