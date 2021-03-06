<?php

/**
 * Project: Welcompose
 * File: globaltemplates_add.php
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
 * @author Andreas Ahlenstorf
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
	
	// start Base_Session
	/* @var $SESSION Session */
	$SESSION = load('Base:Session');

	// load user class
	/* @var $USER User_User */
	$USER = load('User:User');
	
	// load User_Login
	/* @var $LOGIN User_Login */
	$LOGIN = load('User:Login');
	
	// load Application_Project
	/* @var $PROJECT Application_Project */
	$PROJECT = load('application:project');
	
	// load Templating_GlobalTemplate
	/* @var $GLOBALTEMPLATE Templating_GlobalTemplate */
	$GLOBALTEMPLATE = load('Templating:GlobalTemplate');
	
	// load Utility_Helper class
	/* @var $HELPER Utility_Helper */
	$HELPER = load('Utility:Helper');
	
	// init user and project
	if (!$LOGIN->loggedIntoAdmin()) {
		header("Location: ../login.php");
		exit;
	}
	$USER->initUserAdmin();
	$PROJECT->initProjectAdmin(WCOM_CURRENT_USER);
	
	// check access
	if (!wcom_check_access('Templating', 'GlobalTemplate', 'Manage')) {
		throw new Exception("Access denied");
	}
	
	// assign current user values
	$_wcom_current_user = $USER->selectUser(WCOM_CURRENT_USER);
	$BASE->utility->smarty->assign('_wcom_current_user', $_wcom_current_user);
	
	// assign current project values
	$_wcom_current_project = $PROJECT->selectProject(WCOM_CURRENT_PROJECT);
	$BASE->utility->smarty->assign('_wcom_current_project', $_wcom_current_project);
	
	// get global template
	$global_template = $GLOBALTEMPLATE->selectGlobalTemplate(Base_Cnc::filterRequest($_REQUEST['id'],
		WCOM_REGEX_NUMERIC));
	
	// prepare mime types
	$mime_types = array(
		'text/css' => 'text/css',
		'text/html' => 'text/html',
		'text/javascript' => 'text/javascript',
		'text/plain' => 'text/plain',
		'text/xml' => 'text/xml'
	);
	
	// start new HTML_QuickForm
	$FORM = $BASE->utility->loadQuickForm('global_template');

	// apply filters to all fields
	$FORM->addRecursiveFilter('trim');
	
	// hidden for id
	$id = $FORM->addElement('hidden', 'id', array('id' => 'id'));
	
	// hidden for start
	$start = $FORM->addElement('hidden', 'start', array('id' => 'start'));

	
	// textfield for name		
	$name = $FORM->addElement('text', 'name', 
		array('id' => 'global_template_name', 'maxlength' => 255, 'class' => 'w300 validate'),
		array('label' => gettext('Name'))
		);
	$name->addRule('required', gettext('Please enter a name'));
	$name->addRule('regex', gettext('Please enter a valid global template name'), WCOM_REGEX_GLOBAL_TEMPLATE_NAME);
	$name->addRule('callback', gettext('A global template with the given name already exists'), 
		array(
			'callback' => array($GLOBALTEMPLATE, 'testForUniqueName'),
			'arguments' => array($id->getValue())
		)
	);

	// textarea for description
	$description = $FORM->addElement('textarea', 'description', 
		array('id' => 'global_template_description', 'cols' => 3, 'rows' => 2, 'class' => 'w298h50'),
		array('label' => gettext('Description'))
		);
		
	// textarea for content
	$content = $FORM->addElement('textarea', 'content', 
		array('id' => 'global_template_content', 'cols' => 3, 'rows' => 2, 'class' => 'w540h550'),
		array('label' => gettext('Content'))
		);
	
	// select for mime type
	$mime_type = $FORM->addElement('select', 'mime_type',
	 	array('id' => 'global_template_mime_type'),
		array('label' => gettext('MIME Type'), 'options' => $mime_types)
		);
	$mime_type->addRule('required', gettext('Please select a mime type'));
	
	// checkbox for change delimiter
	$change_delimiter = $FORM->addElement('checkbox', 'change_delimiter',
		array('id' => 'global_template_change_delimiter', 'class' => 'chbx'),
		array('label' => gettext('Change delimiter'))
		);
	$change_delimiter->addRule('regex', gettext('The field whether to change the delimiver accepts only 0 or 1'), WCOM_REGEX_ZERO_OR_ONE);
		
	// submit button (save and stay)
	$save = $FORM->addElement('submit', 'save', 
		array('class' => 'submit200', 'value' => gettext('Save edit'))
		);
		
	// submit button (save and go back)
	$submit = $FORM->addElement('submit', 'submit', 
		array('class' => 'submit200go', 'value' => gettext('Save edit and go back'))
		);
		
	// set defaults
	$FORM->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
		'start' => Base_Cnc::filterRequest($_REQUEST['start'], WCOM_REGEX_NUMERIC),
		'id' => Base_Cnc::ifsetor($global_template['id'], null),
		'name' => Base_Cnc::ifsetor($global_template['name'], null),
		'description' => Base_Cnc::ifsetor($global_template['description'], null),
		'content' => Base_Cnc::ifsetor($global_template['content'], null),
		'mime_type' => Base_Cnc::ifsetor($global_template['mime_type'], null),
		'change_delimiter' => Base_Cnc::ifsetor($global_template['change_delimiter'], null)
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
			'response' => Base_Cnc::filterRequest($_SESSION['response'], WCOM_REGEX_NUMERIC)
		);
		
		// assign $_SESSION to smarty
		$BASE->utility->smarty->assign('session', $session);
		
		// empty $_SESSION
		if (!empty($_SESSION['response'])) {
			$_SESSION['response'] = '';
		}

		// select available projects
		$select_params = array(
			'user' => WCOM_CURRENT_USER,
			'order_macro' => 'NAME'
		);
		$BASE->utility->smarty->assign('projects', $PROJECT->selectProjects($select_params));
		
		// display the form
		define("WCOM_GLOBALTEMPLATE_KEY", md5($_SERVER['REQUEST_URI']));
		$BASE->utility->smarty->display('templating/globaltemplates_edit.html', WCOM_GLOBALTEMPLATE_KEY);
		
		// flush the buffer
		@ob_end_flush();
		
		exit;
	} else {
		// freeze the form
		$FORM->toggleFrozen(true);
		
		// create the article group
		$sqlData = array();
		$sqlData['name'] = $name->getValue();
		$sqlData['description'] = $description->getValue();
		$sqlData['content'] = $content->getValue();
		$sqlData['mime_type'] = $mime_type->getValue();
		$sqlData['change_delimiter'] = (string)intval($change_delimiter->getValue());
		
		// check sql data
		$HELPER = load('utility:helper');
		$HELPER->testSqlDataForPearErrors($sqlData);
		
		// insert it
		try {
			// begin transaction
			$BASE->db->begin();
			
			// execute operation
			$GLOBALTEMPLATE->updateGlobalTemplate($id->getValue(),
				$sqlData);
			
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
				
		// redirect
		$SESSION->save();
		
		// clean the buffer
		if (!$BASE->debug_enabled()) {
			@ob_end_clean();
		}
		
		// save request start range
		$start = $start->getValue();
		$start = (!empty($start)) ? $start : 0;
		
		// redirect
		if (!empty($saveAndRemainOnPage)) {
			header("Location: globaltemplates_edit.php?id=".$id->getValue()."&start=".$start);
		} else {
			header("Location: globaltemplates_select.php?start=".$start);
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