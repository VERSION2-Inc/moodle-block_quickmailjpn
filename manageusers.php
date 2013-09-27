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

		$columns = ['name', 'mobileemail', 'mobileemailstatus', 'operations'];
		$headers = [
			qm::str('name'),
			qm::str('mobilephone'),
			qm::str('status'),
			''
		];

		$table = new \flexible_table('users');
		$table->attributes = ['class' => 'generaltable boxaligncenter'];
		$table->define_baseurl($this->url);
		$table->define_columns($columns);
		$table->define_headers($headers);
		$table->setup();

		$statusstr = qm::get_status_options();
		$editicon = new \pix_icon('t/edit', 'edit');

		$users = get_enrolled_users($this->context, '', 0, 'u.*', 'lastname, firstname');
		foreach ($users as $user) {
			$userid = $user->id;

			$mobileemail = '';
			$mobileemailstatus = qm::STATUS_NOT_SET;
			$qmuser = $DB->get_record(table::USERS, ['userid' => $user->id]);
			if ($qmuser) {
				$mobileemail = $qmuser->mobileemail;
				$mobileemailstatus = $qmuser->mobileemailstatus;
			}
			$buttons = $this->output->action_icon(
					new \moodle_url($this->url, ['mode' => 'edit', 'userid' => $userid]), $editicon);
			$table->add_data([
					fullname($user),
					$mobileemail,
					$statusstr[$mobileemailstatus],
					$buttons
			]);
		}
		$table->finish_output();

		echo $this->output->footer();
	}

	private function edit() {
		global $DB;

		$userid = required_param('userid', PARAM_INT);
		$user = $DB->get_record('user', ['id' => $userid]);

		$form = new form_edit_user(null, (object)[
				'course' => $this->course->id,
				'user' => $user
		]);

		if ($form->is_cancelled()) {
			redirect($this->url);
		} else if ($form->is_submitted()) {
			$data = $form->get_data();
			qm::set_user($data);
			redirect($this->url);
		}

		$qmuser = quickmailjpn::get_user($userid);
		$form->set_data($qmuser);

		$this->set_title(qm::str('manageemailaddresses'));

		echo $this->output->header();
		$form->display();
		echo $this->output->footer();
	}
}

class form_edit_user extends \moodleform {
	protected function definition() {
		$f = $this->_form;

		$f->addElement('hidden', 'mode', 'edit');
		$f->setType('mode', PARAM_ALPHA);
		$f->addElement('hidden', 'course', $this->_customdata->course);
		$f->setType('course', PARAM_INT);
		$f->addElement('hidden', 'userid', $this->_customdata->user->id);
		$f->setType('userid', PARAM_INT);

		$f->addElement('static', 'name', qm::str('name'), fullname($this->_customdata->user));

		$f->addElement('text', 'mobileemail', qm::str('mobilephone'));
		$f->setType('mobileemail', PARAM_EMAIL);

		$f->addElement('select', 'mobileemailstatus', qm::str('status'), qm::get_status_options());
		$f->setDefault('mobileemailstatus', qm::STATUS_NOT_SET);

		$this->add_action_buttons();
	}
}

$page = new page_manage_users('/blocks/quickmailjpn/manageusers.php');
$page->execute();
