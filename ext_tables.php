<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// Load the full TCA

t3lib_div::loadTCA('tt_content');

// Disable the display of layout and select_key fields

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1'] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2'] = 'layout,select_key,pages';

// Activate the display of the plug-in flexform field and set FlexForm defintion

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:vge_tagcloud/flexform_ds.xml');

// Register request updates from the pi_flexform field

$GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',referenceTable';

// Add class for the itemsProcFunc calls in the FlexForm

require_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_vgetagcloud_listsdef.php');

// Add the plug-ins to the list of existing plug-ins
t3lib_extMgm::addPlugin(
	array(
		'LLL:EXT:vge_tagcloud/locallang_db.xml:tt_content.list_type_pi1',
		$_EXTKEY . '_pi1',
		t3lib_extMgm::extRelPath($_EXTKEY) . 'res/images/plugin1_icon.png'
	),
	'list_type'
);
t3lib_extMgm::addPlugin(
	array(
		'LLL:EXT:vge_tagcloud/locallang_db.xml:tt_content.list_type_pi2',
		$_EXTKEY . '_pi2',
		t3lib_extMgm::extRelPath($_EXTKEY) . 'res/images/plugin2_icon.png'
	),
	'list_type'
);

// Define the path to the static TS files

t3lib_extMgm::addStaticFile($_EXTKEY,'static/','A Better Tag Cloud');

// Add the plug-in to the new content elements wizard

if (TYPO3_MODE == 'BE') $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_vgetagcloud_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_vgetagcloud_pi1_wizicon.php';
if (TYPO3_MODE == 'BE') $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_vgetagcloud_pi2_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi2/class.tx_vgetagcloud_pi2_wizicon.php';

	// Add context sensitive help (csh) for the FlexForm
t3lib_extMgm::addLLrefForTCAdescr('tt_content.pi_flexform.vge_tagcloud_pi1.list', 'EXT:' . $_EXTKEY . '/locallang_csh_vgetagcloud.xml');
?>