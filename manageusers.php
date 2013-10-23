<?php
namespace ver2\quickmailjpn;

require_once '../../config.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/locallib.php';
require_once $CFG->libdir . '/tablelib.php';
require_once $CFG->libdir . '/formslib.php';

use ver2\quickmailjpn\quickmailjpn as qm;

class page_manage_users extends page {
	public function execute() {
		$this->require_manager();

		switch (optional_param('mode', '', PARAM_ALPHA)) {
			case 'edit':
				$this->edit();
				break;
			default:
				$this->view();
		}
	}

	private function view() {
		global $DB;

		$this->set_title(qm::str('manageemailaddresses'));

		echo $this->output->header();
		echo $this->output->heading(qm::str('manageemailaddresses'));

		echo groups_print_course_menu($this->course, $this->url);

		$columns = array('name', 'mobileemail', 'mobileemailstatus', 'operations');
		$headers = array(
			qm::str('name'),
			qm::str('mobilephone'),
			qm::str('status'),
			''
		);

		$table = new \flexible_table('users');
		$table->attributes = array('class' => 'generaltable boxaligncenter');
		$table->define_baseurl($this->url);
		$table->define_columns($columns);
		$table->define_headers($headers);
		$table->setup();

		$statusstr = qm::get_status_options();
		$editicon = new \pix_icon('t/edit', 'edit');

		$groupid = 0;
		if (groups_get_course_groupmode($this->course)) {
			$groupid = groups_get_course_group($this->course, true);
		}
		$users = get_enrolled_users($this->context, '', $groupid, 'u.*', 'lastname, firstname');
		foreach ($users as $user) {
			$userid = $user->id;

			$mobileemail = '';
			$mobileemailstatus = qm::STATUS_NOT_SET;
			$qmuser = $DB->get_record(table::USERS, array('userid' => $user->id));
			if ($qmuser) {
				$mobileemail = $qmuser->mobileemail;
				$mobileemailstatus = $qmuser->mobileemailstatus;
			}
			$statuscell = \html_writer::tag('span', $statusstr[$mobileemailstatus],
					array('class' => 'status-'.$mobileemailstatus));
			$buttons = $this->output->action_icon(
					new \moodle_url($this->url, array('mode' => 'edit', 'userid' => $userid)), $editicon);
			$table->add_data(array(
					fullname($user),
					$mobileemail,
					$statuscell,
					$buttons
			));
		}
		$table->finish_output();

		echo $this->output->footer();
	}

	private function edit() {
		global $DB;

		$userid = required_param('userid', PARAM_INT);
		$user = $DB->get_record('user', array('id' => $userid));

		$form = new form_edit_user(null, (object)array(
				'course' => $this->course->id,
				'user' => $user
		));

		if ($form->is_cancelled()) {
			redirect($this->url);
		} else if ($form->is_submitted() && $form->is_validated()) {
			$data = $form->get_data();
			if (empty($data->mobileemail)) {
				$data->mobileemailstatus = qm::STATUS_NOT_SET;
			}
			qm::set_user($data);
			redirect($this->url);
		}

		$qmuser = qm::get_user($userid);
		$form->set_data($qmuser);

		$this->set_title(qm::str('manageemailaddresses'));

		echo $this->output->header();
		echo $this->output->heading(qm::str('manageemailaddresses'));
		$form->display();
		echo $this->output->footer();
	}
}

class form_edit_user extends \moodleform {
	protected function definition() {
		$f = $this->_form;

		$user = $this->_customdata->user;

		$f->addElement('hidden', 'mode', 'edit');
		$f->setType('mode', PARAM_ALPHA);
		$f->addElement('hidden', 'course', $this->_customdata->course);
		$f->setType('course', PARAM_INT);
		$f->addElement('hidden', 'userid', $user->id);
		$f->setType('userid', PARAM_INT);

		$f->addElement('header', 'emailhdr', qm::str('mobilephone'));

		$f->addElement('static', 'name', qm::str('name'), fullname($user));

		$f->addElement('text', 'mobileemail', qm::str('mobilephone'), array('size' => 40));
		$f->setType('mobileemail', PARAM_TEXT);

		$f->addElement('select', 'mobileemailstatus', qm::str('status'), qm::get_status_options());
		$f->setDefault('mobileemailstatus', qm::STATUS_NOT_SET);

		$this->add_action_buttons();
	}

    /**
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['mobileemail']) && !validate_email($data['mobileemail'])) {
            $errors['mobileemail'] = qm::str('invalidaddress');
        }
        return $errors;
    }
}

$page = new page_manage_users('/blocks/quickmailjpn/manageusers.php');
$page->execute();
