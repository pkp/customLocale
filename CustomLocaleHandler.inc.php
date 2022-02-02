<?php

/**
 * @file CustomLocaleHandler.inc.php
 *
 * Copyright (c) 2016-2020 Language Science Press
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

		$contextFileManager = new ContextFileManager($context ? $context->getId() : CONTEXT_SITE);
		$customLocaleDir = $contextFileManager->getBasePath() . 'customLocale';
		if (!file_exists($customLocaleDir) || !is_dir($customLocaleDir)) throw new Exception("Path \"$customLocaleDir\" does not exist!");

		// get all xml-files in the custom locale directory
		$directory = new RecursiveDirectoryIterator($customLocaleDir);
		$iterator = new RecursiveIteratorIterator($directory);
		$regex = new RegexIterator($iterator, '/^.+\.xml$/i', RecursiveRegexIterator::GET_MATCH);
		$files = iterator_to_array($regex);
		$fileKeys = array_keys($files);

		$output = '';

		import('lib.pkp.classes.i18n.LocaleFile');
		// iterate through all customized files
		foreach ($fileKeys as $pathToFile) {
			$posLib = strpos($pathToFile,'lib');
			$posLocale = strpos($pathToFile,'locale');
			$posPlugins = strpos($pathToFile,'plugins');

			$localeFile = '';
			if (!$posLib===false) {
				$localeFile = substr($pathToFile,$posLib);
			} else if (!$posPlugins===false) {
				$localeFile = substr($pathToFile,$posPlugins);
			}
			else {
				$localeFile = substr($pathToFile,$posLocale);
			}

			$localeContentsCustomized = null;
			if ($contextFileManager->fileExists($pathToFile)) {
				$localeContentsCustomized = LocaleFile::load($pathToFile);
			}

			$localeContents = null;
			if ($contextFileManager->fileExists($localeFile)) {
				$localeContents = LocaleFile::load($localeFile);
			}

			$localeKeys = array_keys($localeContentsCustomized);

			if (sizeof($localeKeys)>0) {
				$output = $output . "\nFile: " . $localeFile;
			}

			foreach ($localeKeys as $index => $localeKey) {
				$output = $output . "\n\n" . $index+1 . '. locale key: ' . $localeKey;
				$output = $output . "\n\n	original content:   " . $localeContents[$localeKey];
				$output = $output . "\n	customized content: " . $localeContentsCustomized[$localeKey];
			}
			if (!empty($localeKeys)) {
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

