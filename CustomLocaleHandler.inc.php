<?php

/**
 * @file plugins/generic/customLocale/CustomLocaleHandler.inc.php
 *
 * Copyright (c) 2016 Language Science Press
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleHandler
 */

import('classes.handler.Handler');

class CustomLocaleHandler extends Handler {
	/**
	 * Print the custom locale changes.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function printCustomLocaleChanges($args, $request) {
		$context = $request->getContext();
		$contextId = $context->getId();

		/* UZH CHANGE OJS-71 2019/03/29/mb set path according to application (OJS or OMP) */
		/* $publicFilesDir = Config::getVar('files', 'public_files_dir'); */
		/* $customLocaleDir = "$publicFilesDir/presses/$contextId/" . CUSTOM_LOCALE_DIR; */
		$contextDir = PublicFileManager::getContextFilesPath(Application::getContextAssocType(), $contextId);
		$customLocaleDir = "$contextDir/" . CUSTOM_LOCALE_DIR;
		/* END UZH CHANGE OJS-71 */

		$absolutePath = dirname(__FILE__);
		$ompPath = $request->getBasePath();
		if (!file_exists($customLocaleDir) || !is_dir($customLocaleDir)) fatalError("Path \"$customLocaleDir\" does not exist!");

		// get all xml-files in the custom locale directory
		$directory = new RecursiveDirectoryIterator($customLocaleDir);
		$iterator = new RecursiveIteratorIterator($directory);
		$regex = new RegexIterator($iterator, '/^.+\.xml$/i', RecursiveRegexIterator::GET_MATCH);
		$files = iterator_to_array($regex);
		$fileKeys = array_keys($files);

		import('lib.pkp.classes.file.FileManager');
		import('lib.pkp.classes.file.EditableLocaleFile');

		$output = '';

		// iterate through all customized files
		for ($i=0; $i<sizeof($fileKeys);$i++) {

			$pathToFile = $fileKeys[$i];
			$posLib = strpos($pathToFile,'lib');
			$posLocale = strpos($pathToFile,'locale');
			$posPlugins = strpos($pathToFile,'plugins');

			$ompFile = '';
			if (!$posLib===false) {
				$ompFile = substr($pathToFile,$posLib);
			} else if (!$posPlugins===false) {
				$ompFile = substr($pathToFile,$posPlugins);
			}
			else {
				$ompFile = substr($pathToFile,$posLocale);
			}

			$fileManagerCustomized = new FileManager();
			$localeContentsCustomized = null;
			if ($fileManagerCustomized->fileExists($fileKeys[$i])) {
				$localeContentsCustomized = EditableLocaleFile::load($fileKeys[$i]);
			}

			$fileManager = new FileManager();
			$localeContents = null;
			if ($fileManager->fileExists($ompFile)) {
				$localeContents = EditableLocaleFile::load($ompFile);
			}

			$localeKeys = array_keys($localeContentsCustomized);

			if (sizeof($localeKeys)>0) {
				$output = $output . "\nFile: " . $ompFile;
			}

			for ($ii=0; $ii<sizeof($localeKeys);$ii++) {
				$pos = $ii+1;
				$output = $output . "\n\n" . $pos . '. locale key: ' . $localeKeys[$ii];
				$output = $output . "\n\n	original content:   " . $localeContents[$localeKeys[$ii]];
				$output = $output . "\n	customized content: " . $localeContentsCustomized[$localeKeys[$ii]];
			}
			if (sizeof($localeKeys)>0) {
				$output = $output . "\n\n__________________________________________________________________________________\n\n";
			}
		}

		$filename = 'customLocale_changes.txt';
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Length: ' . strlen($output));
		echo $output;
	}
}

