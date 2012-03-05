<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


$TCA["tx_wecknowledgebase_comments"] = Array (
	"ctrl" => $TCA["tx_wecknowledgebase_comments"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,kb_uid,user_uid,name,email,comment_text"
	),
	"feInterface" => $TCA["tx_wecknowledgebase_comments"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"kb_uid" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_knowledgebase/locallang_db.php:tx_wecknowledgebase_comments.kb_uid",
			"config" => Array (
				"type" => "input",
				"size" => "4",
				"max" => "4",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "1000",
					"lower" => "1"
				),
				"default" => 0
			)
		),
		"user_uid" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_knowledgebase/locallang_db.php:tx_wecknowledgebase_comments.user_uid",
			"config" => Array (
				"type" => "input",
				"size" => "4",
				"max" => "4",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "1000",
					"lower" => "1"
				),
				"default" => 0
			)
		),
		"comment_text" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_knowledgebase/locallang_db.php:tx_wecknowledgebase_comments.comment_text",
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "4",
			)
		),
		"name" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_knowledgebase/locallang_db.php:tx_wecknowledgebase_comments.name",
			"config" => Array (
				"type" => "input",
				"size" => "30",
			)
		),
		"email" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_knowledgebase/locallang_db.php:tx_wecknowledgebase_comments.email",
			"config" => Array (
				"type" => "input",
				"size" => "30",
			)
		),		
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, kb_uid, user_uid, name, email, comment_text")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);
?>