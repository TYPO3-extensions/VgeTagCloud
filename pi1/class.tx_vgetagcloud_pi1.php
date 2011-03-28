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

require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Display a (better) tag cloud for the 'vge_tagcloud' extension.
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_vgetagcloud
 *
 * $Id$
 */
class tx_vgetagcloud_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_vgetagcloud_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_vgetagcloud_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'vge_tagcloud';	// The extension key.
	var $pi_checkCHash = true;
	var $doLangOverlay = false; // Check whether language overlay is needed or not
	var $allKeywordPages = array(); // List of page uids where keyword is found, for each keyword
	var $pageIdField; // uid or pid depending on table being queried

	/**
	 * This is the main method of the plugin. It returns the content to display
	 *
	 * @param	string		$content: The plugin content
	 * @param	array		$conf: The plugin configuration
	 * @return	string		The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->init($conf);

			// Get all keywords
			// If several tables were defined per TypoScript, loop on those tables. Otherwise just get the keywords from the reference table
		$allKeywords = array();
		if (isset($this->conf['references.'])) { // Array of reference tables
			foreach ($this->conf['references.'] as $values) {
				$whereClause = $this->buildCondition($values['table']);

					// Add specific where clause, if defined
				if (isset($values['where'])) {
					if (!empty($whereClause)) $whereClause .= ' AND';
					$whereClause .= ' (' . $values['where'] . ')';
				}
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($values['table'] . '.*', $values['table'], $whereClause);

					// Get keywords and merge with existing keywords
				$allKeywords = array_merge($allKeywords, $this->getKeywords($result, $values['table'], $values['fields']));
			}
		} else {
			// Single reference table

			$whereClause = $this->buildCondition($this->conf['referenceTable']);
			if (isset($this->conf['addWhere'])) {
				if (!empty($whereClause)) {
					$whereClause .= ' AND';
				}
				$whereClause .= ' (' . $this->conf['addWhere'] . ')';
			}
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->conf['referenceTable'] . '.*', $this->conf['referenceTable'], $whereClause);
			$allKeywords = $this->getKeywords($result, $this->conf['referenceTable'], $this->conf['referenceFields']);
		}

			// Count the keywords
		$countedKeywords = $this->countKeywords($allKeywords);

			// Apply sorting and limit
		$finalKeywords = $this->sortAndCapKeywords($countedKeywords);

			// Generate the tag cloud
		$content = $this->generateCloud($finalKeywords);

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

			// Load the flexform and loop on all its values to override TS setup values
			// Some properties use a different test (more strict than not empty) and yet some others no test at all
		$this->pi_initPIflexForm();
		if (is_array($this->cObj->data['pi_flexform']['data'])) {
			foreach ($this->cObj->data['pi_flexform']['data'] as $sheet => $langData) {
				foreach ($langData as $fields) {
					foreach ($fields as $field => $value) {
						$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $field, $sheet);
						if ($field == 'caseHandling') {
							$this->conf[$field] = $value;
						} elseif ($field == 'separator') {
							if ($value !== '') {
								$this->conf[$field] = $value;
							}
						} else {
							if (!empty($value)) {
								$this->conf[$field] = $value;
							}
						}
					}
				}
			}
		}

			// Handle local TypoScript override
		if (!empty($this->conf['flexformTS'])) {
				// Check for file inclusion
			$typoscript = t3lib_TSparser::checkIncludeLines($this->conf['flexformTS']);
				// Instantiate a TS parser
	        $parseObj = t3lib_div::makeInstance('t3lib_TSparser');
				// Parse the local TypoScript
	   	    $parseObj->parse($typoscript);
				// Merge with local configuration
	   	    $this->conf = t3lib_div::array_merge_recursive_overrule($this->conf, $parseObj->setup);
		}

			// Handle splitChar as a stdWrap property
		if (isset($this->conf['splitChar.'])) {
			$this->conf['splitChar'] = $this->cObj->stdWrap($this->conf['splitChar'], $this->conf['splitChar.']);
		}
			// Handle excludePids as a stdWrap property
		if (isset($this->conf['excludePids.'])) {
			$this->conf['excludePids'] = $this->cObj->stdWrap($this->conf['excludePids'], $this->conf['excludePids.']);
		}

			// Start page (and recursive exploring of page tree beneath it) may come from TS
			// or from a selection in the "startingpoint" form field
			// If the start page is still empty after all that, use current page as starting point
		if (isset($conf['startPage.'])) {
			$this->conf['startPage'] = $this->cObj->stdWrap('', $conf['startPage.']);
		}
		if (!empty($this->cObj->data['pages'])) {
			$this->conf['startPage'] = $this->cObj->data['pages'];
		}
		if (empty($this->conf['startPage'])) {
			$this->conf['startPage'] = $GLOBALS['TSFE']->id;
		}
		if (!empty($this->cObj->data['recursive'])) {
			$this->conf['recursive'] = $this->cObj->data['recursive'];
		}
	}

	/**
	 * This method can be used for a light and fast initialization when not called as a FE plugin
	 * to avoid all the work performed when calling main()
	 *
	 * @param	array				$conf: TS configuration of the extension
	 * @param	tslib_content		$cObj: content object
	 * @return	void
	 */
	function externalInit($conf, $cObj)	{
		$this->conf = $conf;
		$this->cObj = $cObj;
	}

	/**
	 * This method builds the condition (WHERE clause) to apply to the query
	 * that will be used to get the fields where the keywords are stored
	 *
	 * @param	string		$referenceTable: name of the table to build the condition for
	 * @return	string		part of a WHERE clause
	 */
	function buildCondition($referenceTable) {
		$tableTCA = array();
		if (isset($GLOBALS['TCA'][$referenceTable])) {
			$tableTCA = $GLOBALS['TCA'][$referenceTable];
		}

			// Get the list of pages to explore, if any
		$selectedPages = '';
		if (!empty($this->conf['startPage'])) {
			if (!empty($this->conf['recursive'])) {
				$selectedPages = $this->pi_getPidList($this->conf['startPage'], $this->conf['recursive']);
			} else {
				$selectedPages = $this->conf['startPage'];
			}
		}
		$selectedPagesArray = t3lib_div::trimExplode(',', $selectedPages);

			// Add exclusion of "not in menu" pages if includeNotInMenu = 0
		if (empty($this->conf['includeNotInMenu'])) {

				// Get all pages that are not in menu
			$output = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', "nav_hide = '1'");
			if (is_array($output)) {
				$excludePages = array();

					// Extract the uid of the resulting pages
				foreach ($output as $row) {
					$excludePages[] = $row['uid'];
				}

					// Use diff to exclude the "not in menu" pages from the list of pages to explore
					// and redefine the list of pages as a comma-separated list of uid's
				$selectedPagesArray = array_diff($selectedPagesArray, $excludePages);
			}
		}

			// Handle list of exclude pages
		if (!empty($this->conf['excludePids'])) {
			$excludePages = $this->pi_getPidList($this->conf['excludePids'], 255);
			$excludePagesArray = t3lib_div::trimExplode(',', $excludePages);
			$selectedPagesArray = array_diff($selectedPagesArray, $excludePagesArray);
		}

			// Hook for post-processing the list of pages
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['postProcessPages'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['postProcessPages'] as $className) {
				$postProcessor = &t3lib_div::getUserObj($className);
				$selectedPagesArray = $postProcessor->postProcessPages($selectedPagesArray, $this);
			}
		}

			// Implode list of selected pages into a comma-separated list
		$pages = implode(',', $selectedPagesArray);

			// If at least one page is defined, assemble it into a where clause
			// Note that this differs depending on whether we are interrogating the pages table or some other table
		$condition = '';
		if (!empty($pages)) {
			if ($referenceTable == 'pages') {
				$condition = 'uid IN (' . $pages . ')';
					// Store for later reference
				$this->pageIdField = 'uid';
			} else {
				$fields = array_keys($GLOBALS['TYPO3_DB']->admin_get_fields($referenceTable));
					// Use pid condition only if reference table has a pid field :-)
				if (in_array('pid', $fields)) {
					$condition = 'pid IN (' . $pages . ')';
						// Store for later reference
					$this->pageIdField = 'pid';
				}
			}
		}

			// If reference has a TCA definition, use it to add enable fields
			// Note: this is not necessary for the "pages" table, because it was already handled by the call to tslib_pibase::pi_getPidList()
		if ($referenceTable != 'pages' && count($tableTCA) > 0) {
			if (empty($condition)) {
				$condition = '1 = 1'; // Prevent SQL syntax error
			}
			$condition .= ' ' . $this->cObj->enableFields($referenceTable);
		}

			// Add selection of language (if activated)
			// There are two main cases:
			//
			//	-	if the overlay mechanism is activated and the reference table has translation information,
			//		we must get the original elements and the translation will be overlaid afterwards
			//	-	if not, then we get directly the elements in the right language
			//
			// This is not done for the pages table, which has a different overlay mechanism
		if ($referenceTable != 'pages') {
			if (!empty($tableTCA['ctrl']['languageField'])) {
				$languageCondition = '';
				if (isset($GLOBALS['TSFE']->sys_language_contentOL) && isset($tableTCA['ctrl']['transOrigPointerField'])) {
					$languageCondition = $tableTCA['ctrl']['languageField'] . ' IN (0,-1)'; // Default language and "all" language

						// If current language is not default, select elements that exist only for current language
						// That means elements that exist for current language but have no parent element
					if ($GLOBALS['TSFE']->sys_language_content > 0) {
						$languageCondition .= ' OR ('.$tableTCA['ctrl']['languageField']." = '".$GLOBALS['TSFE']->sys_language_content."' AND ".$tableTCA['ctrl']['transOrigPointerField']." = '0')";
						$this->doLangOverlay = true; // Set flag to activate language overlay later
					}
				}
				else {
					$languageCondition = $tableTCA['ctrl']['languageField'] . " = '" . $GLOBALS['TSFE']->sys_language_content . "'";
				}
				if (!empty($condition)) {
					$condition .= ' AND ';
				}
				$condition .= '(' . $languageCondition . ')';
			}
		} else {
			if ($GLOBALS['TSFE']->sys_language_content > 0) {
				$this->doLangOverlay = true; // Set flag to activate language overlay later
			}
		}

			// Add selection of workspace (if activated)
		if (!empty($tableTCA['ctrl']['versioningWS'])) {
			if (!empty($condition)) {
				$condition .= ' AND ';
			}
			if (empty($GLOBALS['TSFE']->workspacePreview)) {
				$condition .= " t3ver_wsid = '0'";
			} else {
				$condition .= ' t3ver_wsid IN (0,' . $GLOBALS['TSFE']->workspacePreview . ')';
			}
		}

			// Add specific exclude conditions
		if (isset($this->conf['exclude.'][$referenceTable.'.'])) {
			foreach ($this->conf['exclude.'][$referenceTable.'.'] as $field => $valuesList) {
				if (!empty($condition)) {
					$condition .= ' AND ';
				}
				$condition .= $referenceTable.'.'.$field;
				$values = t3lib_div::trimExplode(',', $valuesList, 1);
				if (count($values) == 1) {
					$condition .= " <> '" . $values[0] . "'";
				}
				else {
					$condition .= " NOT IN ('" . implode("', '", $values) . "')";
				}
			}
		}

		return $condition;
	}

	/**
	 * This method takes the results from the database query and makes them into a list of keywords
	 * It creates a raw array with one entry per keyword, with no sorting or counting whatsoever
	 * This is done at a later stage
	 *
	 * @param	integer		$result: handle to the database query result
	 * @param	string		$referenceTable: name of the table the results were gathered from
	 * @param	string		$referenceFields: comma-separated list of fields to get the keywords from
	 * @return	array		Simple array with 1 entry per keyword found
	 */
	function getKeywords($result, $referenceTable, $referenceFields) {
		$allKeywords = array();

			// Get the list of fields to retrieve keywords from
		$fields = t3lib_div::trimExplode(',', $referenceFields, 1);

			// Load data extractor hooks, if any
			// Data extractor hooks make it possible to use other methods of extracting keywords than the default one
			// So note that data extractors are not called *on top* of the normal method, but *instead*
		$dataExtractors = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['extractKeywords'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['extractKeywords'] as $className) {
				$dataExtractors[] = &t3lib_div::getUserObj($className);
			}
		}

			// Set a boolean flag instead of testing inside every iteration below
		$hasDataExtractors = count($dataExtractors) > 0;

			// Loop on all rows returned
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))) {

				// If necessary overlay translations
			if ($this->doLangOverlay) {

				if ($referenceTable == 'pages') {
					// For pages, the specific overlay method must be called

					$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
						// No overlay was found, skip page
					if (!isset($row['_PAGES_OVERLAY'])) {
						continue;
					}
				} else {
					// For other kind of records, use generic overlay method

					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($referenceTable, $row, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
				}
			}
				// Row may be unset if no translation was found and overlay mode is hideNonTranslated
			if (!empty($row)) {
				foreach ($fields as $aField) {
					if (!empty($row[$aField])) {
						$keywords = array();

						if ($hasDataExtractors) {
							// If there are some data extraction hooks registered, use them instead of normal methods

							foreach($dataExtractors as $aDataExtractors) {
								$aDataExtractors->extractKeywords($row[$aField], $this->conf, $keywords);
							}
						} elseif (empty($this->conf['splitChar'])) {
							// If there's no splitChar define, we use word boundaries

							$rawKeywords = preg_split('/' . addcslashes($this->conf['splitWords'], "'/") . '/', strip_tags($row[$aField]));
							foreach ($rawKeywords as $theKeyword) { // Exclude empty or blank strings
								$theKeyword = trim($theKeyword);
								if (!empty($theKeyword)) {
									$keywords[] = $theKeyword;
								}
							}
							if ($this->conf['uniqueKeywordsPerItem']) {
								$keywords = array_unique($keywords);
							}
						} else {
							// Otherwise, use the defined splitChar

							$keywords = t3lib_div::trimExplode($this->conf['splitChar'], $row[$aField],1);
							if ($this->conf['uniqueKeywordsPerItem']) {
								$keywords = array_unique($keywords);
							}
						}

							// Store keywords in the global keyword array, appyling case transformation if necessary
						foreach ($keywords as $aKeyword) {
							if ($this->conf['caseHandling'] == 'upper') {
								$aKeyword = $GLOBALS['TSFE']->csConvObj->conv_case($GLOBALS['TSFE']->renderCharset, $aKeyword,'toUpper');
							} elseif ($this->conf['caseHandling'] == 'lower') {
								$aKeyword = $GLOBALS['TSFE']->csConvObj->conv_case($GLOBALS['TSFE']->renderCharset, $aKeyword,'toLower');
							}
							$allKeywords[] = $aKeyword;

								// If there's a page id to remember, store it now
								// making sure array is set and that no page id is stored twice for a given keyword
							if (!empty($this->pageIdField)) {
								if (!isset($this->allKeywordPages[$aKeyword])) {
									$this->allKeywordPages[$aKeyword] = array();
								}
								if (!in_array($row[$this->pageIdField], $this->allKeywordPages[$aKeyword])) {
									$this->allKeywordPages[$aKeyword][] = $row[$this->pageIdField];
								}
							}
						}
					}
				}
			}
		}

			// Hook for post-processing the raw list of keywords before returning it
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['postProcessRawKeywords'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['postProcessRawKeywords'] as $className) {
				$postProcessor = &t3lib_div::getUserObj($className);
				$allKeywords = $postProcessor->postProcessRawKeywords($allKeywords, $this);
			}
		}

		return $allKeywords;
	}

	/**
	 * This method counts how many times each keyword appears
	 * and returns an associative array with the keywords and their totals
	 *
	 * @param	array		$keywords: 1-dimensional array containing all keywords
	 * @return	array		Array of all keywords and their totals as key-value pairs
	 */
	function countKeywords($keywords) {
		$countedKeywords = array();
		foreach ($keywords as $aKeyword) {
			if (isset($countedKeywords[$aKeyword])) {
				$countedKeywords[$aKeyword]++;
			} else {
				$countedKeywords[$aKeyword] = 1;
			}
		}
		return $countedKeywords;
	}

	/**
	 * This method applies the sorting and limit criteria to the list of counted keywords
	 *
	 * @param	array		$keywords: array of keywords to sort
	 * @return	array		Array of keywords, sorted and limited
	 */
	function sortAndCapKeywords($keywords) {
		$slicedKeywords = array();

			// Make sure some default values are set for sorting
		if (empty($this->conf['sorting'])) {
			$this->conf['sorting'] == 'natural';
		}
		if (empty($this->conf['sortOrder'])) {
			$this->conf['sortOrder'] == 'asc';
		}

			// Sort keywords
		switch ($this->conf['sorting']) {
			case 'alpha': // Alphabetical sorting is equivalent to sorting on keys
				if ($this->conf['sortOrder'] == 'desc') {
					krsort($keywords);
				} else {
					ksort($keywords);
				}
				break;
			case 'weight': // Sorting by weight is equivalent to sorting on values
				if ($this->conf['sortOrder'] == 'desc') {
					arsort($keywords);
				} else {
					asort($keywords);
				}
				break;
			default:
				break;
		}

			// Apply limit to number of keywords
		if (empty($this->conf['maxWords'])) { // No value, take all keywords
			$slicedKeywords = $keywords;
		} else {
			$slicedKeywords = array();
			$counter = 0;
			foreach($keywords as $aKeyword => $aWeight) {
				$slicedKeywords[$aKeyword] = $aWeight;
				$counter++;
				if ($counter == $this->conf['maxWords']) {
					break;
				}
			}
		}
		return $slicedKeywords;
	}

	/**
	 * This method generates the actual tag cloud given a list of counted keywords
	 *
	 * @param	array		$keywords: Keywords and their count
	 * @return	string		HTML-code of the tag cloud
	 */
	function generateCloud($keywords) {

			// Sort the keywords for display
		$keywords = $this->sortKeywords($keywords);

			// Hook for applying some last minute algorithm to the keywords
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['postProcessFinalKeywords'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['postProcessFinalKeywords'] as $className) {
				$postProcessor = &t3lib_div::getUserObj($className);
				$keywords = $postProcessor->postProcessFinalKeywords($keywords, $this);
			}
		}

			// Assemble the cloud only if there are any keywords left at this point
		$cloud = '';
		if (count($keywords) > 0) {

				// Calculate the styles for each keyword
			$styles = $this->calculateStyles($keywords);

				// Load data processor hooks, if any
			$dataProcessors = array();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['processTagData'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->prefixId]['processTagData'] as $className) {
					$dataProcessors[] = &t3lib_div::getUserObj($className);
				}
			}

				// Assemble the HTML code for the tag cloud
			$tags = array();
				// The counter is used to generated a unique ID number for each keyword
			$counter = 1;
			$tagWrapConfiguration = ($this->conf['renderingType'] == 'styles') ? $this->conf['tagWrapStyles.'] : $this->conf['tagWrap.'];
				// Load the start page into the cObj data
			$this->cObj->data['tag_startpage'] = $this->conf['startPage'];
			$this->cObj->data['tag_link'] = $this->conf['targetPage'];
			foreach ($keywords as $aKeyword => $count) {

					// Load all the specific tag cloud-related values into the cObj data
				$this->cObj->data['tag_keyword'] = $aKeyword;
				$this->cObj->data['tag_weight'] = $count;
				$this->cObj->data['tag_style'] = $styles[$aKeyword];
				$this->cObj->data['tag_id'] = $counter;
				$this->cObj->data['tag_pages'] = (isset($this->allKeywordPages[$aKeyword])) ? implode('_', $this->allKeywordPages[$aKeyword]) : '';

					// Call hooks to process tag data, if any
				foreach($dataProcessors as $aDataProcessors) {
					$aDataProcessors->processTagData($this->cObj->data);
				}

					// Assemble the tag
				$tags[] = $this->cObj->stdWrap($aKeyword, $tagWrapConfiguration);
				$counter++;
			}
			$allTags = implode($this->conf['separator'], $tags);
			$cloud = $this->cObj->stdWrap($allTags, $this->conf['cloudWrap.']);
		}
		return $cloud;
	}


	/**
	 * This method applies another sorting before the keywords are actually displayed
	 *
	 * @param	array		$keywords: array of keywords to sort
	 * @return	array		Array of sorted keywords
	 */
	function sortKeywords($keywords) {

			// Make sure some default values are set for sorting
		if (empty($this->conf['sortingForDisplay'])) {
			$this->conf['sortingForDisplay'] == 'natural';
		}
		if (empty($this->conf['sortOrderForDisplay'])) {
			$this->conf['sortOrderForDisplay'] == 'asc';
		}

			// Sort keywords
		switch ($this->conf['sortingForDisplay']) {
				// Alphabetical sorting is equivalent to sorting on keys
			case 'alpha':
				if ($this->conf['sortOrderForDisplay'] == 'desc') {
					krsort($keywords);
				} else {
					ksort($keywords);
				}
				break;

				// Sorting by weight is equivalent to sorting on values
			case 'weight':
				if ($this->conf['sortOrderForDisplay'] == 'desc') {
					arsort($keywords);
				} else {
					asort($keywords);
				}
				break;
			default:
				break;
		}
		return $keywords;
	}

	/**
	 * This methods defined which style applies to each keyword
	 *
	 * @param	array		$keywords: Keywords and their count
	 * @return	array		Array with a style for each keyword
	 */
	function calculateStyles($keywords) {
		$styles = array();

			// Make sure there's a default value
		if (empty($this->conf['renderingType'])) {
			$this->conf['renderingStyle'] = 'weight';
		}

			// Get smallest and largest weight among keywords and calculate difference
		$smallestWeight = min($keywords);
		$largestWeight = max($keywords);
		$deltaWeight = $largestWeight - $smallestWeight;

		if ($this->conf['renderingType'] == 'styles') {
			$styleList = t3lib_div::trimExplode(',', $this->conf['styles'],1);
			$numStyles = count($styleList);
			if ($numStyles > 0) { // Calculate styles only if at least one was defined :-)
				$weightIncrement = doubleval($deltaWeight) / doubleval($numStyles);
				foreach ($keywords as $aKeyword => $count) {
					$weightDifference = $count - $smallestWeight;
					if ($weightIncrement == 0) {
						$styleIndex = 0;
					} else {
						$incrementMultiplier = $weightDifference / $weightIncrement;
						$styleIndex = ceil($incrementMultiplier) - 1;
						if ($styleIndex < 0) $styleIndex = 0;
					}
					$styles[$aKeyword] = $styleList[$styleIndex];
				}
			}
		} else {
			$minRelWeight = $this->conf['minWeight'];
			$maxRelWeight = $this->conf['maxWeight'];
			$deltaRelWeight = $maxRelWeight - $minRelWeight;
			$scaleFactor = intval($this->conf['scaleFactor']);
			if ($scaleFactor <= 0) {
				$scaleFactor = 3;
			}
			foreach ($keywords as $aKeyword => $count) {
				if ($deltaWeight == 0) {
					$size = $minRelWeight;
				} else {
					$linearDeltaSize = ($count - $smallestWeight) / $deltaWeight; // 0 <= $linearDeltaSize <= 1

					switch($this->conf['scale']) {
						case 'flatTop':
							$deltaSize = 1 - pow((1 - $linearDeltaSize), $scaleFactor);
							break;
						case 'flatBottom':
							$deltaSize = pow($linearDeltaSize, $scaleFactor);
							break;
						case 'flatTopAndBottom':
							$deltaSize = sin(( sin( $linearDeltaSize * M_PI - M_PI/2 ) / 2 + .5 ) * M_PI - M_PI/2) / 2 + .5;
							break;
						case 'flatMiddle':
							$deltaSize = 2 * $linearDeltaSize + sin( $linearDeltaSize * M_PI + M_PI / 2 ) / 2 - .5;
							$deltaSize = 2 * $deltaSize + sin( $deltaSize * M_PI + M_PI / 2 ) / 2 - .5;
							break;
						case 'linear':
						default:
							$deltaSize = $linearDeltaSize;
					}

					$size = $minRelWeight + ($deltaSize * $deltaRelWeight);
				}
				$styles[$aKeyword] = round($size);
			}
		}
		return $styles;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_tagcloud/pi1/class.tx_vgetagcloud_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/vge_tagcloud/pi1/class.tx_vgetagcloud_pi1.php']);
}
?>