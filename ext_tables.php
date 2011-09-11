<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
t3lib_extMgm::addStaticFile($_EXTKEY,'static/t3feblog/', 'FE blog widgets for T3BLOG');

$tempColumns = array (
	'tx_t3feblog_authorname' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:t3feblog/locallang_db.xml:tx_t3blog_post.tx_t3feblog_authorname',		
		'config' => array (
			'type' => 'input',	
			'size' => '30',	
			'eval' => 'trim',
		)
	),
	'tx_t3feblog_authoremail' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:t3feblog/locallang_db.xml:tx_t3blog_post.tx_t3feblog_authoremail',		
		'config' => array (
			'type' => 'input',	
			'size' => '30',	
			'eval' => 'trim',
		)
	),
	'tx_t3feblog_authorurl' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:t3feblog/locallang_db.xml:tx_t3blog_post.tx_t3feblog_authorurl',		
		'config' => array (
			'type' => 'input',	
			'size' => '30',	
			'eval' => 'trim',
		)
	),
);


t3lib_div::loadTCA('tx_t3blog_post');
t3lib_extMgm::addTCAcolumns('tx_t3blog_post',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tx_t3blog_post','tx_t3feblog_authorname;;;;1-1-1, tx_t3feblog_authoremail, tx_t3feblog_authorurl');
?>