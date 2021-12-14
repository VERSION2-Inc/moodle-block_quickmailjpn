<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_quickmail
 * @copyright  2008-2017 Louisiana State University
 * @copyright  2008-2017 Adam Zapletal, Chad Mazilly, Philip Cali, Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

class   email_form extends moodleform {
    public function definition() {
        global $CFG, $USER, $COURSE, $OUTPUT;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_TEXT);

        $mform->addElement('hidden', 'instanceid', $this->_customdata['instanceid']);
        $mform->setType('instanceid',PARAM_INT);

        $mform->addElement('html','<div align="center"><table border="0" cellpadding="5"><tr valign="top"><td align="right"><strong>To:</strong></td><td align="left"> 
               <a href="javascript:void(0);" onclick="block_quickmailjpn_toggle(true, 1, 0);">'. get_string('selectall').'</a>
             / <a href="javascript:void(0);" onclick="block_quickmailjpn_toggle(false, 1, 0);">'. get_string('deselectall').'</a> <br /><br />
            '.$this->_customdata['tblStr'].'</td></tr></table></div>');

        $mform->addElement('text', 'mailfrom', get_string('from'),array('size'=>60));
        $mform->setType('mailfrom', PARAM_TEXT);
        $mform->setDefault('mailfrom',$this->_customdata['mailfrom']);

        $mform->addElement('text', 'subject', get_string('subject', 'forum'),array('size'=>60));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addElement(
            'filemanager', 'attachments', get_string('attachments', 'block_quickmailjpn'),
            null, array('subdirs' => 1, 'accepted_types' => '*')
        );
        $mform->addElement('editor', 'message_editor', get_string('message', 'forum'),
            array(), $this->_customdata['editor_options']);

        $mform->addElement('html','<div align="center"><input type="checkbox" name="sendmecopy" id="sendmecopy"><label for="sendmecopy">'.get_string('sendmecopy', 'block_quickmailjpn').'</label></div>');
        $buttons = array();
        $buttons[] =& $mform->createElement('Cancel');
        $buttons[] =& $mform->createElement('submit', 'send', get_string('sendemail', 'block_quickmailjpn'));


        $mform->addGroup($buttons, 'buttons', '', array(' '), false);
    }
}
