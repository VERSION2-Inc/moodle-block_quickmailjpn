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

function get_jmailer() {
    global $CFG;

    $mailer = new JPHPMailer();

    $mailer->Version   = 'Moodle '.$CFG->version;         // mailer version
    $mailer->PluginDir = $CFG->libdir.'/phpmailer/';      // plugin directory (eg smtp plugin)
    #$mailer->CharSet   = 'UTF-8';

    // some MTAs may do double conversion of LF if CRLF used, CRLF is required line ending in RFC 822bis
    if (isset($CFG->mailnewline) and $CFG->mailnewline == 'CRLF') {
        $mailer->LE = "\r\n";
    } else {
        $mailer->LE = "\n";
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
