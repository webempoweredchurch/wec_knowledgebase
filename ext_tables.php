<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::allowTableOnStandardPages("tx_wecknowledgebase_comments");
$thisExtRelPath = t3lib_extMgm::extRelPath($_EXTKEY);
// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tt_news']);

$TCA["tx_wecknowledgebase_comments"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:wec_knowledgebase/locallang_db.php:tx_wecknowledgebase_comments",
		"label" => "comment_text",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY crdate DESC",
		"enablecolumns" => Array (
			"disabled" => "hidden",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wecknowledgebase_comments.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, kb_uid, user_uid, comment_text",
	)
);

$tempColumns = Array (
	'tx_weckb_tutorial_content' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_knowledgebase/locallang_db.php:tt_news.tx_weckb_tutorial_content',
		'config' => Array (
			'type' => 'group',
			'internal_type' => 'file',
			'allowed' => '',
			'disallowed' => 'php,php3',
			'max_size' => 10000,
			'uploadfolder' => 'uploads/tx_wecknowledgebase',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_weckb_tutorial_version_info' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_knowledgebase/locallang_db.php:tt_news.tx_weckb_tutorial_version_info',
		'config' => Array (
			'type' => 'text',
			'cols' => '40',
			'rows' => '3',
		)
	),
	'tx_weckb_tutorial_image' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:wec_knowledgebase/locallang_db.php:tt_news.tx_weckb_tutorial_image',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => '5000',
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => '1',
				'size' => 1,
				'autoSizeMax' => 1,
				'maxitems' => '1',
				'minitems' => '0'
			)
	),
	'tx_weckb_click_count' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_knowledgebase/locallang_db.php:tt_news.tx_weckb_click_count',
		'config' => Array (
			'type' => 'input',
			'size' => '4',
			'max' => '4',
			'eval' => 'int',
			'checkbox' => '0',
			'range' => Array (
				'upper' => '1000000',
				'lower' => '1'
			),
			'default' => 0
		)
	),
	'tx_weckb_comment_count' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:wec_knowledgebase/locallang_db.php:tt_news.tx_weckb_comment_count',
		'config' => Array (
			'type' => 'input',
			'size' => '4',
			'max' => '4',
			'eval' => 'int',
			'checkbox' => '0',
			'range' => Array (
				'upper' => '5000',
				'lower' => '1'
			),
			'default' => 0
		)
	),
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';

t3lib_extMgm::addPlugin(Array('LLL:EXT:wec_knowledgebase/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

t3lib_extMgm::addStaticFile($_EXTKEY,'static/ts','WEC Knowledgebase Template');

// setup the flexform for the knowledgebase
$TCA["tt_content"]["types"]["list"]["subtypes_addlist"][$_EXTKEY."_pi1"]="pi_flexform";

// switch the XML files for the FlexForm depending on if "use StoragePid"(general record Storage Page) is set or not.
if ($confArr['useStoragePid']) {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:wec_knowledgebase/flexform_ds.xml');
} else {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:wec_knowledgebase/flexform_ds_no_storagepid.xml');
}

// setup tt_news record so that the BE record has a third tab for KB and it is only active when the type=Knowledgebase
t3lib_div::loadTCA('tt_news');
t3lib_extMgm::addTCAcolumns('tt_news', $tempColumns, 1);
$TCA['tt_news']['ctrl']['typeicons'][] = $thisExtRelPath.'icon_tx_wecknowledgebase_record.gif';
$TCA['tt_news']['ctrl']['sortby'] = "sorting"; // adding sorting capability for wec_kb
$TCA['tt_news']['columns']['type']['config']['items'][] = Array('LLL:EXT:wec_knowledgebase/locallang_db.php:tt_news.tx_wecknowledgebase', 4);
$TCA['tt_news']['interface']['showRecordFieldList'] .= ',tx_weckb_tutorial_content,tx_weckb_tutorial_version_info,tx_weckb_tutorial_image,tx_weckb_click_count,tx_weckb_comment_count';
$TCA['tt_news']['types']['4'] = array();

$TCA['tt_news_cat']['ctrl']['sortby'] = "sorting"; // adding sorting capability for wec_kb categories

t3lib_extMgm::addToAllTCAtypes('tt_news', 'title;;1;;,type,editlock,datetime;;2;;1-1-1,author;;3;;,short,bodytext;;4;richtext:rte_transform[flag=rte_enabled|mode=ts];4-4-4,no_auto_pb,--div--;Relations,category,image;;;;1-1-1,imagecaption;;5;;,links;;;;2-2-2,related;;;;3-3-3,news_files;;;;4-4-4,--div--;Knowledgebase,tx_weckb_tutorial_content;;;;1-1-1,tx_weckb_tutorial_version_info,tx_weckb_tutorial_image;;;;1-1-1,tx_weckb_click_count,tx_weckb_comment_count', 4);

if (TYPO3_MODE=="BE")	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_wecknowledgebase_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_wecknowledgebase_pi1_wizicon.php';

?>