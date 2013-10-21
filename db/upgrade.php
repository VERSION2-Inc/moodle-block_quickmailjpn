<?php
defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_block_quickmailjpn_upgrade($oldversion = 0) {
	global $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2013092700) {

        // Define table block_quickmailjpn_users to be created.
        $table = new xmldb_table('block_quickmailjpn_users');

        // Adding fields to table block_quickmailjpn_users.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('mobileemail', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('mobileemailstatus', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('mobileemailauthkey', XMLDB_TYPE_CHAR, '16', null, null, null, null);

        // Adding keys to table block_quickmailjpn_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_quickmailjpn_users.
        $table->add_index('userid', XMLDB_INDEX_UNIQUE, array('userid'));

        // Conditionally launch create table for block_quickmailjpn_users.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

		$emailfieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'quickmailJPNmobileemail'));
		$statusfieldid = $DB->get_field('user_info_field', 'id', array('shortname' => 'quickmailJPNmobilestatus'));

		if ($emailfieldid && $statusfieldid) {
			$oldemails = $DB->get_records_sql('
					SELECT uid.userid, uid.data
					FROM {user_info_data} uid
					WHERE uid.fieldid = :fieldid
					',
					array('fieldid' => $emailfieldid)
			);
			$oldstatuses = $DB->get_records_sql('
					SELECT uid.userid, uid.data
					FROM {user_info_data} uid
					WHERE uid.fieldid = :fieldid
					',
					array('fieldid' => $statusfieldid)
			);
			foreach ($oldemails as $oldemail) {
				if (empty($oldemail->data)) {
					continue;
				}

				$row = new \stdClass();
				$row->userid = $oldemail->userid;
				$row->mobileemail = $oldemail->data;
				if (isset($oldstatuses[$oldemail->userid])) {
					$row->mobileemailstatus = $oldstatuses[$oldemail->userid]->data;
				}
				$DB->insert_record('block_quickmailjpn_users', $row);
			}

			$DB->delete_records('user_info_data', array('fieldid' => $emailfieldid));
			$DB->delete_records('user_info_data', array('fieldid' => $statusfieldid));
			$DB->delete_records('user_info_field', array('id' => $emailfieldid));
			$DB->delete_records('user_info_field', array('id' => $statusfieldid));
		}

        // Quickmailjpn savepoint reached.
        upgrade_block_savepoint(true, 2013092700, 'quickmailjpn');
    }

    return $result;
}
