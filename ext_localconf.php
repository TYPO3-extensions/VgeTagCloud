<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_vgetagcloud_pi1.php', '_pi1', 'list_type', 1);
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi2/class.tx_vgetagcloud_pi2.php', '_pi2', 'list_type', 1);

// Register RealURL autoconfiguration class

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['vge_tagcloud'] = 'EXT:vge_tagcloud/class.tx_vgetagcloud_realurl.php:tx_vgetagcloud_realurl->addVgetagcloudConfig';

// Examples of use of the extension's hooks
// Uncomment to activate or (better) copy to some other, personal ext_localconf.php file

//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_vgetagcloud_pi1']['postProcessRawKeywords'][] = 'EXT:vge_tagcloud/hooks/class.tx_vgetagcloud_hook_example.php:tx_vgetagcloud_hook_example';
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_vgetagcloud_pi1']['postProcessFinalKeywords'][] = 'EXT:vge_tagcloud/hooks/class.tx_vgetagcloud_hook_example.php:tx_vgetagcloud_hook_example';
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_vgetagcloud_pi1']['processTagData'][] = 'EXT:vge_tagcloud/hooks/class.tx_vgetagcloud_hook_example.php:tx_vgetagcloud_hook_example';
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_vgetagcloud_pi1']['extractKeywords'][] = 'EXT:vge_tagcloud/hooks/class.tx_vgetagcloud_hook_example.php:tx_vgetagcloud_hook_example';
?>