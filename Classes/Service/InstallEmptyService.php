<?php

namespace Rosemary\Service;

class InstallEmptyService extends Service {

	public function install($installationConfiguration) {
		try {
			$this->output->writeln('InstallEmptyService');
			$this->task_createDirectories();
			$this->task_createVhost();
			$this->task_installVhostAndRestartApache();
		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}
}