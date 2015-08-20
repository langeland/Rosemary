<?php

namespace Rosemary\Service;

class SynchronizeCmsService extends AbstractSynchronizeService {


	protected function task_syncronizeFiles() {

		$cmd = vsprintf('rsync -av --delete %sdocs/fileadmin/ %s/%s/docs/fileadmin/', array(
			$this->seed['datasource'],
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Synchronizing fileadmin directory');

		$cmd = vsprintf('rsync -av --delete %sdocs/uploads/ %s/%s/docs/uploads/', array(
			$this->seed['datasource'],
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Synchronizing uploads directory');
	}

}