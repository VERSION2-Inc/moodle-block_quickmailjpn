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

/* @var $OUTPUT core_renderer */

require_once('../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/tablelib.php');

$id = required_param('id', PARAM_INT);    // course id
$action = optional_param('action', '', PARAM_ALPHA);
$instanceid = optional_param('instanceid', 0, PARAM_INT);

$PAGE->set_url('/blocks/quickmailjpn/emaillog.php');

$instance = new stdClass();

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

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
    if (has_capability('block/quickmailjpn:cansend', context_block::instance($instanceid))) {
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
	print_error('errornopermission', 'block_quickmailjpn');
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


/// Start printing everyting
$strquickmailjpn = get_string('blockname', 'block_quickmailjpn');
if (empty($pastemails)) {
    $disabled = 'disabled="disabled" ';
} else {
    $disabled = '';
}

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
		echo $OUTPUT->notification(get_string('deletesuccess', 'block_quickmailjpn'), 'notifysuccess');
	} else {
		echo $OUTPUT->notification(get_string('deletefail', 'block_quickmailjpn'));
	}
}

/// set table columns and headers
$tablecolumns = array('timesent', 'subject', 'action');
$tableheaders = array(get_string('date', 'block_quickmailjpn'), get_string('subject', 'forum'),
                      get_string('action', 'block_quickmailjpn'));

if ($action != 'confirm') {
	echo $OUTPUT->container_start('', 'tablecontainer');
}
$table = new flexible_table('bocks-quickmailjpn-emaillog');

/// define table columns, headers, and base url
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl(
		new moodle_url('/blocks/quickmailjpn/emaillog.php', array(
				'id' => $course->id,
				'instanceid' => $instanceid
		)));

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

$pastemails = $DB->get_records_sql($sql, $params, $table->get_page_start(), $table->get_page_size());
$viewicon = new pix_icon('t/preview', get_string('view'));
$deleteicon = new pix_icon('t/delete', get_string('delete'));
$confirmaction = new confirm_action(get_string('confirmdelete', 'block_quickmailjpn'));
foreach ($pastemails as $pastemail) {
	$row = array();
	$row[] = userdate($pastemail->timesent);
	$row[] = s($pastemail->subject);
	$row[] =
		$OUTPUT->action_icon(
				new moodle_url('/blocks/quickmailjpn/email.php', array(
						'id' => $course->id,
						'instanceid' => $instanceid,
						'emailid' => $pastemail->id,
						'action' => 'view'
				)),
				$viewicon
		)
		. $OUTPUT->action_icon(
				new moodle_url('/blocks/quickmailjpn/email.php', array(
						'id' => $course->id,
						'instanceid' => $instanceid,
						'sesskey' => sesskey(),
						'action' => 'dump',
						'emailid' => $pastemail->id
				)),
				$deleteicon,
				$confirmaction
		);
	$table->add_data($row);
}

$table->finish_output();
echo $OUTPUT->container_end(); // #tablecontainer

echo $OUTPUT->footer();
