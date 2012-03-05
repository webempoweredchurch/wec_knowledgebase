
#
# Table structure for table 'tx_wecknowledgebase_comments'
#
CREATE TABLE tx_wecknowledgebase_comments (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	kb_uid int(11) DEFAULT '0' NOT NULL,
	user_uid int(11) DEFAULT '0' NOT NULL,
	comment_text text NOT NULL,
	name text NOT NULL,
	email text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure to add for table 'tt_news'
#
CREATE TABLE tt_news (
	tx_weckb_tutorial_content blob NOT NULL,
    tx_weckb_tutorial_version_info tinytext NOT NULL,
    tx_weckb_tutorial_image text NOT NULL,
	tx_weckb_click_count int(11) DEFAULT '0' NOT NULL,
	tx_weckb_comment_count int(11) DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Table structure to add for table 'tt_news_category'
#
CREATE TABLE tt_news_cat (
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
);
