<?php

/**
 * @file classes/template/PKPTemplateResource.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPTemplateResource
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 */

class PKPTemplateResource extends Smarty_Resource_Custom {
	var $coreTemplateDir;

	function __construct($coreTemplateDir) {
		$this->coreTemplateDir = $coreTemplateDir;
	}

	function fetch($name, &$source, &$mtime) {
		$filename = $this->_getFilename($name);
		$source = file_get_contents($filename);
		$mtime = filemtime($filename);
	}

	/**
	 * Resource function to get the timestamp of a "core" (pkp-lib)
	 * template.
	 * @param $template string
	 * @param $templateTimestamp int reference
	 * @return boolean
	 */
	function fetchTimestamp($name) {
		$filename = $this->_getFilename($name);
		return filemtime($filename);
	}

	function _getFilename($template) {
		return $this->coreTemplateDir . DIRECTORY_SEPARATOR . $template;
	}
}

?>
