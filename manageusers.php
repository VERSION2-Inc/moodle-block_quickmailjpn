<?php
namespace ver2\quickmailjpn;

require_once '../../config.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/locallib.php';
require_once $CFG->libdir . '/tablelib.php';

class page_manage_users extends page {
	public function execute() {
		$this->require_manager();

		$this->view();
	}

	private function view() {
		global $DB;

		echo $this->output->header();

		$columns = ['name', 'mobileemail', 'mobileemailstatus', 'operations'];
		$headers=$columns;

		$table = new \flexible_table('users');
		$table->define_baseurl($this->url);
		$table->define_columns($columns);
		$table->define_headers($headers);
		$table->setup();

		$users = get_enrolled_users($this->context, '', 0, 'u.*', 'lastname, firstname');
		foreach ($users as $user) {
			$mobileemail = '';
			$mobileemailstatus = '';
			$qmuser = $DB->get_record(table::USERS, ['userid' => $user->id]);
			if ($qmuser) {
				$mobileemail = $qmuser->mobileemail;
				$mobileemailstatus = $qmuser->mobileemailstatus;
			}
			$table->add_data([
					fullname($user),
					$mobileemail,
					$mobileemailstatus,
					''
			]);
		}
		$table->finish_output();

		echo $this->output->footer();
	}
}

$page = new page_manage_users('/blocks/quickmailjpn/manageusers.php');
$page->execute();
