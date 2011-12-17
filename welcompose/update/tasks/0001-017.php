<?php

/**
 * Project: Welcompose
 * File: 0001-017.php
 *
 * Copyright (c) 2009 creatics
 *
 * Project owner:
 * creatics, Olaf Gleba
 * 50939 Köln, Germany
 * http://www.creatics.de
 *
 * This file is licensed under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE v3
 * http://www.opensource.org/licenses/agpl-v3.html
 *
 * @copyright 2011 creatics, Olaf Gleba
 * @author Olaf Gleba
 * @package Welcompose
 * @license http://www.opensource.org/licenses/agpl-v3.html GNU AFFERO GENERAL PUBLIC LICENSE v3
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
	$smarty_update_conf = dirname(__FILE__).'/../smarty.inc.php';
	$BASE->utility->loadSmarty(Base_Compat::fixDirectorySeparator($smarty_update_conf), true);
	
	// load gettext
	$gettext_path = dirname(__FILE__).'/../../core/includes/gettext.inc.php';
	include(Base_Compat::fixDirectorySeparator($gettext_path));
	gettextInitSoftware($BASE->_conf['locales']['all']);
	
	// start Base_Session
	/* @var $SESSION session */
	$SESSION = load('base:session');
	
	// connect to database
	$BASE->loadClass('database');
	
	// define major/minor task number
	define('TASK_MAJOR', '0001');
	define('TASK_MINOR', '017');
	
	// get schema version from database
	$sql = "
		SELECT
			`schema_version`
		FROM
			".WCOM_DB_APPLICATION_INFO."
		LIMIT
			1
	";
	$version = $BASE->db->select($sql, 'field');
	list($major, $minor) = explode('-', $version);
	
	/*
	 * References
	 * ----------
	 *
	 * Commit: no decent allocation possible
	 * 
	 * Changes to be applied
	 * ---------------------
	 *
	 * - Update CONSTRAINT ON DELETE Attribute on table content_blog_tags2content_blog_postings
	     from NO ACTION to CASCADE
	 */
	if ($major < TASK_MAJOR || ($major == TASK_MAJOR && $minor < TASK_MINOR)) {
		try {
			// begin transaction
			$BASE->db->begin();
			
			// disable foreign key checks
			$BASE->db->execute('SET FOREIGN_KEY_CHECKS = 0');

			// drop foreign keys
			$sql = "
				ALTER TABLE
					".WCOM_DB_CONTENT_BLOG_TAGS2CONTENT_BLOG_POSTINGS."
				DROP 
					FOREIGN KEY `content_blog_postings.id2content_blog_tags.id`,
				DROP
					FOREIGN KEY `content_blog_tags.id2content_blog_postings.id`
			";

			$BASE->db->execute($sql);
			
			// add contraints
			$sql = "
				ALTER TABLE
					".WCOM_DB_CONTENT_BLOG_TAGS2CONTENT_BLOG_POSTINGS."
				ADD CONSTRAINT `content_blog_postings.id2content_blog_tags.id` 
				  FOREIGN KEY (`posting`) 
					REFERENCES `content_blog_postings`(`id`)
				  ON DELETE CASCADE
				  ON UPDATE CASCADE,
				ADD CONSTRAINT `content_blog_tags.id2content_blog_postings.id` 
				  FOREIGN KEY (`tag`) 
					REFERENCES `content_blog_tags`(`id`)
				  ON DELETE CASCADE
				  ON UPDATE CASCADE				
			";

			$BASE->db->execute($sql);
			
			// enable foreign key checks
			$BASE->db->execute('SET FOREIGN_KEY_CHECKS = 1');

			// update schema version
			$sqlData = array(
				'schema_version' => TASK_MAJOR.'-'.TASK_MINOR
			);

			$BASE->db->update(WCOM_DB_APPLICATION_INFO, $sqlData);

			// commit
			$BASE->db->commit();
		} catch (Exception $e) {
			// do rollback
			$BASE->db->rollback();

			// re-throw exception
			throw $e;
		}
	}

	// flush the buffer
	@ob_end_flush();

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