<?php

namespace Rosemary\Utility;

class RecursiveTreeIterator {

	/**
	 * @param $basePath
	 * @return void
	 * @throws \Exception
	 */
	public static function getTree($array) {
		$treeIterator = new RecursiveTreeIterator(
			new RecursiveArrayIterator($array),
			RecursiveTreeIterator::SELF_FIRST);

		foreach ($treeIterator as $val) {
			echo $val, PHP_EOL;
		}
	}

}
