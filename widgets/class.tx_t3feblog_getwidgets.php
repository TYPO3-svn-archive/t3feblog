<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Dmitry Dulepov (dmitry.dulepov@gmail.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * $Id: class.tx_t3blogwidgetdemo_getwidgets.php 33568 2010-05-27 11:51:24Z dmitry $
 *
 */

/**
  * This class is a hook to return widget information to t3blog.
  *
  * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
  * @package TYPO3
  * @subpackage tx_t3blogwidgetdemo_getwidgets
  */
class tx_t3feblog_getwidgets {

	/**
	 * Provides information about widgets in this extension
	 *
	 * @param array $unusedParams
	 * @return array
	 */
	public function getWidgets(array $unusedParams) {
		return array(
			'tx_t3feblog' => 'EXT:t3feblog/widgets/fepost/'
		);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3feblog/widgets/class.tx_t3feblog_getwidgets.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3feblog/widgets/class.tx_t3feblog_getwidgets.php']);
}

?>