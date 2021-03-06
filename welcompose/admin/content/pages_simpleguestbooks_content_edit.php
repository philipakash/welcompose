<?php

/**
 * Project: Welcompose
 * File: pages_simpleguestbooks_content_edit.php
 *
 * Copyright (c) 2008-2012 creatics, Olaf Gleba <og@welcompose.de>
 *
 * Project owner:
 * creatics, Olaf Gleba
 * 50939 Köln, Germany
 * http://www.creatics.de
 *
 * This file is licensed under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE v3
 * http://www.opensource.org/licenses/agpl-v3.html
 * 
 * @author Olaf Gleba
 * @package Welcompose
 * @link http://welcompose.de
 * @license http://www.opensource.org/licenses/agpl-v3.html GNU AFFERO GENERAL PUBLIC LICENSE v3
 */

// define area constant
define('WCOM_CURRENT_AREA', 'ADMIN');

// get loader
$path_parts = array(
	dirname(__FILE__),
	'..',
	'..',
	'core',
	'loader.php'
);
$loader_path = implode(DIRECTORY_SEPARATOR, $path_parts);
require($loader_path);

// start base
/* @var $BASE base */
$BASE = load('base:base');

// deregister globals
$deregister_globals_path = dirname(__FILE__).'/../../core/includes/deregister_globals.inc.php';
require(Base_Compat::fixDirectorySeparator($deregister_globals_path));

// admin_navigation
$admin_navigation_path = dirname(__FILE__).'/../../core/includes/admin_navigation.inc.php';
require(Base_Compat::fixDirectorySeparator($admin_navigation_path));

try {
	// start output buffering
	@ob_start();
	
	// load smarty
	$smarty_admin_conf = dirname(__FILE__).'/../../core/conf/smarty_admin.inc.php';
	$BASE->utility->loadSmarty(Base_Compat::fixDirectorySeparator($smarty_admin_conf), true);
	
	// load gettext
	$gettext_path = dirname(__FILE__).'/../../core/includes/gettext.inc.php';
	include(Base_Compat::fixDirectorySeparator($gettext_path));
	gettextInitSoftware($BASE->_conf['locales']['all']);
	
	// start session
	/* @var $SESSION session */
	$SESSION = load('base:session');
	
	// load user class
	/* @var $USER User_User */
	$USER = load('user:user');
	
	// load login class
	/* @var $LOGIN User_Login */
	$LOGIN = load('User:Login');
	
	// load project class
	/* @var $PROJECT Application_Project */
	$PROJECT = load('application:project');
	
	// load page class
	/* @var $PAGE Content_Page */
	$PAGE = load('content:page');
	
	// load simpleguestbook class
	/* @var $SIMPLEGUESTBOOK Content_SimpleGuestbook */
	$SIMPLEGUESTBOOK = load('Content:SimpleGuestbook');
	
	// load textconverter class
	/* @var $TEXTCONVERTER Application_Textconverter */
	$TEXTCONVERTER = load('application:textconverter');
	
	// load textmacro class
	/* @var $TEXTMACRO Application_Textmacro */
	$TEXTMACRO = load('application:textmacro');
	
	// load helper class
	/* @var $HELPER Utility_Helper */
	$HELPER = load('utility:helper');
	
	// init user and project
	if (!$LOGIN->loggedIntoAdmin()) {
		header("Location: ../login.php");
		exit;
	}
	$USER->initUserAdmin();
	$PROJECT->initProjectAdmin(WCOM_CURRENT_USER);
	
	// check access
	if (!wcom_check_access('Content', 'SimpleGuestbook', 'Manage')) {
		throw new Exception("Access denied");
	}
	
	// assign current user values
	$_wcom_current_user = $USER->selectUser(WCOM_CURRENT_USER);
	$BASE->utility->smarty->assign('_wcom_current_user', $_wcom_current_user);
	
	// assign current project values
	$_wcom_current_project = $PROJECT->selectProject(WCOM_CURRENT_PROJECT);
	$BASE->utility->smarty->assign('_wcom_current_project', $_wcom_current_project);
	
	// get page
	$page = $PAGE->selectPage(Base_Cnc::filterRequest($_REQUEST['id'], WCOM_REGEX_NUMERIC));
	
	// get simple guestbook
	$simple_guestbook = $SIMPLEGUESTBOOK->selectSimpleGuestbook(Base_Cnc::filterRequest($_REQUEST['id'], WCOM_REGEX_NUMERIC));
	
	// get default text converter if set
	$default_text_converter = $TEXTCONVERTER->selectDefaultTextConverter();
	
	// prepare captcha types array
	$captcha_types = array(
		'no' => gettext('Disable captcha'),
		'image' => gettext('Use image captcha'),
		'numeral' => gettext('Use numeral captcha')
	);
	
	// start new HTML_QuickForm
	$FORM = $BASE->utility->loadQuickForm('simple_guestbook');

	// apply filters to all fields
	$FORM->addRecursiveFilter('trim');

	// hidden guestbook id
	$id = $FORM->addElement('hidden', 'id', array('id' => 'id'));
	
	// hidden for frontend view control
	$preview = $FORM->addElement('hidden', 'preview', array('id' => 'preview'));	
		
	// textfield for title
	$title = $FORM->addElement('text', 'title', 
		array('id' => 'simple_guestbook_title', 'maxlength' => 255, 'class' => 'w300'),
		array('label' => gettext('Title'))
		);
	$title->addRule('required', gettext('Please enter a title'));
	
	// textfield for URL title
	$title_url = $FORM->addElement('text', 'title_url', 
		array('id' => 'simple_guestbook_title_url', 'maxlength' => 255, 'class' => 'w300 validate'),
		array('label' => gettext('URL title'))
		);
	$title_url->addRule('required', gettext('Enter an URL title'));
	$title_url->addRule('regex', gettext('The URL title may only contain chars, numbers and hyphens'), WCOM_REGEX_URL_NAME);
		
	// textarea for content
	$content = $FORM->addElement('textarea', 'content', 
		array('id' => 'simple_guestbook_content', 'cols' => 3, 'rows' => '2', 'class' => 'w540h550'),
		array('label' => gettext('Content'))
		);
	
	// select for text_converter
	$text_converter = $FORM->addElement('select', 'text_converter',
	 	array('id' => 'simple_guestbook_text_converter'),
		array('label' => gettext('Text converter'), 'options' => $TEXTCONVERTER->getTextConverterListForForm())
		);

	// checkbox for apply_macros
	$apply_macros = $FORM->addElement('checkbox', 'apply_macros',
		array('id' => 'simple_guestbook_apply_macros', 'class' => 'chbx'),
		array('label' => gettext('Apply text macros'))
		);
	$apply_macros->addRule('regex', gettext('The field whether to apply text macros accepts only 0 or 1'), WCOM_REGEX_ZERO_OR_ONE);
	
	// select for use_captcha
	$use_captcha = $FORM->addElement('select', 'use_captcha',
	 	array('id' => 'simple_guestbook_use_captcha'),
		array('label' => gettext('Use captcha'), 'options' => $captcha_types)
		);

	// checkbox for meta_use		
	$meta_use = $FORM->addElement('checkbox', 'meta_use',
		array('id' => 'simple_guestbook_meta_use', 'class' => 'chbx'),
		array('label' => gettext('Custom meta tags'))
		);
	$meta_use->addRule('regex', gettext('The field whether to use customized meta tags accepts only 0 or 1'), WCOM_REGEX_ZERO_OR_ONE);
	
	// textfield for meta_title
	$meta_title = $FORM->addElement('text', 'meta_title', 
		array('id' => 'simple_guestbook_meta_title', 'maxlength' => 255, 'class' => 'w300'),
		array('label' => gettext('Title'))
		);
	
	// textarea for meta_keywords
	$meta_keywords = $FORM->addElement('textarea', 'meta_keywords', 
		array('id' => 'simple_guestbook_meta_keywords', 'cols' => 3, 'rows' => '2', 'class' => 'w540h50'),
		array('label' => gettext('Keywords'))
		);

	// textarea for meta_description
	$meta_description = $FORM->addElement('textarea', 'meta_description', 
		array('id' => 'simple_guestbook_meta_description', 'cols' => 3, 'rows' => '2', 'class' => 'w540h50'),
		array('label' => gettext('Description'))
		);

	// checkbox for allow entry
	$allow_entry = $FORM->addElement('checkbox', 'allow_entry',
		array('id' => 'simple_guestbook_allow_entry', 'class' => 'chbx'),
		array('label' => gettext('Allow new entries'))
		);
	$allow_entry->addRule('regex', gettext('The field whether to allow new entries accepts only 0 or 1'), WCOM_REGEX_ZERO_OR_ONE);
	
	// checkbox for send notification
	$send_notification = $FORM->addElement('checkbox', 'send_notification',
		array('id' => 'simple_guestbook_send_notification', 'class' => 'chbx'),
		array('label' => gettext('Send notification'))
		);
	$send_notification->addRule('regex', gettext('The field whether to enable notifications accepts only 0 or 1'), WCOM_REGEX_ZERO_OR_ONE);
	
		
	// textfield for email_from	
	$notification_email_from = $FORM->addElement('text', 'notification_email_from', 
		array('id' => 'simple_guestbook_email_from', 'maxlength' => 255, 'class' => 'w300 validate'),
		array('label' => gettext('From: address'))
		);		
	if ($send_notification->getValue() != '') {
		$notification_email_from->addRule('required', gettext('Please enter a From: address'));
		$notification_email_from->addRule('regex', gettext('Please enter a valid From: address'), WCOM_REGEX_EMAIL);
	}
	
	// textfield for email_to	
	$notification_email_to = $FORM->addElement('text', 'notification_email_to', 
		array('id' => 'simple_guestbook_email_to', 'maxlength' => 255, 'class' => 'w300 validate'),
		array('label' => gettext('To: address'))
		);
	if ($send_notification->getValue() != '') {
		$notification_email_to->addRule('required', gettext('Please enter a To: address'));
		$notification_email_to->addRule('regex', gettext('Please enter a valid To: address'), WCOM_REGEX_EMAIL);
	}
	
	// textfield for email_subject
	$notification_email_subject = $FORM->addElement('text', 'notification_email_subject', 
		array('id' => 'simple_guestbook_email_subject', 'maxlength' => 255, 'class' => 'w300'),
		array('label' => gettext('Subject'))
		);
	if ($send_notification->getValue() != '') {
		$notification_email_subject->addRule('required', gettext('Please enter a subject'));
	}
	
	// submit button (save and stay)
	$save = $FORM->addElement('submit', 'save', 
		array('class' => 'submit200', 'value' => gettext('Save edit'))
		);
		
	// submit button (save and go back)
	$submit = $FORM->addElement('submit', 'submit', 
		array('class' => 'submit200go', 'value' => gettext('Save edit and go back'))
		);
	
	// set text converter value or get default converter
	if (isset($simple_guestbook['text_converter'])) {
		$_text_converter = $simple_guestbook['text_converter'];
	} else {
		if ($default_text_converter > 0) {
			$_text_converter = $default_text_converter['id'];
		} else {
			$_text_converter = null;
		}
	}
	
	// set defaults
	$FORM->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
		'id' => Base_Cnc::ifsetor($simple_guestbook['id'], null),
		'title' => Base_Cnc::ifsetor($simple_guestbook['title'], null),
		'title_url' => Base_Cnc::ifsetor($simple_guestbook['title_url'], null),
		'content' => Base_Cnc::ifsetor($simple_guestbook['content_raw'], null),
		'text_converter' => $_text_converter,
		'apply_macros' => Base_Cnc::ifsetor($simple_guestbook['apply_macros'], null),
		'meta_use' => Base_Cnc::ifsetor($simple_guestbook['meta_use'], null),
		'meta_title' => Base_Cnc::ifsetor($simple_guestbook['meta_title_raw'], null),
		'meta_keywords' => Base_Cnc::ifsetor($simple_guestbook['meta_keywords'], null),
		'meta_description' => Base_Cnc::ifsetor($simple_guestbook['meta_description'], null),
		'use_captcha' => Base_Cnc::ifsetor($simple_guestbook['use_captcha'], null),
		'allow_entry' => Base_Cnc::ifsetor($simple_guestbook['allow_entry'], null),
		'send_notification' => Base_Cnc::ifsetor($simple_guestbook['send_notification'], null),
		'notification_email_from' => Base_Cnc::ifsetor($simple_guestbook['notification_email_from'], null),
		'notification_email_to' => Base_Cnc::ifsetor($simple_guestbook['notification_email_to'], null),
		'notification_email_subject' => Base_Cnc::ifsetor($simple_guestbook['notification_email_subject'], null),
		// ctrl var for frontend view
		'preview' => $_SESSION['preview_ctrl']
	)));
	
	// validate it
	if (!$FORM->validate()) {
		// render it
		$renderer = $BASE->utility->loadQuickFormSmartyRenderer();
		
		// fetch {function} template to set
		// required/error markup on each form fields
		$BASE->utility->smarty->fetch(dirname(__FILE__).'/../quickform.tpl');
	
		// assign the form to smarty
		$BASE->utility->smarty->assign('form', $FORM->render($renderer)->toArray());
		
		// assign paths
		$BASE->utility->smarty->assign('wcom_admin_root_www',
			$BASE->_conf['path']['wcom_admin_root_www']);
	    
		// assign current user and project id
		$BASE->utility->smarty->assign('wcom_current_user', WCOM_CURRENT_USER);
		$BASE->utility->smarty->assign('wcom_current_project', WCOM_CURRENT_PROJECT);
		
		// build session
		$session = array(
			'response' => Base_Cnc::filterRequest($_SESSION['response'], WCOM_REGEX_NUMERIC),
			'preview_ctrl' => Base_Cnc::filterRequest($_SESSION['preview_ctrl'], WCOM_REGEX_NUMERIC)
		);
		
		// assign $_SESSION to smarty
		$BASE->utility->smarty->assign('session', $session);
		
		// empty $_SESSION
		if (!empty($_SESSION['response'])) {
			$_SESSION['response'] = '';
		}
		if (!empty($_SESSION['preview_ctrl'])) {
		  	$_SESSION['preview_ctrl'] = '';
		}
		
		// assign page
		$BASE->utility->smarty->assign("page", $page);
		
		// select available projects
		$select_params = array(
			'user' => WCOM_CURRENT_USER,
			'order_macro' => 'NAME'
		);
		$BASE->utility->smarty->assign('projects', $PROJECT->selectProjects($select_params));
		
		// display the form
		define("WCOM_TEMPLATE_KEY", md5($_SERVER['REQUEST_URI']));
		$BASE->utility->smarty->display('content/pages_simpleguestbooks_content_edit.html', WCOM_TEMPLATE_KEY);
		
		// flush the buffer
		@ob_end_flush();
		
		exit;
	} else {
		// freeze the form
		$FORM->toggleFrozen(true);
		
		// prepare sql data
		$sqlData = array();
		$sqlData['title'] = $title->getValue();
		$sqlData['title_url'] = $title_url->getValue();
		$sqlData['content_raw'] = $content->getValue();
		$sqlData['content'] = $content->getValue();
		$sqlData['text_converter'] = ($text_converter->getValue() > 0) ? 
			$text_converter->getValue() : null;
		$sqlData['apply_macros'] = (string)intval($apply_macros->getValue());
		$sqlData['meta_use'] = $meta_use->getValue();
		$sqlData['meta_title_raw'] = null;
		$sqlData['meta_title'] = null;
		$sqlData['meta_keywords'] = null;
		$sqlData['meta_description'] = null;
		$sqlData['use_captcha'] = $use_captcha->getValue();
		$sqlData['allow_entry'] = (string)intval($allow_entry->getValue());
		$sqlData['send_notification'] = (string)intval($send_notification->getValue());
		$sqlData['notification_email_from'] = $notification_email_from->getValue();
		$sqlData['notification_email_to'] = $notification_email_to->getValue();
		$sqlData['notification_email_subject'] = $notification_email_subject->getValue();
		
		// apply text macros and text converter if required
		if ($text_converter->getValue() > 0 || $apply_macros->getValue() > 0) {
			// extract content
			$content = $content->getValue();
			
			// apply startup and pre text converter text macros 
			if ($apply_macros->getValue() > 0) {
				$content = $TEXTMACRO->applyTextMacros($content, 'pre');
			}
			
			// apply text converter
			if ($text_converter->getValue() > 0) {
				$content = $TEXTCONVERTER->applyTextConverter(
					$text_converter->getValue(),
					$content
				);
			}
			
			// apply post text converter and shutdown text macros 
			if ($apply_macros->getValue() > 0) {
				$content = $TEXTMACRO->applyTextMacros($content, 'post');
			}
			
			// assign content to sql data array
			$sqlData['content'] = $content;
		}
		
		// prepare custom meta tags
		if ($meta_use->getValue() == 1) { 
			$sqlData['meta_title_raw'] = $meta_title->getValue();
			$sqlData['meta_title'] = str_replace("%title", $title->getValue(), 
				$meta_title->getValue());
			$sqlData['meta_keywords'] = $meta_keywords->getValue();
			$sqlData['meta_description'] = $meta_description->getValue();
		}
		
		// test sql data for pear errors
		$HELPER->testSqlDataForPearErrors($sqlData);
		
		// insert it
		try {
			// begin transaction
			$BASE->db->begin();
			
			// execute operation
			$SIMPLEGUESTBOOK->updateSimpleGuestbook($id->getValue(), $sqlData);
			
			// commit
			$BASE->db->commit();
		} catch (Exception $e) {
			// do rollback
			$BASE->db->rollback();
			
			// re-throw exception
			throw $e;
		}

		// controll value
		$saveAndRemainOnPage = $save->getValue();
		
		// add response to session
		if (!empty($saveAndRemainOnPage)) {
			$_SESSION['response'] = 1;
		}
		
		// preview control value
		$activePreview = $preview->getValue();
				
		// add preview_ctrl to session
		if (!empty($activePreview)) {
			$_SESSION['preview_ctrl'] = 1;
		}
				
		// save session
		$SESSION->save();
		
		// clean the buffer
		if (!$BASE->debug_enabled()) {
			@ob_end_clean();
		}
		
		// redirect
		if (!empty($saveAndRemainOnPage)) {
			header("Location: pages_simpleguestbooks_content_edit.php?id=".$id->getValue());
		} else {
			header("Location: pages_select.php");
		}
		exit;
	}
} catch (Exception $e) {
	// clean the buffer
	if (!$BASE->debug_enabled()) {
		@ob_end_clean();
	}
	
	// raise error
	$BASE->error->displayException($e, $BASE->utility->smarty);
	$BASE->error->triggerException($e);
	
	// exit
	exit;
}
?>