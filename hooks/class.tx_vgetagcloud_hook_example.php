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
 * This class is an example of using the hooks provided within the vge_tagcloud extension
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_vgetagcloud
 */
class tx_vgetagcloud_hook_example {
	var $glossary = array();

	/**
	 * The constructor is used to build a list of keywords from the sg_glossary extension
	 * This list is used in the processTagData() example method below
	 */
	function __construct() {
		if (t3lib_extMgm::isLoaded('sg_glossary')) {
				// Note that this is very rough. Enable fields should be tested for.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_sgglossary_entries', '');
				// Assemble associative array to link keywords to their uid
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->glossary[strtolower($row['word'])] = $row['uid'];
			}
		}
	}

	/**
	 * This method receives the data as extracted from the database and is expected to return
	 * an array of all words inside that data. No data transformation is expected at that point
	 * (e.g. removal of duplicates or case transformation happens at a later point)
	 *
	 * @param mixed $data Database field containing the data from which to extract the keywords
	 * @param array $conf TS configuration of the calling object
	 * @param array $keywords Array of keywords passed as a reference (in case several hooks are called in a row)
	 *
	 * @return    void
	 */
	function extractKeywords($data, $conf, &$keywords) {

		// This example hook extracts keywords from the "field_caption" field of a localised FCE
		// First transform FCE's xml to an array
		$flexformData = t3lib_div::xml2array($data);

		// Translate current language number to pointer for flexform
		if (!empty($flexformData['data']['sDEF']['lDEF']['field_caption'])) {
			switch ($GLOBALS['TSFE']->sys_language_content) {
				case 1:
					$langField = 'vEN';
					break;
				case 2:
					$langField = 'vDE';
					break;
				default:
					$langField = 'vDEF';
			}
			$theField = $flexformData['data']['sDEF']['lDEF']['field_caption'][$langField];

			// Extract keywords using the same methods as inside the plugin
			if (empty($conf['splitChar'])) {
				$rawKeywords = preg_split('/'.addcslashes($conf['splitWords'],"'/").'/',strip_tags($theField));
				// Exclude empty or blank strings
				foreach ($rawKeywords as $theKeyword) {
					$theKeyword = trim($theKeyword);
					if (!empty($theKeyword)) $keywords[] = $theKeyword;
				}
			}
			else {
				$keywords = t3lib_div::trimExplode($conf['splitChar'], $theField, 1);
			}
		}
	}

	/**
	 * This method receives an array containing all the keywords found by the vge_tagcloud extension
	 * before any processing has been done on the keywords, except for case conversion
	 *
	 * In this example, we just filter out any word containing 2 letters or less
	 *
	 * NOTE: this hook is not useful anymore, since this feature was included in the code base in version 1.7.0.
	 *
	 * @param array $keywords List of keywords
	 * @param tx_vgetagcloud_pi1 $callingObj Callback to the calling object
	 *
	 * @return	array	transformed list of keywords
	 */
	function postProcessRawKeywords($keywords, tx_vgetagcloud_pi1 $callingObj) {
		$transformedKeywords = array();
		foreach ($keywords as $aKeyword) {
			if ($GLOBALS['TSFE']->csConvObj->strlen($GLOBALS['TSFE']->renderCharset,$aKeyword) > 2) {
				$transformedKeywords[] = $aKeyword;
			}
		}
		return $transformedKeywords;
	}

	/**
	 * This method receives an array containing all the keywords just before they are used for display
	 * Note that this array is associative, with the keywords as keys and their absolute weights as values
	 * This association must absolutely be preserved for the extension to work properly!
	 *
	 * In this example, we sort the keywords by string length
	 *
	 * @param array $keywords List of keywords
	 * @param tx_vgetagcloud_pi1 $callingObj Callback to the calling object
	 *
	 * @return	array	transformed list of keywords
	 */
	function postProcessFinalKeywords($keywords, tx_vgetagcloud_pi1 $callingObj) {
		uksort($keywords, array('tx_vgetagcloud_hook_example','sortKeywordsByLength'));
		return $keywords;
	}

	/**
	 * This method is a helper sorting function to sort an array according to the length of its keys
	 *
	 * @param string $a First item key
	 * @param string $b Second item key
	 *
	 * @return integer -1, 0 or 1 depending in result of comparison
	 */
	function sortKeywordsByLength($a,$b) {
		$aLength = $GLOBALS['TSFE']->csConvObj->strlen($GLOBALS['TSFE']->renderCharset,$a);
		$bLength = $GLOBALS['TSFE']->csConvObj->strlen($GLOBALS['TSFE']->renderCharset,$b);
		if ($aLength == $bLength) {
			return 0;
		}
		else {
			return ($aLength < $bLength) ? -1 : 1;
		}
	}

	/**
	 * This method receives the data array of the calling cObj as a reference
	 * It can thus modify it as desired
	 *
	 * @param array $data Data array of the calling cObj
	 *
	 * @return	void
	 */
	function processTagData(&$data) {
			// Change uid to match that of the glossary's for the given keyword
		$data['tag_uid'] = $this->glossary[$data['tag_keyword']];
	}
}
?>