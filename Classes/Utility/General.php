<?php

namespace Rosemary\Utility;

class General {

	public static function getResourcePathAndName($resourceName) {
		if (is_file($_SERVER['HOME'] . '/.rosemary/Resources/' . $resourceName)) {
			return $_SERVER['HOME'] . '/.rosemary/Resources/' . $resourceName;
		} elseif (is_file(ROOT_DIR . 'Resources/' . $resourceName)) {
			return ROOT_DIR . 'Resources/' . $resourceName;
		} else {
			throw new \Exception('Cannot find resources: ' . $resourceName);
		}
	}

}
