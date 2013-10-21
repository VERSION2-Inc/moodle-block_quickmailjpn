<?php
namespace ver2\quickmailjpn;

abstract class page {
	/**
	 *
	 * @var \moodle_url
	 */
	protected $url;
	/**
	 *
	 * @var \core_renderer
	 */
	protected $output;
	/**
	 *
	 * @var \stdClass
	 */
	protected $course;
	/**
	 *
	 * @var \context_course
	 */
	protected $context;

	/**
	 *
	 * @param string $url
	 */
	public function __construct($url) {
		global $DB, $PAGE, $OUTPUT;

		$courseid = required_param('course', PARAM_INT);
		$this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
		$this->context = \context_course::instance($this->course->id);

		require_login($courseid);
		$this->url = new \moodle_url($url, array('course' => $this->course->id));
		$PAGE->set_url($this->url);
		$PAGE->set_heading($this->course->fullname);

		$this->output = $OUTPUT;
	}

	public abstract function execute();

	/**
	 *
	 * @param string $title
	 */
	protected function set_title($title) {
		global $PAGE;

		$PAGE->set_title($this->course->shortname . ': ' . $title);
		$PAGE->navbar->add($title);
	}

	protected function require_manager() {
		require_capability('block/quickmailjpn:manage', $this->context);
	}
}
