<?php // $Id: tabs.php 4 2012-04-28 18:19:08Z yama $
/**
 * Tabs for QuickmailJPN
 *
 * @author Mark Nielsen
 * @version $Id: tabs.php 4 2012-04-28 18:19:08Z yama $
 * @package quickmailjpn
 **/

    if (empty($course)) {
        error('Programmer error: cannot call this script without $course set');
    }
    if (!isset($instanceid)) {
        $instanceid = 0;
    }
    if (empty($currenttab)) {
        $currenttab = 'compose';
    }

    $rows = array();
    $row = array();

    $row[] = new tabobject('compose', "$CFG->wwwroot/blocks/quickmailjpn/email.php?id=$course->id&amp;instanceid=$instanceid", get_string('compose', 'block_quickmailjpn'));
    $row[] = new tabobject('history', "$CFG->wwwroot/blocks/quickmailjpn/emaillog.php?id=$course->id&amp;instanceid=$instanceid", get_string('history', 'block_quickmailjpn'));
    $rows[] = $row;

    print_tabs($rows, $currenttab);
?>