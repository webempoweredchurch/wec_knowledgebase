<?php

########################################################################
# Extension Manager/Repository config file for ext "wec_knowledgebase".
#
# Auto generated 05-03-2012 14:39
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'WEC Knowledgebase',
	'description' => 'Knowledgebase functionality including articles with comments and tutorial resources, category menu, and listing by most popular and latest entries, etc. Uses tt_news as base.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.9.4',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Web-Empowered Church Team',
	'author_email' => 'knowledgebase(at)webempoweredchurch.org',
	'author_company' => 'Christian Technology Ministries International Inc.',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'tt_news' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:82:{s:12:"ext_icon.gif";s:4:"2d1c";s:17:"ext_localconf.php";s:4:"9384";s:14:"ext_tables.php";s:4:"0f39";s:14:"ext_tables.sql";s:4:"0a35";s:15:"flexform_ds.xml";s:4:"1a0b";s:29:"flexform_ds_no_storagepid.xml";s:4:"4cde";s:37:"icon_tx_wecknowledgebase_comments.gif";s:4:"c39d";s:35:"icon_tx_wecknowledgebase_record.gif";s:4:"f7f5";s:16:"locallang_db.php";s:4:"c858";s:7:"tca.php";s:4:"1db9";s:14:"doc/manual.sxw";s:4:"b854";s:14:"pi1/ce_wiz.gif";s:4:"6155";s:37:"pi1/class.tx_wecknowledgebase_pi1.php";s:4:"d782";s:45:"pi1/class.tx_wecknowledgebase_pi1_wizicon.php";s:4:"b89f";s:13:"pi1/clear.gif";s:4:"cc11";s:20:"pi1/kb_template.tmpl";s:4:"f4c1";s:17:"pi1/locallang.php";s:4:"3828";s:17:"res/action_go.gif";s:4:"91ab";s:15:"res/add_cat.gif";s:4:"f7fb";s:18:"res/add_subcat.gif";s:4:"745e";s:13:"res/arrow.gif";s:4:"0ee8";s:17:"res/atom_0_3.tmpl";s:4:"54c9";s:29:"res/example_amenuUserFunc.php";s:4:"58c1";s:31:"res/example_imageMarkerFunc.php";s:4:"c5af";s:35:"res/example_itemMarkerArrayFunc.php";s:4:"dcc9";s:35:"res/example_userPageBrowserFunc.php";s:4:"6209";s:14:"res/folder.gif";s:4:"57ce";s:14:"res/kb_new.gif";s:4:"cf4a";s:15:"res/kb_page.gif";s:4:"1234";s:18:"res/kb_popular.gif";s:4:"e577";s:13:"res/kb_up.gif";s:4:"ad0a";s:27:"res/news_amenuUserFunc2.php";s:4:"6496";s:12:"res/page.gif";s:4:"1234";s:12:"res/rdf.tmpl";s:4:"ba4a";s:29:"res/realUrl_example_setup.txt";s:4:"6728";s:17:"res/rss_0_91.tmpl";s:4:"c2e0";s:14:"res/rss_2.tmpl";s:4:"ce35";s:16:"res/swfobject.js";s:4:"7759";s:23:"res/tt_news_article.gif";s:4:"91b6";s:26:"res/tt_news_article__h.gif";s:4:"d29b";s:27:"res/tt_news_article__ht.gif";s:4:"d092";s:28:"res/tt_news_article__htu.gif";s:4:"412b";s:27:"res/tt_news_article__hu.gif";s:4:"a2c8";s:26:"res/tt_news_article__t.gif";s:4:"3df2";s:27:"res/tt_news_article__tu.gif";s:4:"9690";s:26:"res/tt_news_article__u.gif";s:4:"4ffc";s:26:"res/tt_news_article__x.gif";s:4:"2e15";s:19:"res/tt_news_cat.gif";s:4:"2efd";s:22:"res/tt_news_cat__d.gif";s:4:"0bdf";s:26:"res/tt_news_cat__f.gif.gif";s:4:"1dc9";s:23:"res/tt_news_cat__fu.gif";s:4:"9dfa";s:22:"res/tt_news_cat__h.gif";s:4:"d98b";s:23:"res/tt_news_cat__hf.gif";s:4:"d98b";s:24:"res/tt_news_cat__hfu.gif";s:4:"d422";s:23:"res/tt_news_cat__ht.gif";s:4:"e4ea";s:24:"res/tt_news_cat__htf.gif";s:4:"e4ea";s:25:"res/tt_news_cat__htfu.gif";s:4:"f324";s:24:"res/tt_news_cat__htu.gif";s:4:"f324";s:27:"res/tt_news_cat__hu.gif.gif";s:4:"d422";s:22:"res/tt_news_cat__t.gif";s:4:"f2c9";s:23:"res/tt_news_cat__tf.gif";s:4:"f2c9";s:24:"res/tt_news_cat__tfu.gif";s:4:"dd60";s:23:"res/tt_news_cat__tu.gif";s:4:"dd60";s:22:"res/tt_news_cat__u.gif";s:4:"1b40";s:22:"res/tt_news_cat__x.gif";s:4:"a08d";s:22:"res/tt_news_exturl.gif";s:4:"57f6";s:25:"res/tt_news_exturl__h.gif";s:4:"7465";s:26:"res/tt_news_exturl__ht.gif";s:4:"9199";s:27:"res/tt_news_exturl__htu.gif";s:4:"7019";s:26:"res/tt_news_exturl__hu.gif";s:4:"467e";s:25:"res/tt_news_exturl__t.gif";s:4:"0fd0";s:26:"res/tt_news_exturl__tu.gif";s:4:"2659";s:25:"res/tt_news_exturl__u.gif";s:4:"260e";s:25:"res/tt_news_exturl__x.gif";s:4:"4ebe";s:28:"res/tt_news_languageMenu.php";s:4:"1c59";s:27:"res/tt_news_medialinks.html";s:4:"82c3";s:22:"res/tt_news_styles.css";s:4:"1214";s:25:"res/tt_news_v2_styles.css";s:4:"7056";s:24:"res/tutorial-icon-lg.gif";s:4:"c6fe";s:21:"res/tutorial-icon.gif";s:4:"a03c";s:23:"static/ts/constants.txt";s:4:"76cc";s:19:"static/ts/setup.txt";s:4:"376e";}',
);

?>