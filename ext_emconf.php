<?php

########################################################################
# Extension Manager/Repository config file for ext "t3feblog".
#
# Auto generated 09-09-2011 20:52
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Frontend blog',
	'description' => 'This extension adds new widgets to T3BLOG. They allow to submit blog posts at frontend.',
	'category' => 'fe',
	'author' => 'Dirk Wenzel',
	'author_email' => 't3feblog@sinnzeichen.com',
	'shy' => '',
	'dependencies' => 't3blog',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'sinnzeichen.com',
	'version' => '0.1.7',
	'constraints' => array(
		'depends' => array(
			't3blog' => '1.1.1-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:9:"ChangeLog";s:4:"b79e";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"4465";s:14:"ext_tables.php";s:4:"b82f";s:14:"ext_tables.sql";s:4:"82fe";s:16:"locallang_db.xml";s:4:"a58c";s:19:"doc/wizard_form.dat";s:4:"de9b";s:20:"doc/wizard_form.html";s:4:"01bb";s:29:"static/t3feblog/constants.txt";s:4:"151b";s:25:"static/t3feblog/setup.txt";s:4:"f07d";s:40:"widgets/class.tx_t3feblog_getwidgets.php";s:4:"3ce2";s:29:"widgets/fepost/adminemail.txt";s:4:"58b7";s:36:"widgets/fepost/class.tx_t3feblog.php";s:4:"c8bd";s:28:"widgets/fepost/locallang.xml";s:4:"007d";}',
	'suggests' => array(
	),
);

?>