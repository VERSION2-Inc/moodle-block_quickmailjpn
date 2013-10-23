<?php
namespace ver2\quickmailjpn;

class quickmailjpn {
	const COMPONENT = 'block_quickmailjpn';

	const TABLE_USERS = 'block_quickmailjpn_users';

	const STATUS_NOT_SET = 'notyet';
	const STATUS_CHECKING = 'checking';
	const STATUS_CONFIRMED = 'confirmed';

	/**
	 *
	 * @param string $identifier
	 * @param string $a
	 * @return string
	 */
	public static function str($identifier, $a = null) {
		return get_string($identifier, self::COMPONENT, $a);
	}

	/**
	 *
	 * @param int $userid
	 * @return \stdClass
	 */
	public static function get_user($userid) {
		global $DB;

		return $DB->get_record(self::TABLE_USERS, array('userid' => $userid));
	}

	public static function get_user_field($userid, $field) {
		global $DB;

		return $DB->get_field(self::TABLE_USERS, $field, array('userid' => $userid));
	}

	/**
	 *
	 * @param array|\stdClass $user
	 * @throws \coding_exception
	 */
	public static function set_user($user) {
		global $DB;

		if (is_array($user)) {
			$user = (object)$user;
		}

		if (empty($user->userid)) {
			throw new \coding_exception('useridを指定してください。');
		}

		if ($olduser = $DB->get_record(self::TABLE_USERS, array('userid' => $user->userid))) {
			$user->id = $olduser->id;
			$DB->update_record(self::TABLE_USERS, $user);
		} else {
			$DB->insert_record(self::TABLE_USERS, $user);
		}
	}

	public static function set_user_field($userid, $field, $value) {
		global $DB;

		if ($user = $DB->get_record(self::TABLE_USERS, array('userid' => $userid))) {
			$user->$field = $value;
			$DB->update_record(self::TABLE_USERS, $user);
		} else {
			$user = (object)array(
					'userid' => $userid,
					$field => $value
			);
			$DB->insert_record(self::TABLE_USERS, $user);
		}
	}

	public static function get_email($userid) {
		if ($user = $this->get_user($userid)) {
			return $user->mobileemail;
		}
		return false;
	}

	public static function set_email($userid, $email) {
		global $DB;

		if ($this->get_user($userid)) {
			$DB->set_field(self::TABLE_USERS, 'mobileemail', $email, array('userid' => $userid));
		} else {
			$user = (object)array(
					'userid' => $userid,
					'mobileemail' => $email
			);
			$DB->insert_record(self::TABLE_USERS, $user);
		}
	}

	/**
	 *
	 * @return string[]
	 */
	public static function get_status_options() {
		return array(
				self::STATUS_CHECKING => self::str('checking'),
				self::STATUS_CONFIRMED => self::str('confirmed'),
				self::STATUS_NOT_SET => self::str('notyet')
		);
	}

	/**
	 *
	 * @param int $len
	 * @return string
	 */
	public static function make_password($len) {
		srand((float)microtime() * 54234853);

		$pstr = 'abcdefghkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ2345679';
		$pary = preg_split('//', $pstr, 0, PREG_SPLIT_NO_EMPTY);

		$pw = '';
		for ($i = 0; $i < $len; $i++) {
			// パスワード文字列を生成
			$pw .= $pary[array_rand($pary, 1)];
		}
		return $pw;
	}
}
