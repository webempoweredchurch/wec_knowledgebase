<?php
/***************************************************************
* Copyright notice
*
* (c) 2005-2008 Christian Technology Ministries International Inc.
* All rights reserved
*
* This file is part of the Web-Empowered Church (WEC)
* (http://WebEmpoweredChurch.org) ministry of Christian Technology Ministries 
* International (http://CTMIinc.org). The WEC is developing TYPO3-based
* (http://typo3.org) free software for churches around the world. Our desire
* is to use the Internet to help offer new life through Jesus Christ. Please
* see http://WebEmpoweredChurch.org/Jesus.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed  in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('tt_news').'pi/class.tx_ttnews.php');

/**
 * Plugin 'WEC Knowledgebase' for the 'wec_knowledgebase' extension.
 *
 * @author	Web-Empowered Church Team <knowledgebase(at)webempoweredchurch.org>
 *
 * The knowledgebase extends tt_news and adds the following features:
 *	1) Add comments to a single record
 *		Admin user can delete comments
 *	2) Count popularity of views of a record.
 *	3) Able to sort list by popularity/date-most recent/alphabetical
 *	4) Add Flash and/or Video content for separate sections (i.e., tutorial)
 *	5) Revised template that supports Knowledgebase look by default
 *
 */
class tx_wecknowledgebase_pi1 extends tx_ttnews {
	var $prefixId 		= 'tx_wecknowledgebase_pi1';		// Same as class name
	var $scriptRelPath 	= 'pi1/class.tx_wecknowledgebase_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey 		= 'wec_knowledgebase';	// The extension key.
	var $pi_checkCHash 	= FALSE; // was TRUE in tt_news...but for debug...

	var $rec_uid;		 // keep track of record UID
	var $rec_clickCount; // click count (so don't have to retrieve again)
	var $isAdmin;		// if administrator (so can delete comments and ...)
	var $hasTutorial;
	var $formErrorText; // text of error on form
	var $freeCap;		// for use by sr_freeCap image captcha

	var $tutorialsList; // list of tutorials on page
	var $didSearch;		// if did search

	/**
	* Init: Initialize the extension
	*
	* @param	array		$conf  the TypoScript configuration
	* @return	void		No return value needed.
	*/
	function init($conf) {
		parent::init($conf);

		$this->rec_uid = 0;
		$this->didSearch = 0;

		// FORCE load of local languages from tt_news (parent)
		$this->extKey = 'tt_news';
		$this->scriptRelPath = 'pi/class.tx_ttnews.php';
		$this->LOCAL_LANG_loaded = false;
		$this->pi_loadLL(); // Loading language-labels for tt_news
		$this->extKey = 'wec_knowledgebase';
		$this->scriptRelPath = 'pi1/class.tx_wecknowledgebase_pi1.php';

		// then load current language labels from wec_knowledgebase
		$tempLOCAL_LANG = t3lib_div::readLLfile(t3lib_extMgm::extPath('wec_knowledgebase').'pi1/locallang.php' ,$this->LLkey);

		// finally, merge the language labels from tt_news with wec_knowledgebase
		if (is_array($tempLOCAL_LANG)) {
		  foreach (array_keys($tempLOCAL_LANG) as $language) {
			if ($tempLOCAL_LANG[$language]) {
				// go through and see if any TYPOScript changes...if so,then override tempLOCAL_LANG
				foreach ($tempLOCAL_LANG[$language] as $key => $value) {
					if ($v = $conf['_LOCAL_LANG.'][$language.'.'][$key]) {
						$tempLOCAL_LANG[$language][$key] = $v;
					}
				}
				// now merge temp with existing locallang
				$this->LOCAL_LANG[$language] = array_merge(is_array($this->LOCAL_LANG[$language]) ? $this->LOCAL_LANG[$language] : array(),$tempLOCAL_LANG[$language]);
			}
		  }
		  $this->LOCAL_LANG_loaded = true;
		}

		// read in flexform values
		$this->config['use_sidebar'] 		 = $this->getConfigVal($this, 'use_sidebar', 's_knowledgebase');
		$this->config['use_actionbar'] 		 = $this->getConfigVal($this, 'use_actionbar', 's_knowledgebase');
		$this->config['use_related'] 		 = $this->getConfigVal($this, 'use_related', 's_knowledgebase');
		$this->config['tutorialplayer_code'] = $this->getConfigVal($this, 'tutorialplayer_code', 's_knowledgebase');
	 	$this->config['use_comments']		 = $this->getConfigVal($this, 'use_comments', 's_knowledgebase');
		$this->config['login_to_post_comments'] = $this->getConfigVal($this, 'login_to_post_comments', 's_knowledgebase');
		$this->config['getallcomments_email']= $this->getConfigVal($this, 'getallcomments_email', 's_knowledgebase');
		$this->config['getallcomments_fromEmail'] = $this->getConfigVal($this, 'getallcomments_fromEmail', 's_knowledgebase');
		$this->config['commentform_required_fields'] = $this->getConfigVal($this, 'commentform_required_fields', 's_knowledgebase');

		// SPAM
		$this->config['html_tags_allowed'] = $this->getConfigVal($this, 'html_tags_allowed', 's_knowledgebase');
		$this->config['use_captcha'] = $this->getConfigVal($this, 'use_captcha', 's_knowledgebase');
		$this->config['numlinks_allowed'] = $this->getConfigVal($this, 'numlinks_allowed', 's_knowledgebase');
		$this->config['filter_wordlist'] = $this->getConfigVal($this, 'filter_wordlist', 's_knowledgebase');
		$this->config['filter_word_handling'] = $this->getConfigVal($this, 'filter_word_handling', 's_knowledgebase');

		// SET if cached or not (only cache if use comments)
		if ($this->config['use_comments']) {
			$GLOBALS['TSFE']->set_no_cache(); 
		}
		
		// Set if isAdmin
		$this->isAdmin = false;
		if ($GLOBALS["TSFE"]->loginUser) {
			$adminList = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'administrator_list', 's_knowledgebase');
			$adminList = explode(",",$adminList);
			for ($i = 0; $i < count($adminList); $i++) {
				if (!strcmp($adminList[$i], $GLOBALS["TSFE"]->fe_user->user['username']) || ($GLOBALS["TSFE"]->fe_user->user['uid'] == intval($adminList[$i]))) {
					$this->isAdmin = true;
					break;
				}
			}
		}

		// add image captcha if loaded
		$this->freeCap = 0;
		if ($this->config['use_comments'] && $this->config['use_captcha'] && t3lib_extMgm::isLoaded('sr_freecap')) {
			require_once(t3lib_extMgm::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php');
			$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
		}

		// handle grabbing any postVars and adding them to piVars
		if ($postVars = t3lib_div::_POST('tx_wecknowledgebase')) {
			foreach ($postVars as $key => $val) {
				if (!$this->piVars[$key])
					$this->piVars[$key] = $val;
			}
		}

		// handle if posted comment, then jump to postComment...
		if ($v = $this->piVars['commentUID']) {
			if (!$this->postComment($this->piVars)) {
				// will handle form errors later in code...
			}
		}
		else if ($v = $this->piVars['delComment']) {
			$this->deleteComment($v);
		}
		else if ($v = $this->piVars['editUID']) {
			$this->saveAdminEdit($this->piVars);
		}
		else if ($v = $this->piVars['showtutorial']) {
			$this->showTutorial($v,$conf);
			unset($this->piVars['showtutorial']);
		}
	}

	/**
	 * calls main news function. this is added so will be wrapped in div class="tx_wecknowledgebase" (for CSS)
	 *
	 * @param	string		$content : function output is added to this
	 * @param	array		$conf : configuration array
	 * @return	string		$content: complete content generated by the tt_news plugin
	 */
	function main_news($content, $conf) {
		$content = parent::main_news($content,$conf);
		
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * this should ONLY be called if static template is not installed
	 *
	 * @param	string		$content : function output is added to this
	 * @param	array		$conf : configuration array
	 * @return	string		$content: complete content generated by the tt_news plugin
	 */
	function main($content, $conf) {
		if ($conf['isLoaded'] != 'yes') {
			$this->pi_loadLL();
		    return $this->pi_getLL('errorIncludeStatic');
		}		
		
		return $this->pi_wrapInBaseClass($content);
	}
	/**
	 * Fills in the markerArray with data for a knowledgebase item
	 *
	 * @param	array		$row : result row for a news/kb item
	 * @param	array		$textRenderObj : conf vars for the current template
	 * @return	array		$markerArray: filled marker array
	 */
	function getItemMarkerArray ($row,$textRenderObj='displaySingle')       {
		$markerArray = parent::getItemMarkerArray($row,$textRenderObj);

		// show comments...
		if ($this->config['use_comments']) {
			$markerArray['###KB_SHOWCOMMENTS###'] = '<a name="kb_comments">'.$this->showCommentSection($row['uid'],$row['tx_weckb_comment_count']).'</a>';
		}

		// add a click count if this is single item display
		if (!strcmp($textRenderObj,'displaySingle')) {
			// ADD CLICK COUNT
			$this->rec_uid = $row['uid']; // save for displaySingle
			$this->rec_clickCount = $row['tx_weckb_click_count'];
			$markerArray['###KB_CLICKCOUNT###'] = $this->pi_getLL('num_clicks','# page views:').$this->rec_clickCount;
		}
		return $markerArray;
	}

	/**
	 * Processes the extra markers that are used in WEC Knowledgebase
	 *
	 * @param	array		$markerArray: existing markers that will add onto
	 * @param	array		$row:	result row for news/kb item
	 * @param	array		$lConf: conf vars for the current template
	 * @param	object		$parentObj: the parent tt-news object
	 * @return	array		$markerArray: filled marker array
	 */
	function extraItemMarkerProcessor($markerArray, $row, $lConf, $parentObj) {
		// reset local_cObj because may be wrong if displayList has been called...
		$parentObj->local_cObj->start($row, 'wec_knowledgebase');

		// we only want to deal with displaySingle markers below...so don't process anything else
		// (assume lConf['tutorial_stdWrap'] is only in displaySingle
		if (!isset($lConf['tutorial_stdWrap.']))
			return $markerArray;

		// ADD TUTORIAL CONTENT
		// must have a player configured or TypoScript otherwise will not show...
		$tutorialContent = $this->getTutorialContent($row,$lConf,$parentObj);
		$markerArray['###KB_TUTORIAL_RESOURCE###'] = '<span id="resource">'.$tutorialContent.'</span>';

		$headerTagStart = $parentObj->pi_getLL('headerTagStart','<h3>');
		$headerTagEnd = $parentObj->pi_getLL('headerTagEnd','</h3>');

		if (strlen($tutorialContent)) {
			$markerArray['###KB_TUTORIAL_HEADER###'] = $headerTagStart.$parentObj->pi_getLL('tutorial_header','Tutorial').$headerTagEnd;
			$markerArray['###KB_TUTORIAL_HEADER###'] = '<a name="kb_tutorial">'.$markerArray['###KB_TUTORIAL_HEADER###'].'</a>';

			$tutorialImg = $row['tx_weckb_tutorial_image'];
			if (!$tutorialImg) {
				if ($tutorialImg = $parentObj->conf['defaultTutorialIcon'])
					$tutorialImg = $parentObj->cObj->fileResource($tutorialImg);
			}

			if ($tutorialImg) {
				// add the link wrap to the image
				$tutorialWrap = $lConf['tutorial_stdWrap.'];
				$tutorialWrap['wrap'] = "|";
				$tutorialWrap['data'] = "";
				$tutorialImg = $parentObj->local_cObj->stdWrap($parentObj->formatStr($tutorialImg), $tutorialWrap);
				// then add it to the template markerArray
				$markerArray['###KB_TUTORIAL_IMAGE###'] = '<span id="image">'.$tutorialImg.'</span>';
			}
		}

		// ADD LINKS, if available
		if (strlen($markerArray['###NEWS_LINKS###'])) {
			$markerArray['###TEXT_LINKS###'] = 	$headerTagStart.$parentObj->pi_getLL('links_header','Links').$headerTagEnd;
		}

		// ADD FILELINKS, if available
		if (strlen($markerArray['###FILE_LINK###'])) {
			$markerArray['###TEXT_FILES###'] = 	$headerTagStart.$parentObj->pi_getLL('filelinks_header','Files').$headerTagEnd.$markerArray['###TEXT_FILES###'] ;
		}

		// ADD VERSION INFO, if available
		if (strlen($row['tx_weckb_tutorial_version_info']))
			$markerArray['###KB_VERSION_INFO###']= '<div class="news-version-info">'.$parentObj->pi_getLL('version_info_text','For Version: ').$row['tx_weckb_tutorial_version_info'].'</div>';

		// ADD ADMIN FOR FE-EDITING
		if ($parentObj->isAdmin) {
			if ($v = $parentObj->piVars['admin_edit']) {
				$markerArray['###KB_ADMIN###'] = $parentObj->adminEdit($v);
			}
			else {
				$parentObj->piVars['admin_edit'] = $row['uid'];
				$adminLink = '<a href="'.$parentObj->pi_linkTP_keepPIvars_url().'">'.$parentObj->pi_getLL('admin_edit_button','Admin Edit').'</a>';
				$adminBtn = '<div class="news-button">'.$adminLink.'</div>';
				$markerArray['###KB_ADMINEDIT_LINK###'] = $adminLink;
				$markerArray['###KB_ADMINEDIT_BTN###'] = $adminBtn;
				unset($parentObj->piVars['admin_edit']);
			}
		}
		else {
			$markerArray['###KB_ADMIN###'] = '';
		}

		// USE RELATED LINKS
		if (!$parentObj->config['use_related']) {
			$markerArray['###NEWS_RELATEDBYCATEGORY###'] = '';
			$markerArray['###TEXT_RELATEDBYCATEGORY###'] = '';
			$markerArray['###NEWS_RELATED###'] = '';
			$markerArray['###TEXT_RELATED###'] = '';
		}

		// ADD SIDEBAR
		if ($parentObj->config['use_sidebar']) {
			$markerArray['###KB_SIDEBAR_HEADER###'] = '<div class="news-sidebar-header">'.$parentObj->pi_getLL('sidebar_header','Your Options').'</div>';

			// put in "go back" link
			$prevPageText = $parentObj->pi_getLL('prev_page', 'Prev Page');
			if ($parentObj->config['backPid'])
				$backLink = '<a href="'.$parentObj->pi_getPageLink($parentObj->config['backPid']).'">'.$prevPageText.'</a>';
			else
				$backLink = '<a href="#" onclick="history.go(-1);return false;">'. $prevPageText .'</a>';
			$markerArray['###KB_SIDEBAR_BACK###'] = '<div class="news-sidebar-links">' . $backLink . '</div>';

			// add comment button if allowed to post
			if ($parentObj->config['use_comments'] && ((!$parentObj->config['login_to_post_comments']) || $GLOBALS["TSFE"]->fe_user->user['uid'])) {
				$markerArray['###KB_SIDEBAR_ADDCOMMENT###'] = '<div class="news-sidebar-links"><a href="#kb_comments" onclick="makeComment();">'.$parentObj->pi_getLL('commentform_makeComment','Add Your Comment').'</a></div>';
			}

			// add jump down to tutorial
			if (strlen($tutorialContent)) {
				$markerArray['###KB_SIDEBAR_SHOWTUTORIAL###'] = '<div class="news-sidebar-links"><a href="#kb_tutorial">'.$parentObj->pi_getLL('view_tutorial','View Tutorial').'</a></div>';
			}

			// add print this page
			// add email this page
			// add rating

			if ($parentObj->isAdmin) {
				$addParam['admin_edit'] = intval($parentObj->tt_news_uid);
				$adminLink = '<a href="'.$parentObj->pi_linkTP_keepPIvars_url($addParam).'">'.$parentObj->pi_getLL('admin_edit_button','Admin Edit').'</a>';
				$markerArray['###KB_SIDEBAR_ADMINEDIT###'] = '<div class="news-sidebar-links">'.$adminLink.'</div>';
			}
		}

		// ADD ACTION BAR
		// show an action bar at top to show what actions are available for a single article
		if ($parentObj->config['use_actionbar']) {
			$actionbarStr = '<div class="news-actionbar">';
			$actionbarCount = 0;

			// get the full URL...
			// the config.prefixLocalAnchors = all needs to be set or this will not work...
			$dn = dirname($_SERVER['PHP_SELF']);

			if (($dn != '/') && ($dn != '\\' ))
				$thisURL = 'http://'.$_SERVER['HTTP_HOST'].$dn.'/';
			else
				$thisURL = 'http://'.$_SERVER['HTTP_HOST'].'/';

			$thisURL .= $parentObj->pi_getPageLink($GLOBALS['TSFE']->id,'');
			if ($thisURL{(strlen($thisURL)-1)} == '/') $thisURL = substr($thisURL,0,strlen($thisURL)-1);

			// if tutorial (flash/video)...then add
			if (strlen($markerArray['###KB_TUTORIAL_RESOURCE###'])) {
				$markerArray['###KB_TUTORIAL_RESOURCE###'] = '<a name="kb_tutorial">'.$markerArray['###KB_TUTORIAL_RESOURCE###'].'</a>';
				$actionbarStr .= ' <a href="#kb_tutorial">'.$parentObj->pi_getLL('actionbar_tutorial','View Tutorial').'</a> |';
				$actionbarCount++;
			}

			// if comments...then add...
			if (strlen($markerArray['###KB_SHOWCOMMENTS###'])) {
				$markerArray['###KB_SHOWCOMMENTS###'] = '<a name="kb_comments">'.$markerArray['###KB_SHOWCOMMENTS###'].'</a>';
				$actionbarStr .= ' <a href="#kb_comments">'.$parentObj->pi_getLL('actionbar_comments','View Comments').'</a> |';
				$actionbarCount++;
			}

			// if related articles...then add...
			if ($markerArray['###NEWS_RELATEDBYCATEGORY###']) {
				$markerArray['###NEWS_RELATEDBYCATEGORY###'] = '<a name="kb_related">'.$markerArray['###NEWS_RELATEDBYCATEGORY###'].'</a>';
				$actionbarStr .= ' <a href="#kb_related">'.$parentObj->pi_getLL('actionbar_related','View Related Articles').'</a> |';
				$actionbarCount++;
			}

			// remove actionbar end | if exists
			if ($actionbarStr[strlen($actionbarStr)-1] == '|')  // remove ending |
				$actionbarStr = substr($actionbarStr,0,strlen($actionbarStr)-2);

			// end of actionbar
			$actionbarStr .= '</div>';

			// only make an actionbar if more than one element...
			if ($actionbarCount > 1)
				$markerArray['###KB_ACTIONBAR###'] = $actionbarStr;
		}

		// now return the updated markerArray
		return $markerArray;
	}

	/**
	 * Get the tutorial content for this knowledgebase item
	 *
	 * @param	array		$row:	result row for news/kb item
	 * @param	array		$lConf: conf vars for the current template
	 * @param	object		$parentObj: the parent tt-news object
	 * @return	string		$tutorialContent: the tutorial HTML content
	 */
	function getTutorialContent($row,$lConf,$parentObj) {
		$tutorialContent = '';
		if (($tutorialFile = $row['tx_weckb_tutorial_content']) && ($tutorialFilePlayer = $parentObj->config['tutorialplayer_code'])) {
			$tutorialFile = '/uploads/tx_wecknowledgebase/'.$tutorialFile;
			$tutorialFilePlayer = str_replace("#FILE#",$tutorialFile, $tutorialFilePlayer);
			$tutorialFilePlayer = str_replace("#IMAGE#",$tutorialImage, $tutorialFilePlayer);
			$tutorialContent = $tutorialFilePlayer;
		}
		else if ($tutorialFile) {
			$tutorial_stdWrap = t3lib_div::trimExplode('|', $lConf['tutorial_stdWrap.']['wrap']);
			$newsTutorial = $parentObj->local_cObj->stdWrap($parentObj->formatStr($tutorialFile), $lConf['tutorial_stdWrap.']);
			$tutorialContent = $newsTutorial.$tutorial_stdWrap[1];
		}
		return $tutorialContent;
	}

	//
	/**
	 * Show the tutorial only. This is called externally.
	 *
	 * @param	integer		$tutorialUID: the tutorial uid to show
	 * @param	array		$lConf: conf vars for the current template
	 * @return	boolean		either false if did not find it, or will change to location and play tutorial
	 */
	function showTutorial($tutorialUID,$lConf) {
		// grab record
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'uid='.$tutorialUID, $groupBy = '', '', $limit = '');
		if (!$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) return false;

		// set row for Typoscript processing (if any)
		$this->local_cObj->start($row, 'wec_knowledgebase');

		// grab tutorial content
		$content = $this->getTutorialContent($row,$lConf['displaySingle.'],$this);

		// grab href from that and change to that location
		if ($m = preg_match("/<a href=\"[\"']?([^\"' >]+)[\"' >]/i", $content, $matches) && $matches[1]) {
			header('Location: '.t3lib_div::locationHeaderURL($matches[1]));
			exit(1);
		}

		return false;
	}

	/**
	 * Display single view. Extends tt-news and adds knowledgebase features
	 *
	 * @return	string		$content: the content to be shown for the single display
	 */
	function displaySingle() {
		$content = parent::displaySingle();

		if (!$this->config['use_sidebar'] ) {
			$content = $this->cObj->substituteSubpart($content,'###SHOW_SIDEBAR###','');
		}

		// Increase click count (popularity) every time display single record
		if ($this->rec_uid) {
			$updArray['tx_weckb_click_count'] = $this->rec_clickCount + 1;
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery ('tt_news', 'uid='.intval($this->rec_uid), $updArray);
		}

		// remove any extra ### template tags...
		$content = ereg_replace('###[A-Za-z_1234567890]+###', '', $content);

		return $content;
	}

	/**
	 * Post the comment to the database and show it. Filter it, if configured.
	 *
	 * @param	array		$commentVar:	comment form POST results
	 * @return	boolean		return false if post comment failed; changes page if post successful
	 */
	function postComment($commentVar) {
		$theMessage = $commentVar['message'];
		$theName = $commentVar['name'];

		// check if required fields exist
		$requiredFieldError = "";

		$reqF = $this->config['commentform_required_fields'];
		if (!$commentVar['message']) $requiredFieldError .= $this->pi_getLL('commentform_message','comment');
		if (!$commentVar['name'] && ($reqF == 'name' || $reqF == 'name_email')) 	 $requiredFieldError .= ((strlen($requiredFieldError)) ? ', ' : '') . $this->pi_getLL('commentform_name','Name');
		if (!$commentVar['email'] && ($reqF == 'email' || $reqF == 'name_email')) 	 $requiredFieldError  .= ((strlen($requiredFieldError)) ? ', ' : '') . $this->pi_getLL('commentform_email','Email');
		if (strlen($requiredFieldError)) {
			$this->formErrorText = $this->pi_getLL('commentform_required_blank','The following field(s) need to be filled in:') . $requiredFieldError;
			return false;
		}

		// captcha check (if set)
		if (is_object($this->freeCap) && !$this->freeCap->checkWord($commentVar['captcha_response'])) {
			$this->formErrorText = $this->pi_getLL('captcha_bad','Please try entering the text for the Image Check again.');
			return false;
		}

		$filterMessage = $this->filterPost($theMessage);

		$filterName = $this->filterPost($theName);
		if (($filterMessage != '0') || ($filterName != '0')) {
			switch ($this->config['filter_word_handling']) {
				case 'filter':
					$theMessage = ($filterMessage != '0') ? $filterMessage : $theMessage;
					$theName = ($filterName != '0') ? $filterName : $theName;
					break;
				default:
					// discard post
					$this->formErrorText = $this->pi_getLL('filter_delete', 'Your comment had unacceptable words in it and could not be posted.');
					return false;
					break;
			}
		}
		// check for number of links allowed
		if (isset($this->config['numlinks_allowed'])) {
			$numLinksAllowed = (int) $this->config['numlinks_allowed'];
			$msg = html_entity_decode($theMessage,ENT_COMPAT,$GLOBALS['TSFE']->renderCharset);
   			preg_match_all("/<\s*a\s+[^>]*href\s*=\s*[\"']?([^\"' >]+)[\"' >]/isU",$msg, $matches, PREG_PATTERN_ORDER);
			$numLinksFound = count($matches[0]);

   			preg_match_all("/http:\/\//isU",$msg, $matches, PREG_PATTERN_ORDER);
   			$numLinksFound += count($matches[0]);

			if ($numLinksFound > $numLinksAllowed) {
				if ($this->config['numlinks_allowed'] > 0)
					$this->formErrorText = $this->pi_getLL('too_many_links','Too many links found -- only allowed ').$numLinksAllowed;
				else
					$this->formErrorText = $this->pi_getLL('no_links_allowed','You cannot post any links here.');
				return false;
			}
		}

		// add comment to db
		//------------------------------------------------------
		$insertVars['pid'] = $this->pid_list;
		$insertVars['kb_uid'] = $commentVar['commentUID'];
		$insertVars['name'] = $theName; //$commentVar['name'];
		$insertVars['comment_text'] = $theMessage; //$commentVar['message'];
		$insertVars['email'] = $commentVar['email'];
		$insertVars['crdate'] = mktime();

		if ($GLOBALS["TSFE"]->fe_user) $insertVars['user_uid'] = $GLOBALS["TSFE"]->fe_user->user['uid'];
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_wecknowledgebase_comments', $insertVars);
		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"failed on INSERT comments "));

		// increment comment count
		$thisdate = mktime();
		$query = 'UPDATE tt_news SET tx_weckb_comment_count=tx_weckb_comment_count+1 WHERE uid='.$commentVar['commentUID'];
		$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		if (mysql_error())	t3lib_div::debug(array(mysql_error(),$query));

		// email out comment to admin (if set)
		if (strlen($this->config['getallcomments_email']) && (strpos($this->config['getallcomments_email'],"@"))) {
			$commentEmailText = $this->pi_getLL('notifyemail_intro', 'Comment posted on ');
			unset($this->piVars['commentUID']);
			unset($this->piVars['name']);
			unset($this->piVars['email']);
			unset($this->piVars['captcha_response']);
			unset($this->piVars['message']);
			$dn = dirname($_SERVER['PHP_SELF']);
			$thisURL = (($dn != '/') && ($dn != '\\' )) ? 'http://'.$_SERVER['HTTP_HOST'].$dn.'/' : 'http://'.$_SERVER['HTTP_HOST'].'/';
			$thisURL .= $this->pi_linkTP_keepPIvars_url();
			$commentEmailText .= $thisURL . "\n\n";
			$commentEmailText .= $this->pi_getLL('notifyemail_postedby','posted by') . $insertVars['name'] . " [".$insertVars['email']."]\n\n";
			$commentEmailText .= "\n".$this->pi_getLL('notifyemail_comments','comments posted:')."\n\n";
			$commentEmailText .= $insertVars['comment_text'];
			$emailSubject = $this->pi_getLL('notifyemail_subject', 'NOTIFY: Comments Posted on KB');
			if ($v = $this->config['getallcomments_fromEmail'])
				$emailFrom = $v;
			else if ($v = $commentVar['email'])
				$emailFrom = $v;
			else if ($v = $this->pi_getLL('notifyemail_fromEmail'))
				$emailFrom = $v;
			else
				$emailFrom = 'nobody@nowhere.org';
			$emailFrom = 'From: <'. $emailFrom . '>';
			mail($this->config['getallcomments_email'], $emailSubject, $commentEmailText, $emailFrom);
		}

		// now redirect to url (so getvars gone)
		unset($this->piVars['commentUID']);
		unset($this->piVars['name']);
		unset($this->piVars['comment_text']);
		unset($this->piVars['email']);
		unset($this->piVars['message']);
		unset($this->piVars['captcha_response']);
		$thisURL = $this->pi_linkTP_keepPIvars_url();
		header('Location: '.t3lib_div::locationHeaderURL($thisURL));
	}

	/**
	 * Show all the comments and comment form.
	 *
	 * @param	integer		$thisUID:	UID of current knowledgebase entry
	 * @param	integer		$commentCount:	number of comments posted.
	 * @return	string		the comment section content
	 */
	function showCommentSection($thisUID, $commentCount) {
		$markerArray['###KB_COMMENT_HEADER###'] = $this->pi_getLL('commentHeader','User Comments');
		$markerArray['###KB_COMMENTS###'] = $this->showComments($thisUID, $commentCount);

		// ADD COMMENT BUTTON & FORM if allowed to post
		//
		// if either do not have to login or logged in, then can enter comments
		$canPost = (!$this->config['login_to_post_comments']) || $GLOBALS["TSFE"]->fe_user->user['uid'];
		if  ($canPost) {
			$markerArray['###KB_MAKECOMMENT_BUTTON###'] = '<div  class="news-button"><a href="#" onclick="javascript:makeComment(); return false;">'.$this->pi_getLL('commentform_makeComment',"Add Your Comment").'</a></div>';
			$markerArray['###KB_COMMENT_FORM###'] = $this->showCommentForm($thisUID);
		}
		$markerArray['###KB_NO_COMMENTS###'] = ($commentCount > 0) ? "" : (!$canPost ? $this->pi_getLL('no_comments_login_req','No comments yet. Please login to post a comment.') : $this->pi_getLL('no_comments','No comments yet...add your questions or comments here.') );
		$commentTemplate = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_KB_SHOWCOMMENTS###'));
		$commentContent = $this->cObj->substituteMarkerArrayCached($commentTemplate, $markerArray, $subpartArray, $wrappedSubPartArray);

		// clear out any empty template fields
		$commentContent = ereg_replace('###[A-Za-z_1234567890]+###', '', $commentContent);

		return $commentContent;
	}

	/**
	 * Show the comments for this page.
	 *
	 * @param	integer		$thisUID:	UID of current knowledgebase entry
	 * @param	integer		$commentCount:	number of comments posted.
	 * @return	string		the comments content
	 */
	function showComments($thisUID, $commentCount) {
		$commentContent = "";
		if ($commentCount > 0) {
			// grab all the comments to list...
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wecknowledgebase_comments', 'kb_uid='.$thisUID, $groupBy = '', $orderBy = 'crdate DESC', $limit = '');
			$commentSingleForm = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_KB_COMMENT_SINGLE###'));
			// go through and add each comment
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// strip off any tags not allowed
				$commentText = stripslashes($row['comment_text']);
				$tagsAllowed = $this->config['html_tags_allowed'];
				if (strlen($tagsAllowed)) {
					$commentText = html_entity_decode($commentText,ENT_COMPAT,$GLOBALS['TSFE']->renderCharset);
					if ($tagsAllowed != 1 && strlen($tagsAllowed)) {
						$commentText = strip_tags($commentText,$tagsAllowed);
					}
				}
				// add breaks in lines
				$commentText = str_replace("\r\n","<br />",$commentText);

				// fill in the comment markers
				$cMarkerArray['###KB_COMMENT_MESSAGE###'] = $commentText;
				$cMarkerArray['###KB_POSTEDBY_LABEL###'] = $this->pi_getLL('posted_by','Posted By:');
				$cMarkerArray['###KB_COMMENT_NAME###'] = $row['name'];

				$emailSep = $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'] ? $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'] : '(at)';
				$showEmail = str_replace('@', $emailSep, $row['email']);
				$showEmailLink = '<a href="javascript:linkTo_UnCryptMailto(\''.$GLOBALS['TSFE']->encryptEmail('mailto:'.$row['email']).'\');">' . $showEmail . '</a>';
				$cMarkerArray['###KB_COMMENT_EMAIL###'] = $showEmail;
				$cMarkerArray['###KB_COMMENT_EMAIL_LINK###'] = $showEmailLink;
				$cMarkerArray['###KB_COMMENT_DATETIME###'] = date($this->pi_getLL('posted_date_format'),$row['crdate']);
				if (!$row['email']) {
					$commentSingleForm = $this->cObj->substituteSubpart($commentSingleForm,'###KB_SHOW_EMAIL###','');
				}

				if ($this->isAdmin) {
					$getVars = t3lib_div::_GET();
					$getVars['tx_wecknowledgebase_pi1[delComment]'] = $row['uid'];
					$thisURL = $this->pi_getPageLink($GLOBALS['TSFE']->id,'', $getVars);
					$cMarkerArray['###KB_COMMENT_ADMIN###'] = '<a href="'.$thisURL.'">'.$this->pi_getLL('admin_delete','delete').'</a>';
				}

				$commentSingle = $this->cObj->substituteMarkerArrayCached($commentSingleForm, $cMarkerArray, array(), array());
				$commentContent .= $commentSingle;
			}

			// clear out any empty template fields
			$commentContent = ereg_replace('###[A-Za-z_1234567890]+###', '', $commentContent);
		}

		return $commentContent;
	}

	/**
	 * Show the comment form for this page.
	 *
	 * @param	integer		$thisUID:	UID of current knowledgebase entry
	 * @return	string		the comment form content
	 */
	function showCommentForm($thisUID) {
		$commentContent = "";
		$commentForm = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_KB_COMMENTFORM###'));

		$markerArray['###KB_COMMENTFORM_ERROR###'] = $this->formErrorText;
		if ($this->formErrorText) // jump down to comment form if we have an error
			$markerArray['###KB_COMMENTFORM_ERROR###'] .= '<script type="text/javascript">window.location.hash=\'addYourComment\';</script>';

		$showCommentForm = ($this->piVars['commentUID']) ? 'block' : 'none';
		$markerArray['###KB_COMMENTFORM_TOGGLE###'] = '<div id="CommentFormToggle" style="display:'.$showCommentForm.';">';
		$markerArray['###KB_COMMENTFORM_TOGGLE_END###'] = '</div>';

		$markerArray['###KB_COMMENTFORM_HIDDEN_VARS###'] = '
			<input type="hidden" name="tx_wecknowledgebase_pi1[commentUID]" value="'.$thisUID.'" />

			<script type="text/javascript">
			//<![CDATA[
			function makeComment() {
				commentForm = document.getElementById("CommentFormToggle");
				if (!commentForm) return false;
				if (commentForm.style.display == "none")
					commentForm.style.display = "block";
				else
					commentForm.style.display = "none";
				return false;
			}
			//]]>
			</script>
			';

		$markerArray['###KB_COMMENTFORM_HEADER###'] = $this->pi_getLL('commentform_header','Add Your Comment:');
		$markerArray['###KB_COMMENTFORM_NAME_LABEL###'] = $this->pi_getLL('commentform_name','Name');
		$markerArray['###KB_COMMENTFORM_EMAIL_LABEL###'] = $this->pi_getLL('commentform_email','Email');
		$markerArray['###KB_COMMENTFORM_MESSAGE_LABEL###'] = $this->pi_getLL('commentform_message','Comment');
		$markerArray['###KB_COMMENTFORM_SUBMIT_BTN###'] = $this->pi_getLL('commentform_submitBtn','Add Comment');

		unset($this->piVars['captcha_response']);
		$markerArray['###KB_COMMENTFORM_ACTION_URL###'] = $this->pi_linkTP_keepPIvars_url();

		$markerArray['###KB_VALUE_NAME###'] = $this->piVars['name'];
		$markerArray['###KB_VALUE_EMAIL###'] = $this->piVars['email'];
		$markerArray['###KB_VALUE_MESSAGE###'] = $this->piVars['message'];

		// Add Image Captcha Support...
		if (is_object($this->freeCap) && !$editID) {
			$markerArray = array_merge($markerArray, $this->freeCap->makeCaptcha());
		} else
			$subpartArray['###CAPTCHA_INSERT###'] = '';

		$commentContent .= $this->cObj->substituteMarkerArrayCached($commentForm, $markerArray, $subpartArray, $wrappedSubPartArray);

		// clear out any empty template fields
		$commentContent = ereg_replace('###[A-Za-z_1234567890]+###', '', $commentContent);
		return $commentContent;
	}

	/**
	 * Remove a comment from this page.
	 *
	 * @param	integer		$whichUID:	UID of comment to delete
	 * @return	boolean		return false if not successful otherwise go to current page
	 */
	function deleteComment($whichUID) {
		// Verify that can delete the message -- must be admin
		if ($this->isAdmin) {
			// then delete it
			$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery("tx_wecknowledgebase_comments","uid=".$whichUID);
			if (mysql_error()) {
				t3lib_div::debug(array(mysql_error(),"DELETE FROM tx_wecknowledgebase_comments WHERE uid=".$whichUID));
				return false; // want to return if delete not successful
			}
			// check if delete was successful
			else if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 0) {
				return false;
			}
			else {
				// decrease the comment count
				// to ensure accuracy, we grab the actual comments and then decrease from there
				if ($this->tt_news_uid) {
					$query = 'SELECT COUNT(*) FROM tx_wecknowledgebase_comments WHERE kb_uid = '.$this->tt_news_uid;
					$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
					$commentCount = $row[0];

					$query = 'UPDATE tt_news SET tx_weckb_comment_count=' . $commentCount . ' WHERE uid=' . $this->tt_news_uid;
					$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
					if (mysql_error())	t3lib_div::debug(array(mysql_error(),$query));
				}
				// remove the delComment getVar and reshow
				unset($this->piVars['delComment']);
				$thisURL = $this->pi_linkTP_keepPIvars_url();
				header('Location: '.t3lib_div::locationHeaderURL($thisURL));
			}
		}
	}

	/**
	 * Let the Admin edit the page
	 *
	 * @param	integer		$whichUID: the uid of the knowledgebase entry to edit
	 * @return	string		$editContent: the edit form to show
	 */
	function adminEdit($whichUID) {
		// grab the news record to edit
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news', 'uid='.$whichUID, $groupBy = '', '', $limit = '');
		if (!$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) return "";

		$editForm = $this->getNewsSubpart($this->templateCode, $this->spMarker('###TEMPLATE_KB_EDITFORM###'));
//unused right now		$markerArray['###KB_EDITFORM_ERROR###'] = $this->formErrorText;

		$markerArray['###KB_EDITFORM_HIDDEN_VARS###'] = '
			<input type="hidden" name="tx_wecknowledgebase_pi1[editUID]" value="'.$whichUID.'" />
			';

		$markerArray['###KB_EDITFORM_HEADER###'] = $this->pi_getLL('editform_header','Edit This Record');
		$markerArray['###KB_EDITFORM_TITLE###'] = $this->pi_getLL('editform_title','Title');
		$markerArray['###KB_EDITFORM_TEXT###'] = $this->pi_getLL('editform_text','Text');
		$markerArray['###KB_EDITFORM_SUBMIT_BTN###'] = $this->pi_getLL('editform_submitBtn','Update Record');

		$markerArray['###KB_EDITFORM_ACTION_URL###'] = $this->pi_linkTP_keepPIvars_url();

		$markerArray['###KB_VALUE_TITLE###'] = $row['title'];

		// add linebreaks so can "read" article more easily
		$editText = $row['bodytext'];
		$editText = str_replace('<br />','<br />'."\r",$editText);
		$markerArray['###KB_VALUE_TEXT###'] = $editText; //$row['bodytext'];

		$editContent .= $this->cObj->substituteMarkerArrayCached($editForm, $markerArray, $subpartArray, $wrappedSubPartArray);
		// clear out any empty template fields
		$editContent = ereg_replace('###[A-Za-z_1234567890]+###', '', $editContent);

		return $editContent;
	}

	/**
	 * Save the admin edit form and update database with info
	 *
	 * @param	array		$editVars:	the variable to edit
	 * @return	string		$tutorialContent: the tutorial HTML content
	 */
	function saveAdminEdit($editVars) {
		if ($this->isAdmin) {
			// add comment to db
			$insertVars['title'] = $editVars['title'];
			$editText = $editVars['text'];
			$editText = str_replace('<br />'."\r\n",'<br />',$editText);
			$insertVars['bodytext'] = $editText; //$editVars['text'];

			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news', 'uid='.$editVars['editUID'], $insertVars);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),"failed on admin UPDATE query uid=".$editVars['uid']." insertvars=".$insertVars));

			unset($this->piVars['editUID']);
			unset($this->piVars['comment_text']);
			unset($this->piVars['title']);
			unset($this->piVars['admin_edit']);
			$thisURL = $this->pi_linkTP_keepPIvars_url();
			header('Location: '.t3lib_div::locationHeaderURL($thisURL));
		}
	}

	/**
	 * Add the POPULAR, CATDROPDOWN, and TUTORIAL_LIST types
	 *
	 * @param	array		&$obj: the parent object
	 * @return	string		$content: content generated for the given types
	 */
	function extraCodesProcessor(&$obj) {
		if (!strcmp($obj->theCode,'POPULAR')) {
			$curListOrderBy = $obj->config['orderBy'];
			$curLimit = $obj->config['limit'];
			$curAscDesc = $obj->config['ascDesc'];
			$obj->config['orderBy'] = 'tx_weckb_click_count';
			$obj->config['ascDesc'] = 'DESC';
			$obj->config['limit'] = $obj->conf['popularLimit'];
			$content .= $obj->displayList();
			$obj->config['orderBy'] = $curListOrderBy;
			$obj->config['limit'] = $curLimit;
			$obj->config['ascDesc'] = $curAscDesc;
			return $content;
		}
		else if (!strcmp($obj->theCode,'CATDROPDOWN')) {
			$content = $this->displayCatDropdown($obj);
			return $content;
		}
		else if (!strcmp($obj->theCode,'TUTORIAL_LIST')) {
			$content = $this->displayTutorialList($obj);
			return $content;
		}
		else if (!strcmp($obj->theCode,'TUTORIALS_MENU')) {
			$content = $this->displayTutorialsMenu($obj);
			return $content;
		}
		return "";
	}

	/**
	 * Process the POPULAR HEADER and SHOW_CATEGORY markers
	 *
	 * @param	array		$obj: the parent object
	 * @param	array		$markerArray: the current markerArray to add onto
	 * @return	array		updated markerArray
	 */
	function extraGlobalMarkerProcessor($obj, $markerArray) {
		if (!strcmp($obj->theCode,'SEARCH')) { // this tracks if a successful search is done
			$markerArray['###EMPTY_SEARCH###'] = '';
		}

		if (!strcmp($obj->theCode,'POPULAR')) {
			$markerArray['###POPULAR_HEADER###'] = $obj->pi_getLL('popularHeader');
		}
		$curCatUID = $this->piVars['cat'] ? $this->piVars['cat'] : 0;
		// if a list item (and not single item), then show category...
		$markerArray['###SHOW_CATEGORY###'] = '';
		if ($curCatUID && (!strcmp($obj->theCode,'LIST')) && (!$obj->piVars['tt_news'])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ('title','tt_news_cat', 'uid='.$curCatUID);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$markerArray['###SHOW_CATEGORY###'] ='<h2>'.$obj->pi_getLL('showCategory_header','Showing Category: ').$row['title'].'</h2>';
			}
		}
		else if ((!strcmp($obj->theCode,'SEARCH')) && $obj->piVars['swords']) {
			$markerArray['###SHOW_CATEGORY###'] ='<h2>'.$obj->pi_getLL('showSearchResults_header','Search Results: ').'"'.$obj->piVars['swords'].'"</h2>';
		}

		return $markerArray;
	}

	/**
	 * This handles nested_kb which positions categories differently, shows only
	 * categories at level on, and also grabs and shows the count
	 *
	 * @param	array		$lConf: conf vars for the current template
	 * @param	object		$parentObj: the parent object
	 * @return	string		the content to display the category menu
	 */
	function userDisplayCatmenu($lConf, $parentObj) {
		if ($lConf['mode'] == 'nested_kb') {
//			if ($parentObj->config['catSelection'])
//				$parentObj->catExclusive = $parentObj->config['catSelection'];
			// determine categories to show...(from tt_news)
			if ($lConf['catOrderBy'])
				$parentObj->config['catOrderBy'] = $lConf['catOrderBy'];

			$catlistWhere = 'pid IN ('.$parentObj->pid_list.')';
			if ($parentObj->catExclusive && $parentObj->config['categoryMode'] == 1)
				$catlistWhere .= ' AND tt_news_cat.uid IN (' . $parentObj->catExclusive.') ';
			$catlistWhere .= ' AND tt_news_cat.deleted=0 AND tt_news_cat.hidden=0';

			// Grab the current categories from database
			$cArr = array();
			$orderBy = 'parent_category,tt_news_cat.'.$parentObj->config['catOrderBy'];
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tt_news_cat',$catlistWhere,'',$orderBy);

			// load in the category list.
			$curLevelCat = $this->piVars['cat'] ? $this->piVars['cat'] : 0;
			$upLevelCat = -1;
			$theCat = 0;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// grab all items at this level
				if ($row['parent_category'] == $curLevelCat) {
					$cArr[] = $row;
				}
				// if the parent node, then mark it
				else if ($row['uid'] == $curLevelCat) {
					$upLevelCat = $row['parent_category'];
					$theCat = $row['title'];
				}
				// keep track of all items at next level
				else {
					for ($j = 0; $j < count($cArr); $j++) {
						if ($cArr[$j]['uid'] == $row['parent_category']) {
							$cArr2[] = $row;
							break;
						}
					}
				}
				if ($row['parent_category'] == 0) {
					$rootCat = $row['uid'];
				}
			}
			//
			// Set Root menu to level 1 or level 2? (if level 1 = one item, and noRoot_catmenu=1, then can use level 2)
			//
			if ((count($cArr) == 1) && ($cArr[0]['parent_category'] == 0) && $parentObj->conf['noRoot_catmenu']) {
				$cArr = $cArr2;
			}
			// If not at root level, then add an "up" selection
			else if (($curLevelCat != 0) && (($parentObj->conf['noRoot_catmenu'] == 0) || ($cArr[0]['parent_category'] != 0) || !$cArr)) {
				$upObject['up_arrow'] = 1;
				$upObject['go_uid'] = $upLevelCat;
				$upObject['title'] = $parentObj->pi_getLL('Up','Up');
				array_unshift($cArr, $upObject);
			}
			// add select category to top of list
			if (count($cArr)) {
				$menuHeader = $parentObj->local_cObj->stdWrap($parentObj->pi_getLL('catmenuHeader','Select a category:'),$lConf['catmenuHeader_stdWrap.']);
				array_unshift($cArr,$menuHeader);
			}


			// Grab the count for each category
			//-----------------------------------
//			if ($parentObj->pid_list)
//				$catPidWhere = ' AND tt_news.pid IN ('.$parentObj->pid_list.') AND tt_news.deleted=0 AND tt_news.hidden=0';
//			$query = 'SELECT tt_news_cat_mm.uid_foreign FROM tt_news_cat_mm,tt_news WHERE tt_news_cat_mm.uid_local=tt_news.uid' . $catPidWhere;
//			$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
//			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
//				$catCountArray[$row[0]]++;
//			}
			$catCountArray=$this->countItems($parentObj->pid_list);
			
			while (list($key,$val) = each($cArr)) {
				if (is_array($cArr[$key]) && !$cArr[$key]['uid']) { // subcategory
					$cArrSub = $cArr[$key];
					for ($k = 0; $k < count($cArrSub);  $k++) {
						if ($cArrSub[$k]) {
							$thisUID2 = $cArrSub[$k]['uid'];
							$catCount = $catCountArray[$thisUID2];
							if ($catCount == '') $catCount = 0;
							$cArr[$key][$k]['cat_count'] = $catCount;
						}
					}
				}
				else if (!is_string($cArr[$key])) {
					$catCount = $catCountArray[$cArr[$key]['uid']];
					if ($catCount == '') $catCount = 0;
					$cArr[$key]['cat_count'] = $catCount;

				}
			}
			//generate the header
			if ($catHeader = $parentObj->pi_getLL('catDisplayHeader')) {
				$curDisplayCat = $theCat ? $theCat : $parentObj->pi_getLL('catDisplayHeader_all','Show All');
				$headerContent = '<h2>'.$catHeader.$curDisplayCat.'</h2>';
			}

			// generate the cat menu...
			$content = $headerContent . $parentObj->getCatMenuContent($cArr,$lConf);
			return $content;
		}
	}

	function countItems($pid=false) {
		if($pid) $catPidWhere = ' AND tt_news.pid IN ('.$pid.')';
		$query =
			'SELECT tt_news_cat_mm.uid_foreign '.
			'FROM tt_news_cat_mm,tt_news '.
			'WHERE tt_news_cat_mm.uid_local=tt_news.uid AND tt_news.deleted=0 AND tt_news.hidden=0 AND (tt_news.sys_language_uid=-1 OR tt_news.sys_language_uid='.$GLOBALS['TSFE']->sys_language_content.')'.$catPidWhere;
		$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
			$catCountArray[$row[0]]++;
		}
		return($catCountArray);
	}
	
	/**
	 * This is tt_news getCatMenuContent with the cat_count added to the result.
	 * (no hook to add this, so just copied. This may need to be updated if tt_news is)
	 *
	 * @param	array		$array_in: the nested categories
	 * @param	array		$lConf: TS configuration
	 * @param	integer		$l: level counter
	 * @return	string		HTML for the category menu
	 */
	function getCatMenuContent($array_in,$lConf, $l=0) {
		$titlefield = 'title';
		if (is_array($array_in))	{
			$result = '';
			while (list($key,$val)=each($array_in))	{
				if (((string)$key == (string)$titlefield) || is_array($array_in[$key]) || (is_int($key))) {
					if ($l) {
						$catmenuLevel_stdWrap = explode('|||',$this->local_cObj->stdWrap('|||',$lConf['catmenuLevel'.$l.'_stdWrap.']));
						$result.= $catmenuLevel_stdWrap[0];
					}
					if (is_array($array_in[$key]))	{
						$result.=$this->getCatMenuContent($array_in[$key],$lConf,$l+1);
					} elseif (((string) $key == (string) $titlefield) || is_int($key)) {
						if ($GLOBALS['TSFE']->sys_language_content && $array_in['uid']) {
							// get translations of category titles
							$catTitleArr = t3lib_div::trimExplode('|', $array_in['title_lang_ol']);
							$syslang = $GLOBALS['TSFE']->sys_language_content-1;
							$val = $catTitleArr[$syslang]?$catTitleArr[$syslang]:$val;
						}
						if (!$title) $title = $val;
						$catSelLinkParams = ($this->conf['catSelectorTargetPid']?($this->config['itemLinkTarget']?$this->conf['catSelectorTargetPid'].' '.$this->config['itemLinkTarget']:$this->conf['catSelectorTargetPid']):$GLOBALS['TSFE']->id);
						$pTmp = $GLOBALS['TSFE']->ATagParams;
						if ($this->conf['displayCatMenu.']['insertDescrAsTitle']) {
							$GLOBALS['TSFE']->ATagParams = ($pTmp?$pTmp.' ':'').'title="'.$array_in['description'].'"';
						}
						if ($array_in['uid']) {
							if ($this->piVars['cat']==$array_in['uid']) {
								$result.= $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($val, array('cat' => $array_in['uid']), $this->allowCaching, 1, $catSelLinkParams),$lConf['catmenuItem_ACT_stdWrap.']);
							} else {
								$result.= $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($val, array('cat' => $array_in['uid']), $this->allowCaching, 1, $catSelLinkParams),$lConf['catmenuItem_NO_stdWrap.']);
							}
						} else {
//							$result.= $this->pi_linkTP_keepPIvars($val, array(), $this->allowCaching, 1, $catSelLinkParams);
							if ($array_in['up_arrow']) {
								$catParam = $array_in['go_uid'] ? array('cat' => $array_in['go_uid']) : array();
								$result .= $this->local_cObj->stdWrap($this->pi_linkTP_keepPIvars($val, $catParam, $this->allowCaching, 1, $catSelLinkParams),$lConf['catmenuItem_UP_stdWrap.']);
							}
							else
								$result .= $val;
						}
						// show the # of items in a category, if set
						if (isset($array_in['cat_count']))
							$result .= ' ('.$array_in['cat_count'].') ';

						$GLOBALS['TSFE']->ATagParams = $pTmp;
					}
					if ($l) { $result.= $catmenuLevel_stdWrap[1];}
				}
			}
		}
		return $result;
	}

	/**
	 * Display ALL categories in a dropdown menu
	 * This handles any level of categories
	 *
	 * @param	object		$parentObj: parent object
	 * @return	string		the content to display the category dropdown menu
	 */
	function displayCatDropdown($parentObj) {
		// First, get all the categories available
		$orderBy = $parentObj->config['catOrderBy'] ? 'parent_category,'.$parentObj->config['catOrderBy'] : 'parent_category';

		$catWhere = 'pid IN ('.$parentObj->pid_list.')';
		if ($parentObj->catExclusive && $parentObj->config['categoryMode'] == 1)
			$catWhere .= ' AND tt_news_cat.uid IN (' . $parentObj->catExclusive . ') ';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_news_cat', $catWhere. $parentObj->enableCatFields,'',$orderBy);
		$cArr = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$cArr[] = $row;
			$treeMenu[$row['parent_category']][] = $row;
		}

		// add the javascript to handle changing categories
		$catSelLinkParams = ($parentObj->conf['catSelectorTargetPid'] ? ($parentObj->config['itemLinkTarget'] ? $parentObj->conf['catSelectorTargetPid'].' '.$parentObj->config['itemLinkTarget']:$parentObj->conf['catSelectorTargetPid']) : $GLOBALS['TSFE']->id);
		$backURL =  t3lib_div::locationHeaderURL($parentObj->pi_getPageLink($parentObj->config['backPid'] ? $parentObj->config['backPid'] : $parentObj->pi_getPageLink($catSelLinkParams)));

		// Then, build the select menu
		$content .= '<div class="news-catdropdown">';
		if ($headerStr = $parentObj->pi_getLL('catmenudropdown_header','Browse by Category'))
			$content .= '<h3>'.$headerStr.'</h3>';
		$content .= '<select name="cat_dropdown" onchange="if (this.value) location.href=this.value;">';
		$content .= '<option value="">'.$parentObj->pi_getLL('catmenuHeader','Select a category:').'</option>';
		if (!$parentObj->conf['noTop_catdropdown'])
			$content .= '<option value="'.$backURL.'">'.$parentObj->pi_getLL('catmenuTop','Show All').'</option>';
		$content .= $this->displayTreeMenu($treeMenu[0],$treeMenu,$parentObj);
		$content .= '</select>';
		$content .= '</div>';

		return $treeContent.$content;
	}

	function displayTreeMenu($curTreeMenu, $treeMenu, $parentObj, $level = 0) {
		$treeContent = '';
		if(is_array($curTreeMenu)){
			foreach ($curTreeMenu as $key => $val) {
				if (is_array($key)) {
					$treeContent .= $this->displayTreeMenu($key, $treeMenu, $parentObj, $level + 1);
				}
				else {
					$indentContent = '';
					$indentContent=str_repeat('&nbsp;&nbsp;&nbsp;',$level);
					$catSelLinkParams = ($parentObj->conf['catSelectorTargetPid'] ? ($parentObj->config['itemLinkTarget'] ? $parentObj->conf['catSelectorTargetPid'].' '.$parentObj->config['itemLinkTarget']:$parentObj->conf['catSelectorTargetPid']) : $GLOBALS['TSFE']->id);
					$gotoURL = $parentObj->getAbsoluteURL($catSelLinkParams,array('tx_wecknowledgebase_pi1[cat]' => $val['uid']));

					$catTitle = '';
					if ($GLOBALS['TSFE']->sys_language_content) {
						// find translations of category titles
						$catTitleArr = t3lib_div::trimExplode('|', $val['title_lang_ol']);
						$catTitle = $catTitleArr[($GLOBALS['TSFE']->sys_language_content-1)];
						$val['title'] = $catTitle?$catTitle:$val['title'];
					}
				
					$isSel = ($parentObj->piVars['cat'] == $val['uid']) ? 'selected' : '';
					$treeContent .= '<option value="'.$gotoURL.'" '.$isSel.'>'.$indentContent.$val['title'] . '</option>';
					if (isset($treeMenu[$val['uid']]))
						$treeContent .= $this->displayTreeMenu($treeMenu[$val['uid']], $treeMenu, $parentObj, $level+1);
				}
			}
		}
		return $treeContent;
	}
	/**
	 * This will show a list of all tutorials for this knowledgebase entry
	 *
	 * @param	object		&$parentObj: parent object (pass-by-reference because may change the tt_news_uid of parentObj)
	 * @return	string		the list of tutorials
	 */
	function displayTutorialList(&$parentObj) {
		// this will check the current 'tutorial_id' and use that to display. This can come either as a TS constant or as a GETvar
		$tutorialID = $this->piVars['tutorial_id'] ? $this->piVars['tutorial_id'] : $parentObj->conf['tutorial_id'];

		if (!$this->tutorialsList) {
			$this->tutoralsList = array();
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title','tt_news','pid='.$GLOBALS["TSFE"]->id,'','sorting');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->tutorialsList[] = $row;
			}
		}

		// if none is given...then will check for any current records on the page and load...
		if (!$tutorialID) {
			$tutorialID = count($this->tutorialsList) ? $this->tutorialsList[0]['uid'] : 0;
		}

		if ($tutorialID) {
			// grab the related news from that record
			$select_fields = 'DISTINCT uid, pid, title, short, datetime, archivedate, type, page, ext_url, sys_language_uid, l18n_parent, M.tablenames';
			$where = 'tt_news.uid=M.uid_foreign AND M.uid_local=' . $tutorialID . ' AND M.tablenames!='.$GLOBALS['TYPO3_DB']->fullQuoteStr('pages', 'tt_news_related_mm');
			$orderBy = 'M.sorting asc'; // get order they are in tt_news_related_mm
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$select_fields,
				'tt_news,tt_news_related_mm AS M',
				$where,
				$groupBy,
				$orderBy);

			// and display in a special list...
			$i = 0;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$tutorial_list[$i]['title'] = $row['title'];
				$tutorial_list[$i]['uid'] = $row['uid'];
				$i++;
			}
			$firstInListUID = $tutorial_list[0]['uid'];

			// find the tutorialLists
			$tutorialName = "";
			for ($i = 0; $i < count($this->tutorialsList); $i++) {
				if ($this->tutorialsList[$i]['uid'] == $tutorialID) {
					$tutorialName = $this->tutorialsList[$i]['title'];
					break;
				}
			}

			// put in list, number(1....2...etc) if wanted
			$tutorialListStr = "<h3>"."Tutorial:".$tutorialName."</h3>";
			for ($i = 0; $i < count($tutorial_list); $i++) {
				$tutorialListStr .= '<div class="news_tutorial_list_el">';
				$tutorialItemStr = "";
				// number if not already numbered
				if (!ctype_digit(substr($tutorial_list[$i]['title'],0,1)))
					$tutorialItemStr = ($i + 1) . '. ';
				$tutorialItemStr .= $tutorial_list[$i]['title'];
				$params = $this->piVars; // add existing piVars as params
				$params['tt_news'] = $tutorial_list[$i]['uid'];
				$tutorialListStr .= $parentObj->pi_linkTP_keepPIvars($tutorialItemStr,$params);
				$tutorialListStr .= '</div>';
			}

			// set tt_news id for single to first in list if none selected...
			if (!$parentObj->tt_news_uid) {
				$parentObj->tt_news_uid = $firstInListUID;
				$this->tt_news_uid = $firstInListUID;
			}

			return $tutorialListStr;
		}
		else
			return "";
	}

	/**
	 * Show all the tutorials on a page in a dropdown menu
	 *
	 * @param	object		&$parentObj: parent object (pass-by-reference because may change the tt_news_uid of parentObj)
	 * @return	string		the dropdown menu of tutorials
	 */
	function displayTutorialsMenu(&$parentObj) {
		// grab all tutorial(news) records on page...if set page, then use that otherwise use current page
		$tutListID = $this->piVars['tutorial_list_id'] ? $this->piVars['tutorial_list_id'] : $parentObj->conf['tutorial_list_id'];

		$tutorialMenu = "";

		$gotoURL = $parentObj->pi_getPageLink($GLOBALS['TSFE']->id);
		$gotoURL = t3lib_div::locationHeaderURL($gotoURL) . ((!strpos($gotoURL,'?')) ? '?' : '&') . 'tx_wecknowledgebase_pi1[tutorial_id]=';

		// Then, build the select menu
		$tutorialMenu .= '<div class="news-tutorial-menu">';
		$curCat = $parentObj->piVars['cat'];
		if ($headerStr = $parentObj->pi_getLL('tutorialmenu_header','Select Tutorial:'))
			$tutorialMenu .= '<h4 style="display:inline;padding-right:5px;">'.$headerStr.'</h4>';
		$tutorialMenu .= '<select name="cat_dropdown" onchange="if (this.value) location.href=this.value;">';
		$tutorialMenu .= '<option value="">'.$parentObj->pi_getLL('tutorialmenu_topitem','Select...').'</option>';

		if (!$this->tutorialsList) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title','tt_news','pid='.$GLOBALS['TSFE']->id,'','sorting');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->tutorialsList[] = $row;
			}
		}
		for ($i = 0; $i < count($this->tutorialsList); $i++) {
			$el = $this->tutorialsList[$i];
			$isSelected = ($el['uid'] == $this->piVars['tutorial_id']) ? "selected" : "";
			$tutorialMenu .= '<option value="'.$gotoURL.$el['uid'].'" '.$isSelected.'>'.$el['title'].'</option>';
		}
		$tutorialMenu .= '</select>';
		$tutorialMenu .= '</div>';

//		if (!$parentObj->tt_news_uid) {
//			$parentObj->tt_news_uid = $firstInListUID;
//			$this->tt_news_uid = $firstInListUID;
//		}

		return $tutorialMenu;
	}

	/**
	*  FILTER THE POST: Check for filter words and if found, run the filter.
	*
	* @param  string $msgText  text of message to filter
	* @return string text of message if filtered or '0' if no need to filter
	*/
	function filterPost($msgText) {
		$filterWordList = trim($this->config['filter_wordlist']);

		// if * in bad word list, then use the default. This supports adding other words to the * default
		if ($filterWordList == '*' || (!(($all = strpos($filterWordList, '*')) === false))) {
			$newBadwordList = strrev($this->conf['spamWords']);
			if ($this->conf['addSpamWords']) {
				if (strlen($newBadwordList))
					$newBadwordList .= ',';
				$newBadwordList .= $this->conf['addSpamWords'];
			}
			if (strlen($filterWordList) > 1) {
				$start = $all;
				$end = $all+1;
				if (!(strpos($filterWordList, '*,') === false)) // if it is *, then remove both
					$end++;
				if (!(strpos($filterWordList, ',*') === false)) // if it is ,* then remove both
					$start--;
				$filterWordList = substr($filterWordList, 0, $start) . substr($filterWordList, $end);
				$filterWordList .= ','.$newBadwordList;
			}
			else
				$filterWordList = $newBadwordList;
		}
		else if (strlen($filterWordList) <= 1)
			return '0';  // if empty, then return

		$filterWordArray = t3lib_div::trimExplode(',', $filterWordList);
		$filterCount = 0;
		foreach ($filterWordArray as $checkWord) {
			if (strlen($checkWord) && eregi($checkWord, $msgText)) {
				$filterWord = trim(strtolower($checkWord));
				$newWord = substr($filterWord, 0, 1).str_repeat('*', strlen($filterWord)-1);
				$msgText = eregi_replace($filterWord, $newWord, $msgText);
				$filterCount++;
			}
		}
		if (!$filterCount)
			return '0';

		return $msgText;
	}

	/**
	 * Build the full URL (ie. http://www.host.com/... to the given ID with all needed params
	 *
	 * @param	integer		$id: Page ID
	 * @param	string		$extraParameters: extra parameters to include in URL
	 * @return	string		$url: the generated URL
	 */
	function getAbsoluteURL($id, $extraParameters = '')
	{
		$dn = dirname($_SERVER['PHP_SELF']);
		if (($dn != '/') && ($dn != '\\' ))
			$url = 'http://'.$_SERVER['HTTP_HOST'].$dn.'/';
		else
			$url = 'http://'.$_SERVER['HTTP_HOST'].'/';
		$url .= $this->pi_getPageLink($id,'',$extraParameters);
		return $url;
	}

	/**
	 * getConfigVal: Return the value from either plugin flexform, typoscript, or default value, in that order
	 *
	 * @param	object		$Obj: Parent object calling this function
	 * @param	string		$ffField: Field name of the flexform value
	 * @param	string		$ffSheet: Sheet name where flexform value is located
	 * @param	string		$TSfieldname: Property name of typoscript value
	 * @param	array		$lConf: TypoScript configuration array from local scope
	 * @param	mixed		$default: The default value to assign if no other values are assigned from TypoScript or Plugin Flexform
	 * @return	mixed		Configuration value found in any config, or default
	 */
	function getConfigVal( &$Obj, $ffField, $ffSheet, $TSfieldname='', $lConf='', $default = '' ) {
		if (!$lConf && $Obj->conf) $lConf = $Obj->conf;
		if (!$TSfieldname) $TSfieldname = $ffField;

		//	Retrieve values stored in flexform and typoscript
		$ffValue = $Obj->pi_getFFvalue($Obj->cObj->data['pi_flexform'], $ffField, $ffSheet);
		$tsValue = $lConf[$TSfieldname];

		//	Use flexform value if present, otherwise typoscript value
		$retVal = $ffValue ? $ffValue : $tsValue;

			//	Return value if found, otherwise default
		return $retVal ? $retVal : $default;
	}

	/**
	 * Display LIST,LATEST or SEARCH
	 *  Updated for WEC_KNOWLEDGEBASE so can have LATEST view without having to set Sort Order and Date.
	 *  The defaults are datetime DESC. Also, you can set your own for LATEST view so it LATEST becomes EARLIEST
	 *  or whatever criteria you want.
	 *
	 * @param	string		$excludeUids : commaseparated list of tt_news uids to exclude from display
	 * @return	string		html-code for the plugin content
	 */
	function displayList($excludeUids = 0) {
		if ($this->theCode == 'LATEST') {
			$lConf = $this->conf['displayLatest.'];
			$this->config['orderBy'] = $lConf['orderBy'] ? $lConf['orderBy'] : 'datetime';
			$this->config['ascDesc'] = $lConf['ascDesc'] ? $lConf['ascDesc'] : 'DESC';
		}
		$resetPageBrowser = false;
		if (($this->theCode == 'LATEST' || $this->theCode == 'POPULAR') && isset($this->config['noPageBrowser_latestpopular'])) {
			$pageBrowserSave = $this->config['noPageBrowser'];
			$this->config['noPageBrowser'] = true;
			$resetPageBrowser = true;
		}

		$ret = parent::displayList($excludeUids);

		if ($resetPageBrowser) {
			$this->config['noPageBrowser'] = $pageBrowserSave;
		}
		if (!strcmp($this->theCode,'SEARCH')) {
			// no search words, so clear out marker
			if (!$this->piVars['swords'])
				$ret = str_replace('###SEARCH_EMPTY_MSG###','',$ret);
			else {
				// if not a successful search,  then say "nothing found" otherwise clear out markers
				$emptyMsg = (strpos($ret,'###EMPTY_SEARCH###')) ? $this->pi_getLL('noResultsMsg2','Nothing found') : '';
				$ret = str_replace('###SEARCH_EMPTY_MSG###',$emptyMsg,$ret); // this is in template_search
				$ret = str_replace('###EMPTY_SEARCH###','',$ret); // this is in template_search_empty
			}
		}
		return $ret;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_knowledgebase/pi1/class.tx_wecknowledgebase_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_knowledgebase/pi1/class.tx_wecknowledgebase_pi1.php']);
}

?>