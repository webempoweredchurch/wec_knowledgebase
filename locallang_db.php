<?php
/**
 * Language labels for database tables/fields belonging to extension "wec_knowledgebase"
 *
 * This file is detected by the translation tool.
 */

$LOCAL_LANG = Array (
	'default' => Array (
		'tx_wecknowledgebase_comments' => 'Comments On Knowledgebase',
		'tx_wecknowledgebase_comments.kb_uid' => 'Knowledgebase record to link to',
		'tx_wecknowledgebase_comments.user_uid' => 'User UID who posted',
		'tx_wecknowledgebase_comments.comment_text' => 'Comment Text',
		'tx_wecknowledgebase_comments.name' => 'Name of user who posted ',
		'tx_wecknowledgebase_comments.email' => 'Email of user who posted',
		'tt_news.tx_wecknowledgebase' => 'Knowledgebase Record',
		'tt_news.tx_weckb_click_count' => 'Count of clicks on (do not change unless want to force)',
		'tt_news.tx_weckb_tutorial_content' => 'Tutorial Resource File',
		'tt_news.tx_weckb_tutorial_version_info' => 'Tutorial Version Information (i.e. Typo3, Ext. Version)',
		'tt_news.tx_weckb_tutorial_image' => 'Tutorial Preview Image (for popup)',
		'tt_news.tx_weckb_comment_count' => 'Count of comments (do not change unless want to force)',
		'tt_content.list_type_pi1' => 'WEC Knowledgebase',

		'wec_knowledgbase.pi_flexform.no_sorting' => 'None',
		'wec_knowledgbase.pi_flexform.sorting' => 'Sorting in Backend',

		'wec_knowledgebase.pi_flexform.sheet_knowledgebase' => 'Knowledgebase',
			'wec_knowledgebase.pi_flexform.administrator_list' => 'Admin List (UID or username sep. by comma): ',
			'wec_knowledgebase.pi_flexform.use_sidebar' => 'Show Sidebar (SINGLE view only)?',
			'wec_knowledgebase.pi_flexform.use_actionbar' => 'Show Actionbar (SINGLE view only)?',
			'wec_knowledgebase.pi_flexform.use_related' => 'Show Related in SINGLE view?',
			'wec_knowledgebase.pi_flexform.tutorialplayer_code' => 'Flash/Video/Audio Player Code (#FILE# for file)',

			'wec_knowledgebase.pi_flexform.use_comments' => 'Allow Comments (SINGLE view only)?',
			'wec_knowledgebase.pi_flexform.login_to_post_comments' => 'Login Required to Post Comments (SINGLE view only)?',
			'wec_knowledgebase.pi_flexform.getallcomments_email' => 'Email(s) to receive all comments',
			'wec_knowledgebase.pi_flexform.getallcomments_fromEmail' => 'Email "FROM" field for comments',
			'wec_knowledgebase.pi_flexform.commentform_required_fields' => 'Require which fields for comments',
				'wec_knowledgebase.pi_flexform.required_none' => 'none',
				'wec_knowledgebase.pi_flexform.required_name' => 'name only',
				'wec_knowledgebase.pi_flexform.required_name_email' => 'name and email',
				'wec_knowledgebase.pi_flexform.required_email' => 'email only',

			'wec_knowledgebase.pi_flexform.html_tags_allowed'  => 'HTML Tags Allowed (0=none, 1=all, or list- <b><i>)',
			'wec_knowledgebase.pi_flexform.use_captcha' 		=> 'Use Image Captcha? (need sr_freecap installed)',
			'wec_knowledgebase.pi_flexform.numlinks_allowed' 	=> 'Number of links allowed in post? (0 = none)',
			'wec_knowledgebase.pi_flexform.filter_wordlist' 	=> 'Word filter list (comma-delimited; use * for default)',
			'wec_knowledgebase.pi_flexform.filter_word_handling' 	=> 'What to do if filter words are found?',
				'wec_knowledgebase.pi_flexform.wordFilter' 		=> 'Filter (ie. ****)',
				'wec_knowledgebase.pi_flexform.wordDiscard' 	=> 'Discard the post',
		'wec_knowledgebase.pi_flexform.imageMaxWidth'  => 'Max width for images in KB records',
		'wec_knowledgebase.pi_flexform.imageMaxHeight' => 'Max height for images in KB records',
	),
);
?>