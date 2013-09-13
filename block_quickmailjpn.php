<?php // $Id: block_quickmailjpn.php 4 2012-04-28 18:19:08Z yama $
/**
 * Quickmail - Allows teachers and students to email one another
 *      at a course level.  Also supports group mode so students
 *      can only email their group members if desired.  Both group
 *      mode and student access to Quickmail are configurable by
 *      editing a Quickmail instance.
 *
 * @author Mark Nielsen
 * @version $Id: block_quickmailjpn.php 4 2012-04-28 18:19:08Z yama $
 * @package quickmail
 **/

require_once dirname(__FILE__).'/constants.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/locallib.php';

use ver2\quickmailjpn\quickmailjpn as qm;

/**
 * This is the Quickmail block class.  Contains the necessary
 * functions for a Moodle block.  Has some extra functions as well
 * to increase its flexibility and useability
 *
 * @package quickmail
 * @todo Make a global config so that admins can set the defaults (default for student (yes/no) default for groupmode (select a groupmode or use the courses groupmode)) NOTE: make sure email.php and emaillog.php use the global config settings
 **/
class block_quickmailjpn extends block_list {

    function has_config() {return true;}

    /**
     * Prepare user_info_field
     *
     * @return void
     **/
//     function init_db() {
//     	global $DB;

//         if ($DB->record_exists('user_info_field', array('shortname' => QuickMailJPN_FieldName::EMAIL))
//             && $DB->record_exists('user_info_field', array('shortname' => QuickMailJPN_FieldName::STATUS))) {
//             return;
//         }

// 		$user_info_field = new stdClass();
// 		$user_info_field->datatype    = 'text';
// 		$user_info_field->categoryid  = 1;
// 		$user_info_field->required    = 0;
// 		$user_info_field->locked      = 0;
// 		$user_info_field->forceunique = 0;
// 		$user_info_field->signup      = 0;
// 		$user_info_field->defaultdata = '';
// 		$user_info_field->param1      = '30';
// 		$user_info_field->param2      = '2048';
// 		$user_info_field->param3      = '0';
// 		$user_info_field->param4      = NULL;
// 		$user_info_field->param5      = NULL;

//  		$max_sortorder = intval($DB->get_field_sql("SELECT MAX(sortorder) FROM {user_info_field}"));

// 		if (!$DB->record_exists('user_info_field', array('shortname' => QuickMailJPN_FieldName::EMAIL))) {
// 			// email カスタムフィールドが存在しないので作成
// 			$user_info_field->shortname   = QuickMailJPN_FieldName::EMAIL;
// 			$user_info_field->name        = 'quickmailJPN_mobile_email';
// 			$user_info_field->description = 'mobile_email';
// 			$user_info_field->visible     = 1;
// 			$user_info_field->sortorder   = ++$max_sortorder;
// 			if (!$DB->insert_record('user_info_field', $user_info_field)) {
// 				error('user_info_field can not be created.');
// 			}
// 		}

// 		if (!$DB->record_exists('user_info_field', array('shortname' => QuickMailJPN_FieldName::STATUS))) {
// 			// status カスタムフィールドが存在しないので作成
// 			$user_info_field->shortname   = QuickMailJPN_FieldName::STATUS;
// 			$user_info_field->name        = 'quickmailJPN_mobile_status';
// 			$user_info_field->description = 'mobile_status';
// 			$user_info_field->visible     = 0; // 非表示にしてユーザーが直接変更できないようにする
// 			$user_info_field->sortorder   = ++$max_sortorder;
// 			if (!$DB->insert_record('user_info_field', $user_info_field)) {
// 				error('user_info_field can not be created.');
// 			}
//     	}
//     }

    /**
     * Sets the block name and version number
     *
     * @return void
     **/
    function init() {
        $this->title = get_string('blockname', 'block_quickmailjpn');
    }

    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with an array of items, an array of icons, and a string for the footer
     **/
    function get_content() {
        global $USER, $CFG, $DB, $OUTPUT, $COURSE;

        if($this->content !== NULL) {
            return $this->content;
        }

        // 必要ならuser_info_fieldの追加処理
//         $this->init_db();

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->items = array();
        $this->content->icons = array();

        if (empty($this->instance)) {
            return $this->content;
        }

		$block_context = context_block::instance($this->instance->id);

		if (has_capability('block/quickmailjpn:cansend', $block_context)) {
			// 「作成」
			$this->content->items[] = ' <a href="'.$CFG->wwwroot.'/blocks/quickmailjpn/email.php'.
			                          '?id='.$this->course->id.'&amp;instanceid='.$this->instance->id.'">'.
			                          get_string('compose', 'block_quickmailjpn').'</a>';
			$this->content->icons[]
                = $OUTPUT->pix_icon('i/email', get_string('email'), 'moodle',
                                    array('width' => 16, 'height' => 16));

			// 「履歴」
			$this->content->items[] = ' <a href="'.$CFG->wwwroot.'/blocks/quickmailjpn/emaillog.php'.
			                          '?id='.$this->course->id.'&amp;instanceid='.$this->instance->id.'">'.
			                          get_string('history', 'block_quickmailjpn').'</a>';
			$this->content->icons[]
                = $OUTPUT->pix_icon('t/log', get_string('log', 'block_quickmailjpn'), 'moodle',
                                    array('width' => 16, 'height' => 16));

			$this->content->items[] = $OUTPUT->action_link(
					new moodle_url('/blocks/quickmailjpn/manageusers.php', ['course' => $COURSE->id]),
					qm::str('manageemailaddresses'));
			$this->content->icons[] = $OUTPUT->pix_icon('i/users', '');

			$this->content->items[] = \html_writer::empty_tag('hr');
			$this->content->icons[] = '';
		}
		if (has_capability('block/quickmailjpn:view', $block_context)) {
			if (!empty($this->instance->pinned) || $this->instance->pagetypepattern == 'course-view-*') {
				$filteropt = new stdClass;
				$filteropt->noclean = true;
			} else {
				$filteropt = null;
			}
			$explanation = isset($this->config->explanation)
			             ? format_text($this->config->explanation, FORMAT_HTML, $filteropt)
			             : get_string('explanation', 'block_quickmailjpn');
			// <p></p>を除去して余分な空白ができないようにする
			// (HTMLエディタが自動で付加してしまうので)
			$this->content->items[] = preg_replace('@<p>(.*?)</p>@is', '$1', $explanation);
			$this->content->icons[] = null;

			$email_address = null;
			$email_status = qm::STATUS_NOT_SET;
			if ($qmuser = qm::get_user($USER->id)) {
				$email_address = $qmuser->mobileemail;
				$email_status = $qmuser->mobileemailstatus;
			}

// 			$email_status = $DB->get_field_sql(
// 				'SELECT d.data FROM {user_info_data} d, {user_info_field} f'
// 				.' WHERE d.userid = :userid AND d.fieldid = f.id'
//                 .'   AND f.shortname = :shortname',
//                 array('userid' => $USER->id, 'shortname' => QuickMailJPN_FieldName::STATUS));
// 			$email_status = $qmuser->mobileemailstatus;
// 			if (!$email_status) {
// 				// データが見つからなければ「未設定」
// 				$email_status = QuickMailJPN_State::NOT_SET;
// 			}
            $classes = array(
                QuickMailJPN_State::NOT_SET => 'status-notset',
                QuickMailJPN_State::CHECKING => 'status-checking',
                QuickMailJPN_State::CONFIRMED => 'status-confirmed');
            $str_email_status = html_writer::tag(
                'span', get_string("user-$email_status", 'block_quickmailjpn'),
                array('class' => $classes[$email_status]));
			$str_email_status = get_string("block-$email_status", 'block_quickmailjpn', $str_email_status);

// 			$email_address = $DB->get_field_sql(
// 				'SELECT d.data FROM {user_info_data} d, {user_info_field} f'
// 				.' WHERE d.userid = :userid AND d.fieldid = f.id'
//                 .'   AND f.shortname = :shortname',
//                 array('userid' => $USER->id, 'shortname' => QuickMailJPN_FieldName::EMAIL));
// 			$email_address = $qmuser->mobileemail;
			if ($email_address) {
				$str_email_address = '[ '.$email_address.' ]<br />';
			} else {
				$str_email_address = '';
			}

			$this->content->items[] = '<div class="mymobilephone">'.
                                      get_string('mymobilephone', 'block_quickmailjpn').'<br />'.
			                          $str_email_address.
			                          '<span class="nowrap">'.
			                          '<a href="'.$CFG->wwwroot.'/blocks/quickmailjpn/'.
                                      'checkemail.php?id='.$this->course->id.'&amp;instanceid='.
                                      $this->instance->id.'">'.$str_email_status.'</a>'.
                                      '</span>'.
                                      '</div>';
			$this->content->icons[] = null;
		}

		return $this->content;
	}

    /**
     * Loads the course
     *
     * @return void
     **/
    function specialization() {
        global $COURSE;

        $this->course = $COURSE;

		// Override its title
		if (isset($this->config->title)) {
			$this->title = format_string($this->config->title);
		}
    }

    /**
     * Cleanup the history
     *
     * @return boolean
     **/
    function instance_delete() {
        global $DB;

        return $DB->delete_records('block_quickmailjpn_log', array('courseid' => $this->course->id));
    }

    /**
     * Set defaults for new instances
     *
     * @return boolean
     **/
    function instance_create() {
        $this->config = new stdClass();
        $this->config->groupmode = $this->course->groupmode;
        $pinned = (!isset($this->instance->pageid));
        return $this->instance_config_commit($pinned);
    }

    /**
     * Allows the block to be configurable at an instance level.
     *
     * @return boolean
     **/
    function instance_allow_config() {
        return true;
    }

    /**
     * Check to make sure that the current user is allowed to use Quickmail.
     *
     * @return boolean True for access / False for denied
     **/
    function check_permission() {
        return has_capability('block/quickmailjpn:cansend', context_block::instance($this->instance->id));
    }

    /**
     * Get the groupmode of Quickmail.  This function pays
     * attention to the course group mode force.
     *
     * @return int The group mode of the block
     **/
    function groupmode() {
        return groupmode($this->course, $this->config);
    }
}
