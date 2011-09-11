<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Dirk Wenzel t3feblog@sinnzeichen.com
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
  * This class is a front end post widget for t3blog. It allows to write blog posts in front end.
  *
  * @author Dirk Wenzel t3feblog@sinnzeichen.com
  * @package TYPO3
  * @subpackage tx_t3feblog
  */


require_once(PATH_tslib.'class.tslib_pibase.php');


class tx_t3feblog extends tslib_pibase{
	protected $enabledFields = array();
	protected $requiredFields = array();
	var $prefixId		= 'tx_t3blog_pi1'; 
	var $prevPrefixId 	= 'blogPost';
	var $scriptRelPath 	= 'widgets/fepost/class.tx_t3feblog.php';	// Path to this script relative to the extension dir.
	var $extKey        	= 't3feblog';// The extension key.
	protected $message = '';
	var $conf;
	var $uploadError;
	var $uploadSucces; // probably not neccessary
	var $savedFile;	
	
	//logging
	var $msg;
	var $severity;
	var $dataVar;
	
	/**
	 * Initializes the widget.
	 *
	 * @param array $conf
	 * @param array $piVars
	 * @return void
	 */
	function init(array $conf, array $piVars) {
		//$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->enabledFields = t3lib_div::trimExplode(',', strtolower($this->conf['enabledFields']), true);
		$this->requiredFields = t3lib_div::trimExplode(',', strtolower($this->conf['requiredFields']), true);		
		if ($this->conf[uploadAllowed] == 1){
			$this->maxFileSize=$this->conf['maxFileSize']?$this->conf['maxFileSize']:100000;
		}
	}
	
	/**
	 * Checks if the field is required.
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	protected function isFieldRequired($fieldName) {
		return in_array(strtolower($fieldName), $this->requiredFields);
	}
	
	/**
	 * Checks if the field is enabled.
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	protected function isFieldEnabled($fieldName) {
		return in_array(strtolower($fieldName), $this->enabledFields);
	}
	
	/**
	 * Produces the output.
	 *
	 * @param string $unused
	 * @param array $conf
	 * @param array $piVars
	 * @param tslib_cObj $cObj
	 */
	public function main($unused, array $conf, $piVars, tslib_cObj $cObj) {
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prevPrefixId];
		//$this->anArray = $piVars[$this->prevPrefixId];
		
		$this->conf = $conf;
		
		$this->init($conf, $piVars);
				
		$this->insertPostIfNecessary();

		/*//devlog
		$this->msg = 'main';
		$this->severity = 0;
		$this->dataVar = array(
			'message' => $this->message,
			'enabledFields' => $this->enabledFields,
			'requiredFields' => $this->requiredFields,
			'piVars' => $piVars,
			'localPiVars' => $this->localPiVars,
			'uploadError' => $this->uploadError,
			'savedFile' 	=> $this->savedFile,
			'conf'		=> $this->conf,
			);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
		// devlog end*/
		
		$content = '';

		$allow_posts = intval($this->conf['allowPosts']);

		$content = $this->showPostForm($allow_posts);
		return $content;
	}
	/**
	 * Creates a list of fields to for the blog post form.
	 *
	 * @return array
	 */
	protected function getBlogPostFormFields() {
		$postFormFields = $this->enabledFields;
		if ($this->conf['useCaptcha'] == 1) {
			array_push($postFormFields, 'captcha', 'captchaimage');
		}
		if ($this->conf['uploadAllowed'] == 1){
			array_push($postFormFields, 'fileupload', 'maxFileSize');
		}
		if ($this->conf['subscribeForComments'] == 1) {
		//TODO imlement subscription for comments
			//array_push($postFormFields, 'subscribe');
		}
		
		// add all requiredFields if not already in array
		foreach ($this->requiredFields as $fieldName){
			if (!in_array($fieldName, $postFormFields, true)){
				array_push($postFormFields, $fieldName);
			}
		}
		
/*		//devlog
		$this->msg = 'getBlogPostFormFields';
		$this->severity = 0;
		$this->dataVar = array(
			'postFormFields' => $postFormFields
			);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
		// devlog end*/
		
		return $postFormFields;
	}
	
	/**
	 * Check if file size is ok
	 * @param $filesize
	 * @return bool
	 */
	function isFileTooBig($filesize){
		return $filesize > $this->maxFileSize;
	}
	
	/**
	 * Check if mime type of file is allowed
	 * @param $mime
	 * @return bool
	 */
	function isMimeAllowed($mime){
		/*//devlog
		$this->msg = 'isMimeAllowed';
		$this->severity = 0;
		$this->dataVar = array(
			'mime' => $mime
			);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
		// devlog end*/
		
		if(!($this->conf['checkMime']) || !$mime) return TRUE; 		//all mimetypes allowed or mime empty
		$includelist = explode(",",$this->conf['mimeInclude']);
		$excludelist = explode(",",$this->conf['mimeExclude']);		//overrides includelist
		return (   (in_array($mime,$includelist) || in_array('*',$includelist))   &&   (!in_array($mime,$excludelist))  );
	}

	/**
	 * Check if file extension is allowed
	 * @param $filename
	 * @return bool
	 */
	function isExtAllowed($filename){
		if(!($this->conf['checkExt']) || !$filename) return TRUE;			//all extensions allowed or filename empty
		$includelist = explode(",",$this->conf['extInclude']);
		$excludelist = explode(",",$this->conf['extExclude']) 	;	//overrides includelist
		$extension='';
		if($extension=strstr($filename,'.')){
			$extension=substr($extension, 1);    
			return ((in_array($extension,$includelist) || in_array('*',$includelist)) && (!in_array($extension,$excludelist)));
		} else {
			return FALSE;
		}
	}
	
	
	/**
	 * Handle file upload
	 */
	function handleUpload(){
		global $TYPO3_CONF_VARS;
		$path = '';
		if ($this->conf['uploadPath']){
			$path=$this->cObj->stdWrap($this->conf['uploadPath'],$this->conf['uploadPath.']);			
		}
		$uploaddir = is_dir($path)?$path:$TYPO3_CONF_VARS['BE']['fileadminDir'];
		
		//if file should be uploaded to the login users homedir
		if($this->conf['FEuserHomePath'] && $GLOBALS["TSFE"]->loginUser){ 
			if($this->conf['FEuserHomePath.']['field']){
				$feuploaddir=$uploaddir.$GLOBALS["TSFE"]->fe_user->user[$this->conf['FEuserHomePath.']['field']].'/';
			} else {
				$feuploaddir=$uploaddir.$GLOBALS["TSFE"]->fe_user->user["uid"].'/';
			}
			if(!is_dir($feuploaddir)){
				if(!mkdir($feuploaddir)){
					$feuploaddir=$uploaddir;
				}
			}
			$uploaddir = $feuploaddir;
		}
	
		$uploadfile = $uploaddir.$_FILES[$this->prefixId]['name'][$this->prevPrefixId]['fileupload'];
		$filename = $_FILES[$this->prefixId]['name'][$this->prevPrefixId]['fileupload'];
		//get file type
		$filetype = $_FILES[$this->prefixId]['type'][$this->prevPrefixId]['fileupload'];

		
		if(is_file($uploadfile) && $this->conf['noOverwriteFile']){//file already exists?
			$this->uploadError[] = $this->pi_getLL('fileExists');
		}
		
		if($this->isFileTooBig($_FILES[$this->prefixId]['size'][$this->prevPrefixId]['fileupload'])){
			$this->uploadError[] = $this->pi_getLL('fileTooBig');
		}
		
		if(!$this->isMimeAllowed($_FILES[$this->prefixId]['type'][$this->prevPrefixId]['fileupload'])){ //mimetype allowed?
			$this->uploadError[] = $this->pi_getLL('fileMimeNotAllowed');
		}
		
		if(!$this->isExtAllowed($_FILES[$this->prefixId]['name'][$this->prevPrefixId]['fileupload'])){ //extension allowed?
			$this->uploadError[] = $this->pi_getLL('fileExtensionNotAllowed');
		}
		
		if(empty($this->uploadError)){ //no errors so far
			if(move_uploaded_file($_FILES[$this->prefixId]['tmp_name'][$this->prevPrefixId]['fileupload'], $uploadfile)) {//succes!
				$filemode = octdec($this->conf['fileMode']);
				@chmod($uploadfile,$filemode);
				$this->uploadSuccess[] = $this->pi_getLL('uploadSuccessfull');
				$this->savedFile = array(
					'filename' => $filename,
					'file' =>	$uploadfile,
					'type' =>	$filetype
				); 
	 		} else {
				$this->handleFileError($_FILES[$this->prevPrefixId]['error'][$this->prevPrefixId]['fileupload']);
			}
		}
	/*	//devlog
		$imagesize = getimagesize($this->savedFile['file']);
		$this->msg = 'handleUpload';
		$this->severity = 0;
		$this->dataVar = array(
			'uploadError' => $this->uploadError,
			'uploaddir' => $uploaddir,
			'_FILES' => $_FILES,
			'filename' => $filename,
			'uploadfile' => $uploadfile,
			'imagesize' => $imagesize 
			);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
		//devlog end*/	
	}

	/**
	 * Handle file errors. If any error occured an error message will be set.
	 */
	function handleFileError($error){

		switch ($error){
			case 0: 
					break;
			case 1:
			case 2:
					$this->uploadError[] = $this->pi_getLL('fileTooBig');
					break;
			case 3:
					$this->uploadError[] = $this->pi_getLL('filePartialyUploaded');
					break;
			case 4:
					$this->uploadError[] = $this->pi_getLL('fileNotFound');
					break;
			default:
					$this->uploadError[] = $this->pi_getLL('fileUnknownError');
					break;
		}
	}
	
	
	/**
	 * Creates a link to unsubscribe from comment notifications
	 *
	 * @return string
	 */
	protected function getUnsubscribeLink($postUid, $code) {
		$additionalParams = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
			'blogList' => array(
				'showUid' => $postUid,
				'unsubscribe' => 1,
				'code' => $code
			)));
		$typoLinkConf = array(
			'additionalParams' => $additionalParams,
			'parameter' => $GLOBALS['TSFE']->id,
			'no_cache' => true
		);
		$link = t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL($typoLinkConf));
		return $link;
	}
	
	/**
	 * Updates reference index for the table
	 *
	 * @return void
	 */
	protected function updateRefIndex($table, $id) {
		t3lib_div::requireOnce(PATH_t3lib . 'class.t3lib_refindex.php');
		if (!class_exists('t3lib_BEfunc', true)) {
			t3lib_div::requireOnce(PATH_t3lib . 'class.t3lib_refindex.php');
		}
		$refIndex = t3lib_div::makeInstance('t3lib_refindex');
		/* @var $refIndex t3lib_refindex */
		$refIndex->updateRefIndexTable($table, $id);
	}
	
	/**
	 * Sends a received post notification per email to the given admin's email address
	 * @author kay stenschke <kstenschke@snowflake.ch>
	 * edited for t3feblog by 
	 * @author dirk wenzel dirk.wenzel@sinnzeichen.com
	 */
	function adminMailPost()	{
		//$pObjPiVars = t3lib_div::_POST('tx_t3blog_pi1');	// pObj piVars array

/*		$postUid = intval($this->localPiVars['uid']);
		list($titleRow) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title',
			'tx_t3blog_post', 'uid=' . intval($postUid)
		);*/
		
		//TODO: adminsCommentMailTemplate in conf mit Template fuer Blogpost ersetzen
		$messageText = $this->cObj->fileResource($this->conf['adminsPostMailTemplate']);
		$markerArray = array(
			'###TITLE###'		=> $this->localPiVars['posttitle'],
			'###HEADER###'		=> $this->localPiVars['postheader'],
			'###TEXT###'		=> $this->localPiVars['posttext'],
			'###AUTHOR###'		=> $this->localPiVars['postauthor'],
			'###EMAIL###'		=> $this->localPiVars['postauthoremail'],
			'###WEBSITE###'		=> $this->localPiVars['postauthorwebsite'],
			'###IP###'			=> t3lib_div::getIndpEnv('REMOTE_ADDR'),
			'###TSFE###'		=> t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST')/*,
			'###POSTTITLE###'   => is_array($titleRow) ? $titleRow['title'] : '',
			getPermalink ist in blogList deklariert und benštigt eine uid (post)
			'###LINK###'		=> $this->getPermalink($this->uid, $this->getPostDate($this->uid), true)*/
		);
		foreach ($markerArray as $key => $val) {
			if (strlen(trim($val)) < 1) {
				$markerArray[$key] = '-';
			}
		}
		$messageSubject = $this->cObj->substituteMarkerArray($this->pi_getLL('postAdminMailSubject'), $markerArray);
		$messageText = $this->cObj->substituteMarkerArray($messageText, $markerArray);
		
		/*//devlog start
		$this->msg = 'adminMailPost';
		$this->severity = 0;
		$this->dataVar = array(
			'mailSubject' => $messageSubject,
			'mailText' => $messageText,
			'piVars'	=> $this->piVars,
			'localPiVars' => $this->localPiVars
		);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
		//devlog end*/
		
		t3lib_div::plainMailEncoded(
			$this->conf['adminsPostsEmail'],			//email (receiver)
			$messageSubject,	//subject
			$messageText,								//message
			'From: ' . $this->conf['adminsPostEmailFrom']
		);
	}
	
	/**
	* Creates an e-mail to unsubscribe from the post.
	 *
	 * @param int $postUid
	 * @param string $postTitle
	 * @param array $subscriber
	 * @param array $post 'title' and 'text' fields are required
	 * @return void
	 * TODO: Lokalisierung fuer Post anpassen (subscribe.newComment)
	 */
	protected function sendUnsubscribeEmail($postUid, $postTitle, $subscriber, $post) {
		$unsubscribeLink = '<' . $this->getUnsubscribeLink($postUid, $subscriber['code']) . '>' . chr(10);
		$text = '"' . trim($post['title']) . ': ' . str_replace(array('<br>', '<br />'), chr(10), trim($post['text'])) .'"' . chr(10);
		$receiver = str_replace(array('\n', '\r'), '', $subscriber['email']);
		$subject = $this->pi_getLL('subscribe.newComment') . ': ' . $postTitle;
		$from = $this->conf['senderEmail'];
		$headers = 'From: <' . $from . '>' . chr(10) .
			'List-Unsubscribe: ' . $unsubscribeLink;

		$message = $this->pi_getLL('subscribe.salutation') . ' ' . $subscriber['name'] . ',' . chr(10) . chr(10);
		$message .= $this->pi_getLL('subscribe.notification') . chr(10) . chr(10);
		$message .= $text . chr(10);
		$message .= $this->pi_getLL('subscribe.optionalTextBeforePermalink');
		$message .= '<' . t3lib_div::locationHeaderUrl($this->getPermalink($postUid, $this->getPostDate($postUid), true)) . '>' . chr(10) . chr(10);

		// unsubscribe
		$message .= $this->pi_getLL('subscribe.unsubscribe') . chr(10);
		$message .= $unsubscribeLink;

		// add footer (optional)
		$message .= chr(10) . $this->pi_getLL('subscribe.optionalFooter');

		// send
		t3lib_div::plainMailEncoded($receiver, $subject, $message, $headers);
	}
	
	/**
	 * Inserts a new post subscriber to the database.
	 *
	 * @param int $postUid
	 * @param string $author
	 * @param string $email
	 * @return void
	 */
	protected function insertNewSubscriber($postUid, $author, $email) {
		$code = md5($email . $GLOBALS['EXEC_TIME']);

		$data = array(
			'pid'		=> t3blog_div::getBlogPid(),
			'tstamp'	=> $GLOBALS['EXEC_TIME'],
			'crdate'	=> $GLOBALS['EXEC_TIME'],
			'email'		=> $email,
			'name'		=> $author,
			'post_uid'	=> $postUid,
			'lastsent'	=> $GLOBALS['EXEC_TIME'],
			'code'		=> $code,
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_t3blog_com_nl', $data);

		return $code;
	}
	
	/**
	 * Sends a subscription confirmation email to a new subscriber.
	 *
	 * @param int $postUid
	 * @param string $email
	 * @param string $unsubscribeCode
	 */
	protected function sendSubscribtionConfirmationEmail($postUid, $email, $unsubscribeCode) {
		$receiver = str_replace(array('\n', '\r'), '', $email);
		$postTitle = $this->getPostTitle($postUid);
		$subject = $this->pi_getLL('subscribe.confirmation') . ': ' . $postTitle;
		$unsubscribeLink = $this->getUnsubscribeLink($postUid, $unsubscribeCode);
		$headers = 'From: <' . $this->conf['senderEmail'] . '>' . chr(10) .
			'List-Unsubscribe: ' . $unsubscribeLink;

		$message = $this->pi_getLL('subscribe.confirmationHello') . chr(10) .
			$this->pi_getLL('subscribe.confirmationtext') . chr(10);
			'<' . $unsubscribeLink . '>' . chr(10);

		// add footer (optional)
		$message .= chr(10) . $this->pi_getLL('subscribe.optionalFooter');

		t3lib_div::plainMailEncoded($receiver, $subject, $message, $headers);
	}


	/**
	 * Checks if a user with given e-mail is already subscribed to receive
	 * notifications about new comments.
	 *
	 * @param int $postUid
	 * @param string $email
	 * @return boolean
	 */
	protected function isSubscribedToPost($postUid, $email) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS t',
			'tx_t3blog_com_nl',
			'post_uid=' . $postUid .' AND email=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($email, 'tx_t3blog_com_nl') .
			$this->cObj->enableFields('tx_t3blog_com_nl'));
		return ($row['t'] > 0);
	}

	
	/**
	 * Subscribes the user to notifications about the post if he is not
	 * subscribed yet.
	 *
	 * @param int $uid 
	 * @param string $author
	 * @param string $email
	 * @return void
	 */
	protected function subscribeToPostNotifications($uid, $author, $email) {
		if (!$this->isSubscribedToPost($uid, $email)) {
			$code = $this->insertNewSubscriber($uid, $author, $email);
			$this->sendSubscribtionConfirmationEmail($uid, $email, $code);

		}
	}
	
	/**
	 * shows the Post Form
	 *
	 * @param 	int		$allowPosts: status 0,1,2 {0 = none, 1 = all, 2 = only registered users}
	 */
	function showPostForm($allowPosts)	{
		if ($allowPosts == 1 || ($allowPosts == 2 && $GLOBALS['TSFE']->fe_user->user['uid'])) {
			$result = $this->doShowPostForm();
		}
		else {
			$result = $this->postsNotAllowed($allowPosts);
		}
		return $result;
	}
	
	protected function postsNotAllowed($allowPosts) {
		if ($allowPosts == 0) {
			// no posts allowed at all
			$result = t3blog_div::getSingle(
				array(
					'text' => $this->pi_getLL('notAllowedToPost')
				),
				'noPostAllowedWrap', $this->conf);
		}
		else {
			// not logged in message
			$returnLink = $this->pi_linkTP_keepPIvars_url(array(),1,0,$GLOBALS['TSFE']->id);
			$result = t3blog_div::getSingle(
				array(
					'text'=>$this->pi_getLL('notAllowedToPostWithoutLogin'),
					'loginPid'=>$this->conf['loginPid'],
					'loginLinkText'=>$this->pi_getLL('loginLinkText'),
					'redirect_url'=> t3lib_div::locationHeaderUrl($returnLink)
				), 'noPostAllowedWrap', $this->conf);
		}
		return $result;
	}

	
	/**
	 * Generates the post form
	 *
	 * @return string
	 */
	protected function doShowPostForm() {
		$data = array();
		// set post form fields
		$this->setPostFormFields($data);
		
		// set upload form fields
		$this->setFileUploadFields($data);
		// setup captcha
		$this->setCaptchaFields($data);
		
		
		// captcha

		// subscribe for comments (not implemented yet)
		/*if ($this->conf['subscribeForComments'] == 1) {
			$postVars = t3lib_div::_POST('tx_t3blog_pi1');
			if ($postVars['blogList']['subscribe']) {
				$data['subscribe'] = 'checked="checked"';
			}
			else {
				$data['subscribe'] = ' ';
			}
			$data['subscribe_text']	= $this->pi_getLL('subscribe_text');
		}*/

		$data['postFormTitle'] 		= $this->pi_getLL('postFormTitle');
		//$data['closeicon'] 		= '<img src="'.t3lib_extMgm::extRelPath('t3blog').'icons/window_close.png" alt="" />';
		//$data['closelink'] 		= '';
		unset($this->piVars[$this->prevPrefixId]['createPostForm']); 
		$data['insert'] = 1;
		$data['submit_label']	= $this->pi_getLl('submit');
		$data['action'] = htmlspecialchars($this->getPostFormAction());
		
		/*//devlog
		$this->msg = 'doShowPostForm';
		$this->dataVar = array(
			'data' 			=> $data,
			'globalPiVars'	=> $this->globalPiVars,
			'localPiVars'	=> $this->localPiVars
		);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
		//devlog end*/
		
		// display error msg
		if ($this->errorMessage){
			$data['errorMsg'] = $this->errorMessage;
			$data['errorTitle'] = $this->pi_getLL('errorTitle');
			unset($this->localPiVars['errorMsg']);
		}
		
		if ($this->message) {
			$data['postMessage'] = $this->message;
		}
		
		$content = t3blog_div::getSingle($data, 'postForm', $this->conf);

		return $content;
	}

	/**
	 * Sets fields according to the poster's previous data
	 *
	 * @param array data
	 * @return void
	 * TODO: 1.) kšnnen wir marker aus t3blog_div::getSingle verwenden?
	 * 2.) set requiredFields (aus conf)
	 * 3.) set localPiVars
	 */
	protected function setPostFormFields(array &$data) {
		if ($GLOBALS['TSFE']->fe_user->user['uid']) {
			$data['postauthor'] = $GLOBALS['TSFE']->fe_user->user['username'];
			$data['postauthoremail'] = $GLOBALS['TSFE']->fe_user->user['email'];
		}
		foreach ($this->getBlogPostFormFields() as $fieldName) {
			$data['show'.ucfirst($fieldName)] = 1;
			if (isset($this->localPiVars[$fieldName]) && $_SERVER['REQUEST_METHOD'] == 'POST') {
				// Must be uncached
				$data[$fieldName] = $this->localPiVars[$fieldName];
			}
			$data[$fieldName.'_label'] = $this->pi_getLL($fieldName);

			//if (in_array(strtolower($fieldName), $this->enabledFields) && in_array(strtolower($fieldName), $this->requiredFields)) {
			if ($this->isFieldRequired($fieldName)) {
			$data[$fieldName.'_label'] .= ' ' . t3blog_div::getSingle(array(
					'marker' => '*'
				), 'requiredFieldMarkerWrap', $this->conf);
			}
		}
	}

	/**
	 * Adds captcha fields if necessary.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setCaptchaFields(array &$data) {
		if ($this->conf['useCaptcha'] == 1) {
			$data['captcha'] = 'tx_t3blog_pi1[blogList][captcha]';
			$data['captchaimage'] = '<img src="' . t3lib_extMgm::siteRelPath('t3blog') .
				'pi1/widgets/blogList/captcha/captcha.php?' .
				'font=' . htmlspecialchars($this->conf['captchaFont']) .
				'&amp;fontSize=' . htmlspecialchars($this->conf['captchaFontSize']) .
				'&amp;fontColor=' . htmlspecialchars($this->conf['captchaFontColor']) .
				'&amp;fontEreg=' . htmlspecialchars($this->conf['captchaEreg']) .
				'&amp;image=' . htmlspecialchars($this->conf['captchaBackgroundPNGImage']) .
				'&amp;showImage=' . htmlspecialchars($this->conf['captchaShowImage']) .
				'&amp;backgroundColor=' . htmlspecialchars($this->conf['captchaBackgroundColor']) .
				'&amp;lines=' . htmlspecialchars($this->conf['captchaLines']) .
				'" alt="" />';
			$data['captcha_label'] .= ' ' . t3blog_div::getSingle(array(
					'marker' => '*'
				), 'requiredFieldMarkerWrap', $this->conf);
		}
	}
	
	/**
	 * Adds fields for file upload (if allowed)
	 */
	protected function setFileUploadFields(array &$data) {
		if ($this->conf['uploadAllowed'] == 1) {
			$data['fileupload'] = ' ';
			$data['doUpload'] = 1;
			$data[maxFileSize] = $this->conf[maxFileSize];
		}
	}

	/**
	 * Creates a post form action URL.
	 *
	 * @return string
	 */
	protected function getPostFormAction() {
		return t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
	}

	
	/**
	 * inserts a post to the blog entry
	 *
	 * @return bool true on success
	 */
	function insertPost() {
		$postAuthor = htmlspecialchars(strip_tags($this->localPiVars['postauthor']));
		$postTitle = htmlspecialchars(strip_tags($this->localPiVars['posttitle']));
		$authorEmail = htmlspecialchars(strip_tags($this->localPiVars['postauthoremail']));
		$authorWebsite = htmlspecialchars(strip_tags($this->localPiVars['postauthorwebsite']));
		$postText = htmlspecialchars(strip_tags($this->localPiVars['posttext']));
		$postHeader = htmlspecialchars(strip_tags($this->localPiVars['postheader']));

		$this->errorMessage = $this->validatePostSubmission(
			$postAuthor, $postTitle, $postHeader, $authorEmail, $authorWebsite, $postText
		);
		
		//we only care about file upload if it is allowed and there was no previous error (while validating post form fields)
		if($this->localPiVars['doUpload'] && !$this->errorMessage){
			$this->handleUpload();
			$this->errorMessage .=$this->validateFileUpload();
		}
		
		$result = false;
		
		// if there is still no error
		if ($this->errorMessage == '') {
			$pid = t3blog_div::getBlogPid();
			$beuser = $this->getBEUserforFEPost(); 
			
			$spam = $this->isSpam(array($postAuthor, $postTitle, $postHeader, $authorWebsite, $authorEmail, $postText));
			
			if (!$spam){
				$result = true; // ist vielleicht noch ein bi§chen frŸh?

				// moved this to unsetLocalPiVarsAfterAddingPost() - we need the localPiVars for the email to admin about new post
				//$this->unsetLocalPiVarsBeforeAddingPost();

			
				$data = array(
					//'ttcontent' => $contentData,
					'ttcontent' => $this->prepareContentData($pid, $beuser, $postHeader, $postText),
					'blogpost'	=> $this->preparePostData($pid, $beuser, $postAuthor, $postTitle, $authorEmail, $authorWebsite),
					'category'	=> intval($this->conf['postCategory']) //we use a preset category for now, could be made selectable by usr later 
				);
				
				/*//devlog start
				$this->msg = 'insertPost';
				$this->severity = 0;
				$this->dataVar = array(
					'data' => $data
					);
				t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
				//devlog end*/
				
				$this->insertNewPost($data);
	
	/*
				if ($this->conf['mailReceivedPostToAdmin']) {
					$this->sendEmailAboutNewPost($postUid); //TODO add method (wir haben hier noch keine postUid)
				}
	
				if (isset($_POST['tx_t3blog_pi1']['blogList']['subscribe'])) {
					$this->subscribeToPostNotifications($postUid, $postAuthor, $authorEmail);
				}*/
			}
		}

		return $result;
	}

	/**
	 * Unsets some local variables before creating/editing a comment.
	 *
	 *
	 * @return void
	 */
	protected function unsetLocalPiVarsAfterAddingPost() {
		unset($this->piVars[$this->prevPrefixId]['postauthor']);
		unset($this->piVars[$this->prevPrefixId]['posttext']);
		unset($this->piVars[$this->prevPrefixId]['posttitle']);
		unset($this->piVars[$this->prevPrefixId]['postheader']);
		unset($this->piVars[$this->prevPrefixId]['postauthoremail']);
		unset($this->piVars[$this->prevPrefixId]['postauthorwebsite']);
		unset($this->piVars[$this->prevPrefixId]['postsubmit']);
		unset($this->piVars[$this->prevPrefixId]['insert']);
		
		unset($this->localPiVars['postauthor']);
		unset($this->localPiVars['posttext']);
		unset($this->localPiVars['posttitle']);
		unset($this->localPiVars['postheader']);
		unset($this->localPiVars['postauthoremail']);
		unset($this->localPiVars['postauthorwebsite']);
		unset($this->localPiVars['postsubmit']);
		unset($this->localPiVars['insert']);
	}

	
	/**
	 * Validates post submission.
	 *
	 * @param string $postAuthor
	 * @param string $postTitle
	 * @param string $postHeader
	 * @param string $authorEmail
	 * @param string $authorWebsite
	 * @param string $postText
	 * @return string Error message (if any)
	 * TODO: error messages kommen von t3blog_div::getSingle -  erweitern um post-eintrŠge
	 */
	protected function validatePostSubmission($postAuthor, $postTitle, $postHeader, $authorEmail, $authorWebsite, $postText) {
		$errorMessage = '';

		$testData = array(
			'postauthor' => array(
				'value' => $postAuthor,
			),
			'postauthoremail' => array(
				'value' => $authorEmail,
				'validator' => array('t3lib_div', 'validEmail')
			),
			'postauthorwebsite' => array(
				'value' => $authorWebsite,
				'validator' => array('t3lib_div', 'isValidUrl')
			),
			'posttitle' => array(
				'value' => $postTitle,
			),
			'postheader' => array(
				'value' => $postHeader,
			),
			'posttext' => array(
				'value' => $postText
			)
		);
		
		//devlog
/*		$this->msg = 'validatePostSubmission';
		$this->severity = 0;
		$this->dataVar = array(
			'testData'	=> $testData,
			'piVars' => $this->piVars,
			'conf'	=> $this->conf	
		);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);
*/		//

		foreach ($testData as $field => $data) {
			$isValid = true;

			$fieldRequired = $this->isFieldRequired($field);
			if ($fieldRequired) {
				$isValid = (trim($data['value']) != '');
			}
			if ($isValid && isset($data['validator']) && ($fieldRequired || $data['value'] != '')) {
				$isValid = call_user_func($data['validator'], $data['value']);
			}
			if (!$isValid) {
				$errorMessage .= t3blog_div::getSingle(array(
						'value' => $this->pi_getLL('error_' . $field)
					), 'errorWrap', $this->conf);
			}
		}
		//devlog
/*		$this->dataVar = array(
			'errorMessage'	=> $errorMessage
		);
		t3lib_div::devLog($this->msg, $this->extKey, $this->severity, $this->dataVar);*/
		//
		
		// captcha
		if ($this->conf['useCaptcha'] == 1) {
			session_start();
			$captchaStr = $_SESSION['tx_captcha_string'];
			$_SESSION['tx_captcha_string'] = '';

			if (!strlen($captchaStr) || $this->localPiVars['captcha'] != $captchaStr) {
				$errorMessage .= t3blog_div::getSingle(array(
					'value' => $this->pi_getLL('error_captcha')
				), 'errorWrap', $this->conf);
			}
		}

		return $errorMessage;
	}
	
	/**
	 * Check if any errors occured while trying to upload file
	 * @return string
	 */
	function validateFileUpload(){
		$errorMessage = '';
		foreach ($this->uploadError as $statusMsg){
			$errorMessage .= t3blog_div::getSingle(array(
				'value' => $statusMsg), 'errorWrap', $this->conf);
		}
		return $errorMessage;
	}
	/**
	 * Checks if passed fields contain spam.
	 *
	 * @param array $textFields
	 * @return boolean
	 */
	protected function isSpam(array $textFields) {
		$sfpantispam = t3lib_div::makeInstance('tx_sfpantispam_tslibfepreproc');
		/* @var tx_sfpantispam_tslibfepreproc $sfantispam */
		return !$sfpantispam->sendFormmail_preProcessVariables($textFields, $this);
	}
	
	/**
	 * Prepares post data for insertion/update.
	 *
	 * @param int $pid
	 * @param int $beuser
	 * @param string $postAuthor
	 * @param string $postTitle
	 * @param string $authorEmail
	 * @param string $authorWebsite
	 * @return array
	 */
	protected function preparePostData($pid, $beuser, $postAuthor, $postTitle, $authorEmail, $authorWebsite) {
		
		$data = array(
			'pid'		=> $pid,
			'tstamp'	=> $GLOBALS['EXEC_TIME'],
			'title'		=> $postTitle,
			'author'	=> $this->getBEUserforFEPost(),
			'tx_t3feblog_authorname' => $postAuthor,
			'tx_t3feblog_authoremail' => $authorEmail,
			'tx_t3feblog_authorurl'	=> $authorWebsite,
			'hidden'	=> intval($this->conf['postHidden']), //preset value, if true post will be hidden initially
			'cat'		=> 1
		);
		return $data;
	}
	
	/**
	 * Prepares content data for insertion / update
	 * @param int $pid
	 * @param int $beuser
	 * @param string $postHeader
	 * @param string $postText
	 * @return array
	 */
	protected function prepareContentData($pid, $beuser, $postHeader, $postText){
		$contenttype = 'text'; //set default content type to text
		$imgDim ='';
		$image ='';
		if ($this->savedFile && $this->isSupportedImageType($this->savedFile['type'])){
			$imagesize = getimagesize($this->savedFile['file']);
			$imgDim = $this->getValidImageDimensions($imagesize);
			$contenttype = 'textpic';
			$image = $this->savedFile['filename'];
		}
		$ttcontentdata = array(
						'pid'	=> $pid,
					'cruser_id' => $beuser,
						'CType' => $contenttype, //only content types 'text' and 'textpic' are allowed for now
						'tstamp'=> $GLOBALS['EXEC_TIME'],
						'image'	=> $image,
				'imageorient'	=> $this->conf['imageOrientation'],
						'header'=> htmlspecialchars(strip_tags($postHeader)),
					  'bodytext'=> htmlspecialchars(strip_tags($postText)),
				'sectionIndex' => 1,  //TODO: this should be configurable
				'header_layout'	=> 0, //TODO: this should be configurable
						'hidden'=> 0, //TODO: read from configuration (setup.txt)
			  'irre_parenttable'=> 'tx_t3blog_post'
		);
		
		if (is_array($imgDim)){
		//todo set only one value (the smaller one)
			$ttcontentdata['imagewidth'] = $imgDim['width'];
			$ttcontentdata['imageheight'] = $imgDim['height'];
		}
		return $ttcontentdata;
	}
	/**
	 * Returns valid image dimensions for DB. Considers configuration values of plugin and global config.
	 * @return array
	 */
	function getValidImageDimensions($imagesize){
		$dim = array(
			'width' => $imagesize[0],
			'height' => $imagesize[1]
		);

		//find biggest value
		if ($dim['width'] > $this->conf['imageMaxWidth']){
			$dim['width'] = $this->conf['imageMaxWidth'];
		}
		if ($dim['height'] > $this->conf['imageMaxHeight']){
			$dim['height'] = $this->conf['imageMaxHeight'];
		}
		return $dim;
	}
	/**
	 * Check wether the type of an image is supported. Expects a mime typ string. 
	 * @param string $type
	 */
	protected function isSupportedImageType($type){
		if ($type = 'image/png' || 
			$type = 'image/jpg' || 
			$type = 'image/pjpg' ||
			$type = 'image/bmp' ||
			$type = 'image/x-windows-bmp' ||
			$type = 'image/tiff' ||
			$type = 'image/x-tiff'){
				return true;
			}else {
				return false;
			}
	}
	/**
	 * Gets the backend user uid for frontend post. This user must be set in TS config. It must not be admin. 
	 * It must belong to the group (and no other) wich is configured for frontend posting. 
	 *
	 * TODO: validate user and its membership in a valid user group and not admin!
	 * @return int
	 */
	protected  function getBEUserForFEPost() {
		$beuser = intval($this->conf['backendUserForFrontendPosts']);
		return $beuser;
	}
	
	/**
	 * Gets the uid of the backend user group for frontend post. This group should have very restricted rights
	 * TODO: validate rights? 
	 */
	protected  function getBEGroupForFEPost() {
		$begroup = intval($this->conf['backendGroupForFrontendPosts']);
		return $begroup;
	}
	
	/**
	 * Inserts a post to the database.
	 *
	 * @param array $data
	 * @return void
	 * 
	 */
	protected function insertNewPost(array $data) {
		$blogpost = $data['blogpost'];
		$blogpost['date'] = $blogpost['crdate'] = $GLOBALS['EXEC_TIME'];
		$category = $data['category'];
		$ttcontent = $data['ttcontent'];
		//insert post
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_t3blog_post', $blogpost);
		$postId = $GLOBALS['TYPO3_DB']->sql_insert_id();
		
		//set category
		$post_cat_mm = array(
			'uid_local' => $postId, //tx_t3blog_post.uid
			'uid_foreign' => $category, // category uid
			'sorting'	=> 1
		);
		// insert category
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_t3blog_post_cat_mm', $post_cat_mm);
		
		// set irre_parentid
		$ttcontent['irre_parentid'] = $postId;
		// insert content
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_content', $ttcontent);
		$contentId = $GLOBALS['TYPO3_DB']->sql_insert_id();
		
		$post_content_mm = array(
			'uid_local' 	=> $postId,
			'uid_foreign' 	=> $contentId,
			'sorting'		=> 1
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_t3blog_post_content_mm', $post_content_mm);
		
		$postcontent = array('content' => 1);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_post', 'uid=' . $postId, $postcontent);
		
		$this->updateRefIndex('tx_t3blog_post', $postId);

		$GLOBALS['TSFE']->clearPageCacheContent_pidList(t3blog_div::getBlogPid());

		// Hook after comment insertion
		/*if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['afterpostinsertion'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['afterpostinsertion'] as $userFunc) {
			  $params = array(
					'data' => &$data,
					'table' => 'tx_t3blog_post',
					'postUid' => $postId
				);
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}*/
	}

	/**
	 * Inserts the post to the database if necessary. 
	 * According to settings in conf some other actions will be carried out:
	 * 1. Send an email to the admin who approves new posts.
	 * 2. Show a message that the post has to be approved.
	 *
	 * @return void
	 */
	protected function insertPostIfNecessary() {
		if ($this->localPiVars['insert']) {
			if ($this->insertPost()) {
				// Todo: and not spam!
				if ($this->conf['mailReceivedPostToAdmin']) {
					$this->adminMailPost();
				}
				// if it first has to be approved, show a message for the writer
				if ($this->conf['toBeApproved']) {
					$this->message = $this->pi_getLL('toBeApproved');
				}
				$this->unsetLocalPiVarsAfterAddingPost();
			}
		}
	}

	/**
	 * Sets some localPiVars necessary for the TypoScript renderer
	 *
	 * @param array $postRow
	 * @return void
	 */
	protected function setSomePiVarValues(array $postRow) {
		//nothing to set for now
	}

	/**
	 * Unsubscribes from comments and returns HTML code to display a corresponding
	 * message if necessary.
	 *
	 * @return string
	 * TODO: nicht sicher ob wir diese methode brauchen!
	 */
	protected function unsubscribeFromComments() {
		$result = '';
		if ($this->localPiVars['unsubscribe']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_com_nl',
				'code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->localPiVars['code'], 'tx_t3blog_com_nl'),
				array('deleted' => 1));
			$result = '<script>alert("'.$this->pi_getLL('subscribe.unsubscribe.succesfully').'");</script>';
		}
		return $result;
	}
	
	//
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3feblog/class.tx_t3feblog.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3feblog/class.tx_t3feblog.php']);
}

?>