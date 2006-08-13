<?php

/**
 * Project: Oak
 * File: smarty_public.inc.php
 * 
 * Copyright (c) 2006 sopic GmbH
 * 
 * Project owner:
 * sopic GmbH
 * 8472 Seuzach, Switzerland
 * http://www.sopic.com/
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * $Id$
 * 
 * @copyright 2006 sopic GmbH
 * @author Andreas Ahlenstorf
 * @package Oak
 * @license http://www.opensource.org/licenses/apache2.0.php Apache License, Version 2.0
 */

/**
 * Whitelist for select plugins. Prevents undesired execution of
 * internal functions/methods. Takes the namespace name as first
 * argument, the class name as second argument and the method name
 * as third argument. These will be tested against the whitelist
 * patterns defined within the function. Returns true if the
 * argument combination passed the whitelist.
 * 
 * @param string Namespace name
 * @param string Class name
 * @param string Method name
 * @param bool 
 */
function oak_smarty_select_whitelist ($ns, $class, $method)
{
	// configure white list
	$whitelist = array(
		array(
			'namespaces' => '=^(.*)$=',
			'classes' => '=^(.*)$=',
			'methods' => '=^select([a-z]+)$=i'
		)
	);
	
	foreach ($whitelist as $_entry) {
		if (preg_match($_entry['namespaces'], $ns) && preg_match($_entry['classes'], $class)
		&& preg_match($_entry['methods'], $method)) {
			return true;
		}
	}
	
	return false;
}

// define constants
if (!defined('SMARTY_DIR')) {
	define('SMARTY_DIR', dirname(__FILE__).'/smarty/');
}
if (!defined('SMARTY_TPL_DIR')) {
	define('SMARTY_TPL_DIR', realpath(dirname(__FILE__).'/../../smarty/'));
}

// load the oak resource plugin
require_once(SMARTY_DIR.'software_extensions/resource.oak.php');
$resource_functions = array(
	"oakresource_FetchTemplate",
	"oakresource_FetchTimestamp",
	"oakresource_isSecure",
	"oakresource_isTrusted"
);
$smarty->register_resource("oak", $resource_functions);
unset($resource_functions);

// configure smarty
$smarty->template_dir = SMARTY_TPL_DIR.'/templates';
$smarty->compile_dir = SMARTY_TPL_DIR.'/compiled';
$smarty->cache_dir = SMARTY_TPL_DIR.'/cache';
$smarty->plugins_dir = array(
	SMARTY_DIR.'my_plugins',
	SMARTY_DIR.'plugins',
	SMARTY_DIR.'software_plugins'
);

?>
