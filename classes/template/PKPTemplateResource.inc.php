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
 * @brief Representation for a PKP template resource (template directory).
 */

class PKPTemplateResource extends Smarty_Resource_Custom {
	var $templateDir;

	/**
	 * Constructor
	 * @param $templateDir Template directory
	 */
	function __construct($templateDir) {
		$this->templateDir = $templateDir;
	}

	/**
	 * Fetch template contents.
	 * @param $name Template name.
	 * @param $source Template contents to return by reference.
	 * @param $mtime File modification time to return by reference.
	 */
	function fetch($name, &$source, &$mtime) {
		$filename = $this->_getFilename($name);
		$source = file_get_contents($filename);
		$mtime = filemtime($filename);
	}

	/**
	 * Get the timestamp for the specified template.
	 * @param $name Filename for template.
	 * @return int Template modification timestamp.
	 */
	function fetchTimestamp($name) {
		$filename = $this->_getFilename($name);
		return filemtime($filename);
	}

	/**
	 * Get the complete template filename including path.
	 * @param $template Template filename.
	 * @return string
	 */
	function _getFilename($template) {
		return $this->templateDir . DIRECTORY_SEPARATOR . $template;
	}
}

?>
