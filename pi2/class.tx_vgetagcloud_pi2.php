<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2014 Francois Suter <typo3@cobweb.ch>
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
 * Plugin to display list of pages for the 'vge_tagcloud' extension.
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_vgetagcloud
 */
class tx_vgetagcloud_pi2 extends tslib_pibase {
	var $prefixId      = 'tx_vgetagcloud_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_vgetagcloud_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'vge_tagcloud';	// The extension key.
	var $pi_checkCHash = true;

	/**
	 * This method display a list of pages according to a selected keyword and its associated pages
	 * as passed from the pi1 plugin
	 *
	 * @param	string		$content: The plugin content
	 * @param	array		$conf: The plugin configuration
	 * @return	string		The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->init($conf);

			// Get the selected keyword and apply stdWrap, if any
		$keyword = (empty($this->piVars['keyword'])) ? '' : htmlspecialchars($this->piVars['keyword']);
		if (isset($this->conf['keyword.'])) {
			$keyword = $this->cObj->stdWrap($keyword, $this->conf['keyword.']);
		}

		if (empty($this->piVars['pages'])) {
			// If no pages were passed, display error message

			$message = $this->pi_getLL('no_pages');
			$message = sprintf($message, $keyword);
			if (isset($this->conf['message.'])) {
				$message = $this->cObj->stdWrap($message, $this->conf['message.']);
			}
			$content = $message;
		} else {
			// Else display resulting list of pages

			$pages = t3lib_div::trimExplode('_', $this->piVars['pages'], 1);

				// Get result message and apply stdWrap, if any
			if (count($pages) == 1) {
				$message = $this->pi_getLL('related_single_page');
					// Message may contain 1 marker
				$message = sprintf($message, $keyword);
			} else {
				$message = $this->pi_getLL('related_pages');
					// Message may contain 2 markers
				$message = sprintf($message, count($pages), $keyword);
			}
			if (isset($this->conf['message.'])) {
				$message = $this->cObj->stdWrap($message, $this->conf['message.']);
			}
			$content = $message;

				// Load page number as a comma-separated list in the data of the cObj
			$this->cObj->data['tag_pages'] = implode(',', $pages);

				// Perform rendering of the list of pages, as a cObj
			$content .= $this->cObj->cObjGetSingle($this->conf['results'], $this->conf['results.']);
		}

			// Wrap the whole result, with baseWrap if defined, else with standard pi_wrapInBaseClass() call
		if (isset($this->conf['baseWrap.'])) {
			return $this->cObj->stdWrap($content, $this->conf['baseWrap.']);
		} else {
			return $this->pi_wrapInBaseClass($content);
		}
	}

	/**
	 * This method performs various initialisations
	 *
	 * @param	array		$conf: plugin configuration, as received by the main() method
	 * @return	void
	 */
	function init($conf) {
			// Base configuration is equal the the plugin's TS setup
		$this->conf = $conf;

			// Load localized strings
		$this->pi_loadLL();
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_tagcloud/pi2/class.tx_vgetagcloud_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_tagcloud/pi2/class.tx_vgetagcloud_pi2.php']);
}
?>