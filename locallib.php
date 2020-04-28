<?php
defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/blocks/quickmailjpn/constants.php';
require_once $CFG->dirroot . '/blocks/quickmailjpn/jphpmailer.php';

function block_quickmailjpn_autoload($classname) {
	global $CFG;

	if (strpos($classname, 'ver2\\quickmailjpn') === 0) {
		$classname = preg_replace('/^ver2\\\\quickmailjpn\\\\/', '', $classname);

		$classdir = $CFG->dirroot . '/blocks/quickmailjpn/class/';
		$path = $classdir . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';
		if (is_readable($path)) {
			require $path;
		}
	}
}

spl_autoload_register('block_quickmailjpn_autoload');

if (!in_array('PHPMailerAutoload', spl_autoload_functions())) {
    if (!function_exists('PHPMailerAutoload')) {
        function PHPMailerAutoload($classname) {
            global $CFG;
            $path = $CFG->libdir.'/phpmailer/class.'.strtolower($classname).'.php';
            if (is_readable($path)) {
                require_once $path;
            }
        }
    }
    spl_autoload_register('PHPMailerAutoload');
}
function attachment_names($draft) {
    global $USER;

    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draft, 'id');

    $only_files = array_filter($files, function($file) {
        return !$file->is_directory() and $file->get_filename() != '.';
    });

    $only_names = function ($file) { return $file->get_filename(); };

    $only_named_files = array_map($only_names, $only_files);

    return implode(',', $only_named_files);
}
function process_attachments($context, $email, $table, $id) {
    $attachments = '';

    if (empty($email->attachment)) {
        return $attachments;
    }

    $fs = get_file_storage();

    $tree = $fs->get_area_tree(
        $context->id, 'block_quickmailjpn',
        'attachment_' . $table, $id
    );

    $base_url = "/$context->id/block_quickmailjpn/attachment_{$table}/$id";

    /**
     * @param string $filename name of the file for which we are generating a download link
     * @param string $text optional param sets the link text; if not given, filename is used
     * @param bool $plain if itrue, we will output a clean url for plain text email users
     *
     */
    $gen_link = function ($filename, $text = '', $plain=false) use ($base_url) {
        if (empty($text)) {
            $text = $filename;
        }

        $url = moodle_url::make_file_url('/pluginfile.php', "$base_url/$filename", true);

        //to prevent double encoding of ampersands in urls for our plaintext users,
        //we use the out() method of moodle_url
        //@see http://phpdocs.moodle.org/HEAD/moodlecore/moodle_url.html
        if($plain){
            return $url->out(false);
        }

        return html_writer::link($url, $text);
    };



    $link = $gen_link("{$email->timesent}_attachments.zip", 'download_all');

    //get a plain text version of the link
    //by calling gen_link with @param $plain set to true
    $tlink = $gen_link("{$email->timesent}_attachments.zip", '', true);

    $attachments .= "\n<br/>-------\n<br/>";
    $attachments .= 'Moodle Attachments<br/>'.$link;
    $attachments .= "\n<br/>".$tlink;
    $attachments .= "\n<br/>-------\n<br/>";
    $attachments .= 'Download File Contents'. "\n<br />";

    return $attachments . flatten_subdirs($tree, $gen_link);
}
function flatten_subdirs($tree, $gen_link, $level=0) {
    $attachments = $spaces = '';
    foreach (range(0, $level) as $space) {
        $spaces .= " - ";
    }
    foreach ($tree['files'] as $filename => $file) {
        $attachments .= $spaces . " " . $gen_link($filename) . "\n<br/>";
    }
    foreach ($tree['subdirs'] as $dirname => $subdir) {
        $attachments .= $spaces . " ". $dirname . "\n<br/>";
        $attachments .= flatten_subdirs($subdir, $gen_link, $level + 2);
    }

    return $attachments;
}
function get_jmailer() {
    global $CFG;

    $mailer = new JPHPMailer();

    $mailer->Version   = 'Moodle '.$CFG->version;         // mailer version
    $mailer->PluginDir = $CFG->libdir.'/phpmailer/';      // plugin directory (eg smtp plugin)
    #$mailer->CharSet   = 'UTF-8';

    // some MTAs may do double conversion of LF if CRLF used, CRLF is required line ending in RFC 822bis
    if (isset($CFG->mailnewline) and $CFG->mailnewline == 'CRLF') {
        $mailer->setLE("\r\n");
    } else {
        $mailer->setLE("\n");
    }

    if ($CFG->smtphosts == 'qmail') {
        $mailer->IsQmail();                              // use Qmail system

    } else if (empty($CFG->smtphosts)) {
        $mailer->IsMail();                               // use PHP mail() = sendmail

    } else {
        $mailer->IsSMTP();                               // use SMTP directly
        if (!empty($CFG->debugsmtp)) {
            $mailer->SMTPDebug = true;
        }
        $mailer->Host          = $CFG->smtphosts;        // specify main and backup servers
        $mailer->SMTPSecure    = $CFG->smtpsecure;       // specify secure connection protocol
        #$mailer->SMTPKeepAlive = $prevkeepalive;         // use previous keepalive

        if ($CFG->smtpuser) {                            // Use SMTP authentication
            $mailer->SMTPAuth = true;
            $mailer->Username = $CFG->smtpuser;
            $mailer->Password = $CFG->smtppass;
        }
    }

    return $mailer;
}
