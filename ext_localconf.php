<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['getWidgets'][$_EXTKEY . '_1'] = 'EXT:' . $_EXTKEY . '/widgets/class.tx_t3feblog_getwidgets.php:tx_t3feblog_getwidgets->getWidgets';

?>