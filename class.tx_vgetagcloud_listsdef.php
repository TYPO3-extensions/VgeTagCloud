<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Francois Suter <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * Various itemsProcFuncs for the 'vge_tagcloud' pi1 FlexForm.
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_vgetagcloud
 */
class tx_vgetagcloud_listsdef {

	/**
	 * This method reads the TCA and adds all table names to the list of items
	 *
	 * @param	array	$config: content element configuration
	 *
	 * @return	array	content element configuration with dynamically added items
	 */
	function getTables($config) {
		global $TCA;
		$elements = array();
		foreach ($TCA as $tableKey => $tableTCA) {
			$elements[] = array($GLOBALS['LANG']->sL($tableTCA['ctrl']['title']), $tableKey);
		}
		$config['items'] = array_merge($config['items'], $elements);
		return $config;
	}

	/**
	 * This method reads the TCA for a given table and adds all table fields to the list of items
	 *
	 * @param	array	$config: content element configuration
	 *
	 * @return	array	content element configuration with dynamically added items
	 */
	function getFields($config) {
		global $TCA;

// Read the reference table's name from the FlexForm and load its full TCA

		$table = '';
		if (!empty($config['row']['pi_flexform'])) {
			$flexFormContent = t3lib_div::xml2array($config['row']['pi_flexform']);
			$table = $flexFormContent['data']['sDEF']['lDEF']['referenceTable']['vDEF'];
		}

// If a table has been defined, loop on all its columns and add them to the list of items

		if (!empty($table)) {
			t3lib_div::loadTCA($table);
			$elements = array();
			foreach ($TCA[$table]['columns'] as $columnKey => $columnInfo) {
				$elements[] = array($GLOBALS['LANG']->sL($columnInfo['label']), $columnKey);
			}
			$config['items'] = array_merge($config['items'], $elements);
		}
		return $config;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_tagcloud/class.tx_vgetagcloud_listsdef.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_tagcloud/class.tx_vgetagcloud_listsdef.php']);
}
?>