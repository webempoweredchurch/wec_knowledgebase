<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_wecknowledgebase_record=1
');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_wecknowledgebase_pi1 = < plugin.tx_wecknowledgebase_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_wecknowledgebase_pi1.php','_pi1','list_type',1);

## ADD HOOK for ExtraCodes
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraCodesHook'][] = 'tx_wecknowledgebase_pi1';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraGlobalMarkerHook'][] = 'tx_wecknowledgebase_pi1';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['userDisplayCatmenuHook'][] = 'tx_wecknowledgebase_pi1';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook'][] = 'tx_wecknowledgebase_pi1';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = array('POPULAR','POPULAR',);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = array('CATDROPDOWN','CATDROPDOWN',);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = array('TUTORIAL_LIST','TUTORIAL_LIST',);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = array('TUTORIALS_MENU','TUTORIALS_MENU',);

?>