<?php

namespace Rosemary\Utility;

class Tasks {

	/**
	 * @param $basePath
	 * @return void
	 * @throws \Exception
	 */
	public static function task_createDirectories($basePath) {
		if (!mkdir($basePath, 0777)) {
			throw new \Exception('Failed to create folderr: ' . $basePath);
		}

		if (!mkdir($basePath . 'logs/', 0777, TRUE)) {
			throw new \Exception('Failed to create folderr: ' . $basePath . 'logs/');
		}

		if (!mkdir($basePath . 'sync/', 0777, TRUE)) {
			throw new \Exception('Failed to create folderr: ' . $basePath . 'sync/');
		}
	}

	private function task_cloneSource() {
	}

	private function task_createDatabase() {
	}

	private function task_updateSettings() {
	}

	private function task_setfilepermissions() {
	}

	private function task_createVhost() {
	}

	private function task_installVhostAndRestartApache() {
	}

	private function task_executePostCreateCommands() {
	}

}
