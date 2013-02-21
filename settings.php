<?php  //$Id: settings.php 4 2012-04-28 18:19:08Z yama $

$settings->add(
	new admin_setting_configtext('block_quickmailjpn_email', 
		get_string('adminemailaddress', 'block_quickmailjpn'),
    	get_string('adminemailaddressexplanation', 'block_quickmailjpn'), 
    	$CFG->noreplyaddress,
    	PARAM_TEXT
	)
);
$settings->add(
	new admin_setting_configtext('block_quickmailjpn_email_subject', 
		get_string('adminmailsubject', 'block_quickmailjpn'),
    	'', 
    	get_string('adminmailsubjecttext', 'block_quickmailjpn'), 
    	PARAM_TEXT
	)
);
$settings->add(
	new admin_setting_configtextarea('block_quickmailjpn_email_message', 
		get_string('adminmailmessage', 'block_quickmailjpn'),
    	'', 
    	get_string('adminmailmessagetext', 'block_quickmailjpn'), 
    	PARAM_TEXT
	)
);

$settings->add(
	new admin_setting_configselect('block_quickmailjpn_sortorder',
		get_string('sortorder', 'block_quickmailjpn'),
		get_string('sortorderdesc', 'block_quickmailjpn'),
		'fullname',
		array(
			'fullname'  => get_string('fullname'),
			'firstname' => get_string('firstname'),
			'lastname'  => get_string('lastname')
		)
	)
);

?>