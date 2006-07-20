<?php

/**
 * Project: Oak
 * File: pages_simplepages_content_edit.php
 *
 * Copyright (c) 2006 sopic GmbH
 *
 * Project owner:
 * sopic GmbH
 * 8472 Seuzach, Switzerland
 * http://www.sopic.com/
 *
 * This file is licensed under the terms of the Open Software License
 * http://www.opensource.org/licenses/osl-2.1.php
 *
 * $Id$
 *
 * @copyright 2006 sopic GmbH
 * @author Andreas Ahlenstorf
 * @package Oak
 * @license http://www.opensource.org/licenses/osl-2.1.php Open Software License
 */

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
	
	// load project class
	/* @var $PROJECT Application_Project */
	$PROJECT = load('application:project');
	
	// load page class
	/* @var $PAGE Content_Page */
	$PAGE = load('content:page');
	
	// load simplepage class
	/* @var $SIMPLEPAGE Content_Simplepage */
	$SIMPLEPAGE = load('content:simplepage');
	
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
	$USER->initUserAdmin();
	$PROJECT->initProjectAdmin(OAK_CURRENT_USER);
	
	// get page
	$page = $PAGE->selectPage(Base_Cnc::filterRequest($_REQUEST['id'], OAK_REGEX_NUMERIC));
	
	// get simple page
	$simple_page = $SIMPLEPAGE->selectSimplePage(Base_Cnc::filterRequest($_REQUEST['id'], OAK_REGEX_NUMERIC));
	
	// prepare text converters array
	$text_converters = array(
		'' => gettext('None')
	);
	foreach ($TEXTCONVERTER->selectTextConverters() as $_converter) {
		$text_converters[(int)$_converter['id']] = htmlspecialchars($_converter['name']);
	}
	
	// start new HTML_QuickForm
	$FORM = $BASE->utility->loadQuickForm('simple_page', 'post');
	
	// hidden for navigation
	$FORM->addElement('hidden', 'id');
	$FORM->applyFilter('id', 'trim');
	$FORM->applyFilter('id', 'strip_tags');
	$FORM->addRule('id', gettext('Id is not expected to be empty'), 'required');
	$FORM->addRule('id', gettext('Id is expected to be numeric'), 'numeric');
	
	// textfield for title
	$FORM->addElement('text', 'title', gettext('Title'),
		array('id' => 'simple_page_title', 'maxlength' => 255, 'class' => 'w300'));
	$FORM->applyFilter('title', 'trim');
	$FORM->applyFilter('title', 'strip_tags');
	$FORM->addRule('title', gettext('Please enter a title'), 'required');
	
	// textarea for content
	$FORM->addElement('textarea', 'content', gettext('Content'),
		array('id' => 'simple_page_content', 'cols' => 3, 'rows' => '2', 'class' => 'w540h400'));
	$FORM->applyFilter('content', 'trim');
	
	// select for text_converter
	$FORM->addElement('select', 'text_converter', gettext('Text converter'), $text_converters,
		array('id' => 'simple_page_text_converter'));
	$FORM->applyFilter('text_converter', 'trim');
	$FORM->applyFilter('text_converter', 'strip_tags');
	$FORM->addRule('text_converter', gettext('Chosen text converter is out of range'),
		'in_array_keys', $text_converters);
	
	// checkbox for apply_macros
	$FORM->addElement('checkbox', 'apply_macros', gettext('Apply text macros'), null,
		array('id' => 'simple_page_apply_macros', 'class' => 'chbx'));
	$FORM->applyFilter('apply_macros', 'trim');
	$FORM->applyFilter('apply_macros', 'strip_tags');
	$FORM->addRule('apply_macros', gettext('The field whether to apply text macros accepts only 0 or 1'),
		'regex', OAK_REGEX_ZERO_OR_ONE);
	
	// checkbox for meta_use
	$FORM->addElement('checkbox', 'meta_use', gettext('Custom meta tags'), null,
		array('id' => 'simple_page_meta_use', 'class' => 'chbx'));
	$FORM->applyFilter('meta_use', 'trim');
	$FORM->applyFilter('meta_use', 'strip_tags');
	$FORM->addRule('meta_use', gettext('The field whether to use customized meta tags accepts only 0 or 1'),
		'regex', OAK_REGEX_ZERO_OR_ONE);
	
	// textfield for meta_title
	$FORM->addElement('text', 'meta_title', gettext('Title'),
		array('id' => 'simple_page_meta_title', 'maxlength' => 255, 'class' => 'w300'));
	$FORM->applyFilter('meta_title', 'trim');
	$FORM->applyFilter('meta_title', 'strip_tags');
	
	// textarea for meta_keywords
	$FORM->addElement('textarea', 'meta_keywords', gettext('Keywords'),
		array('id' => 'simple_page_meta_keywords', 'cols' => 3, 'rows' => 2, 'class' => 'w298h50'));
	$FORM->applyFilter('meta_keywords', 'trim');
	$FORM->applyFilter('meta_keywords', 'strip_tags');

	// textarea for meta_description
	$FORM->addElement('textarea', 'meta_description', gettext('Description'),
		array('id' => 'simple_page_meta_description', 'cols' => 3, 'rows' => 2, 'class' => 'w298h50'));
	$FORM->applyFilter('meta_description', 'trim');
	$FORM->applyFilter('meta_description', 'strip_tags');
	
	// submit button
	$FORM->addElement('submit', 'submit', gettext('Update page'),
		array('class' => 'submitbut140'));
	
	// set defaults
	$FORM->setDefaults(array(
		'id' => Base_Cnc::ifsetor($simple_page['id'], null),
		'title' => Base_Cnc::ifsetor($simple_page['title'], null),
		'content' => Base_Cnc::ifsetor($simple_page['content_raw'], null),
		'text_converter' => Base_Cnc::ifsetor($simple_page['text_converter'], null),
		'apply_macros' => Base_Cnc::ifsetor($simple_page['apply_macros'], null),
		'meta_use' => Base_Cnc::ifsetor($simple_page['meta_use'], null),
		'meta_title' => Base_Cnc::ifsetor($simple_page['meta_title_raw'], null),
		'meta_keywords' => Base_Cnc::ifsetor($simple_page['meta_keywords'], null),
		'meta_description' => Base_Cnc::ifsetor($simple_page['meta_description'], null)
	));
	
	// validate it
	if (!$FORM->validate()) {
		// render it
		$renderer = $BASE->utility->loadQuickFormSmartyRenderer();
		$quickform_tpl_path = dirname(__FILE__).'/../quickform.tpl.php';
		include(Base_Compat::fixDirectorySeparator($quickform_tpl_path));
		
		// remove attribute on form tag for XHTML compliance
		$FORM->removeAttribute('name');
		$FORM->removeAttribute('target');
		
		$FORM->accept($renderer);
	
		// assign the form to smarty
		$BASE->utility->smarty->assign('form', $renderer->toArray());
		
		// assign paths
		$BASE->utility->smarty->assign('oak_admin_root_www',
			$BASE->_conf['path']['oak_admin_root_www']);
		
		// build $session
	    $session = array(
			'response' => Base_Cnc::filterRequest($_SESSION['response'], OAK_REGEX_NUMERIC)
	    );
	    
	    // assign $_SESSION to smarty
	    $BASE->utility->smarty->assign('session', $session);
	    
	    // empty $_SESSION
	    if (!empty($_SESSION['response'])) {
	        $_SESSION['response'] = '';
	    }
	    
		// assign current user and project id
		$BASE->utility->smarty->assign('oak_current_user', OAK_CURRENT_USER);
		$BASE->utility->smarty->assign('oak_current_project', OAK_CURRENT_PROJECT);
		
		// assign page
		$BASE->utility->smarty->assign("page", $page);
		
		// select available projects
		$select_params = array(
			'user' => OAK_CURRENT_USER,
			'order_macro' => 'NAME'
		);
		$BASE->utility->smarty->assign('projects', $PROJECT->selectProjects($select_params));
		
		// display the form
		define("OAK_TEMPLATE_KEY", md5($_SERVER['REQUEST_URI']));
		$BASE->utility->smarty->display('content/pages_simplepages_content_edit.html', OAK_TEMPLATE_KEY);
		
		// flush the buffer
		@ob_end_flush();
		
		exit;
	} else {
		// freeze the form
		$FORM->freeze();
		
		// prepare sql data
		$sqlData = array();
		$sqlData['title'] = $FORM->exportValue('title');
		$sqlData['title_url'] = $HELPER->createMeaningfulString($FORM->exportValue('title'));
		$sqlData['content_raw'] = $FORM->exportValue('content');
		$sqlData['content'] = $FORM->exportValue('content');
		$sqlData['text_converter'] = ($FORM->exportValue('text_converter') > 0) ? 
			$FORM->exportValue('text_converter') : null;
		$sqlData['apply_macros'] = (string)intval($FORM->exportValue('apply_macros'));
		$sqlData['meta_use'] = $FORM->exportValue('meta_use');
		$sqlData['meta_title_raw'] = null;
		$sqlData['meta_title'] = null;
		$sqlData['meta_keywords'] = null;
		$sqlData['meta_description'] = null;
		
		// apply text macros and text converter if required
		if ($FORM->exportValue('text_converter') > 0 || $FORM->exportValue('apply_macros') > 0) {
			// extract content
			$content = $FORM->exportValue('content');
			
			// apply startup and pre text converter text macros 
			if ($FORM->exportValue('apply_macros') > 0) {
				$content = $TEXTMACRO->applyTextMacros($content, 'pre');
			}
			
			// apply text converter
			if ($FORM->exportValue('text_converter') > 0) {
				$content = $TEXTCONVERTER->applyTextConverter(
					$FORM->exportValue('text_converter'),
					$content
				);
			}
			
			// apply post text converter and shutdown text macros 
			if ($FORM->exportValue('apply_macros') > 0) {
				$content = $TEXTMACRO->applyTextMacros($content, 'post');
			}
			
			// assign content to sql data array
			$sqlData['content'] = $content;
		}
		
		// prepare custom meta tags
		if ($FORM->exportValue('meta_use') == 1) { 
			$sqlData['meta_title_raw'] = $FORM->exportValue('meta_title');
			$sqlData['meta_title'] = str_replace("%title", $FORM->exportValue('title'), 
				$FORM->exportValue('meta_title'));
			$sqlData['meta_keywords'] = $FORM->exportValue('meta_keywords');
			$sqlData['meta_description'] = $FORM->exportValue('meta_description');
		}
		
		// test sql data for pear errors
		$HELPER->testSqlDataForPearErrors($sqlData);
		
		// insert it
		try {
			// begin transaction
			$BASE->db->begin();
			
			// execute operation
			$SIMPLEPAGE->updateSimplePage($FORM->exportValue('id'), $sqlData);
			
			// commit
			$BASE->db->commit();
		} catch (Exception $e) {
			// do rollback
			$BASE->db->rollback();
			
			// re-throw exception
			throw $e;
		}
		
		// redirect
		$SESSION->save();
		
		// clean the buffer
		if (!$BASE->debug_enabled()) {
			@ob_end_clean();
		}
		
		// redirect
		header("Location: pages_select.php");
		exit;
	}
} catch (Exception $e) {
	// clean the buffer
	if (!$BASE->debug_enabled()) {
		@ob_end_clean();
	}
	
	// raise error
	Base_Error::triggerException($BASE->utility->smarty, $e);	
	
	// exit
	exit;
}
?>