<?php

namespace Rosemary\Utility;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class General {

	public static function getResourcePathAndName($resourceName) {
		if (is_file($_SERVER['HOME'] . '/.rosemary/Resources/' . $resourceName)) {
			return $_SERVER['HOME'] . '/.rosemary/Resources/' . $resourceName;
		} elseif (is_file(ROOT_DIR . '/Resources/' . $resourceName)) {
			return ROOT_DIR . '/Resources/' . $resourceName;
		} else {
			throw new \Exception('Cannot find resources: ' . $resourceName . '. (Looking in: ' . $_SERVER['HOME'] . '/.rosemary/Resources/, ' . ROOT_DIR . '/Resources/)');
		}
	}

	public function isSite (){

	}

	/**
	 *
	 */
	public static function getAlises() {
		$aliasFile = ROOT_DIR . '/Configuration/SiteAliases.yaml';
		if (file_exists($aliasFile) === FALSE) {
			die('Alias file' . $aliasFile . 'not found. ' . PHP_EOL . ' Run rosemary update-aliases first' . PHP_EOL);
		}

		try {
			$yaml = Yaml::parse(file_get_contents($aliasFile));
			return $yaml;
		} catch (ParseException $e) {
			$this->outputLine('Error: ' . $e->getMessage());
		}

	}

}
