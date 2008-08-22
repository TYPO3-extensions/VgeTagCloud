<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Francois Suter <typo3@cobweb.ch>
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
 * RealURL autoconfiguration for the 'vge_tagcloud' extension.
 *
 * @author		Francois Suter <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_vgetagcloud
 *
 */
class tx_vgetagcloud_realurl {

	/**
	 * Generates additional RealURL configuration and merges it with provided configuration
	 *
	 * @param	array					$params: Default configuration
	 * @param	tx_realurl_autoconfgen	$pObj: Parent object
	 * @return	array					Updated configuration
	 */
	function addVgetagcloudConfig($params, &$pObj) {
		$tagcloudConfig = array(
			'postVarSets' => array(
				'_DEFAULT' => array(
					'tagcloud' => array(
						array('GETvar' => 'tx_vgetagcloud_pi2[keyword]'),
						array('GETvar' => 'tx_vgetagcloud_pi2[pages]')
					)
				)
			)
		);
		return array_merge_recursive($params['config'], $tagcloudConfig);
	}
}
?>
