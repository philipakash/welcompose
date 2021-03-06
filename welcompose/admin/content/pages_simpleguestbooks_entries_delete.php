<?php

/**
 * Project: Welcompose
 * File: pages_simpleguestbooks_entries_delete.php
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
	
	// load Content_SimpleGuestbookEntry class
	$SIMPLEGUESTBOOKENTRY = load('Content:SimpleGuestbookEntry');
	
	// init user and project
	if (!$LOGIN->loggedIntoAdmin()) {
		header("Location: ../login.php");
		exit;
	}
	$USER->initUserAdmin();
	$PROJECT->initProjectAdmin(WCOM_CURRENT_USER);
	
	// check access
	if (!wcom_check_access('Content', 'SimpleGuestbookEntry', 'Manage')) {
		throw new Exception("Access denied");
	}
	
	// assign current user values
	$_wcom_current_user = $USER->selectUser(WCOM_CURRENT_USER);
	$BASE->utility->smarty->assign('_wcom_current_user', $_wcom_current_user);
	
	// assign current project values
	$_wcom_current_project = $PROJECT->selectProject(WCOM_CURRENT_PROJECT);
	$BASE->utility->smarty->assign('_wcom_current_project', $_wcom_current_project);
	
	try {
		// start transaction
		$BASE->db->begin();
		
		// get page
		$page = $PAGE->selectPage(Base_Cnc::filterRequest($_REQUEST['page'], WCOM_REGEX_NUMERIC));
		
		// make sure that we got a page and page id
		if (is_null(Base_Cnc::ifsetor($page['id'], null))) {
			header("Location: pages_select.php");
			exit;
		}
		
		// delete row
		$SIMPLEGUESTBOOKENTRY->deleteSimpleGuestbookEntry(Base_Cnc::filterRequest($_REQUEST['id'], WCOM_REGEX_NUMERIC));
		
		// commit transaction
		$BASE->db->commit();
	} catch (Exception $e) {
		// do rollback
		$BASE->db->rollback();
		
		// re-throw exception
		throw $e;
	}

	// clean buffer
	if (!$BASE->debug_enabled()) {
		@ob_end_clean();
	}
	
	// save request params 
	$start = Base_Cnc::filterRequest($_REQUEST['start'], WCOM_REGEX_NUMERIC);
	$limit = Base_Cnc::filterRequest($_REQUEST['limit'], WCOM_REGEX_NUMERIC);
	$timeframe = Base_Cnc::filterRequest($_REQUEST['timeframe'], WCOM_REGEX_TIMEFRAME);
	$search_name = Base_Cnc::filterRequest($_REQUEST['search_name'], WCOM_REGEX_SEARCH_NAME);
	$macro = Base_Cnc::filterRequest($_REQUEST['macro'], WCOM_REGEX_ORDER_MACRO);
	
	// append request params
	$redirect_params = (!empty($start)) ? '&start='.$start : '';
	$redirect_params .= (!empty($limit)) ? '&limit='.$limit : '&limit=20';
	$redirect_params .= (!empty($timeframe)) ? '&timeframe='.$timeframe : '';
	$redirect_params .= (!empty($macro)) ? '&macro='.$macro : '';
	$redirect_params .= (!empty($search_name)) ? '&search_name='.$search_name : '';
		
	// go back to overview page
	header("Location: pages_simpleguestbooks_entries_select.php?page=".(int)$page['id'].$redirect_params);
	exit;
	
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