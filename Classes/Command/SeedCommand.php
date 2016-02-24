<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;

/**
 * Class ListSeedsCommand
 *
 * @author Jon Klixbüll Langeland <jon@moc.net>
 * @package Rosemary\Command
 */
class SeedCommand extends \Rosemary\Command\AbstractCommand {

	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this
			->setName('seed')
			->setDescription('Seed handling')
			->addArgument('action', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the name of the installation. If no name is given, then the seed is used');
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @throws \Exception
	 * @return null
	 */
	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
		$actions = array('list', 'update');
		if (in_array($this->input->getArgument('action'), $actions)) {
			call_user_func(array($this, $this->input->getArgument('action') . 'Action'));
		}
	}

	private function listAction() {
		$this->output->writeln('Available seeds');
		$table = new \Symfony\Component\Console\Helper\Table($this->output);
		$table->setHeaders(array('Name', 'Description', 'Type'));
		$seeds = General::getSeeds();
		ksort($seeds);
		foreach ($seeds as $seed => $seedConfiguration) {

			if (isset($seedConfiguration['type'])) {
				if ($seedConfiguration['type'] == 'cms') {
					$typeField = 'CMS';
					if ($seedConfiguration['version']) {
						$typeField .= ' (' . $seedConfiguration['version'] . ')';
					}
				} elseif ($seedConfiguration['type'] == 'flow') {
					$typeField = 'Flow/NEOS';
				}
			} else {
				$typeField = 'N/A';
			}
			$table->addRow(array($seed, $seedConfiguration['description'], $typeField));
		}
		$table->render();
	}

	private function updateAction() {
		$this->output->writeln('Updating seeds');

		if(!is_dir($_SERVER['HOME'] . '/.rosemary')){
			if (!mkdir($_SERVER['HOME'] . '/.rosemary', 0777)) {
				$this->output->writeln('Failed to create folders... ' . $_SERVER['HOME'] . '/.rosemary');
				die(1);
			}
		}

		$source = 'rsync@moc-files.moc.net:/volume1/developer/Seeds2.yaml';
		$destination = $_SERVER['HOME'] . '/.rosemary/Seeds2.yaml';
		$cmd = sprintf('scp %s %s', $source, $destination);
		$this->output->writeln($cmd);
		\Rosemary\Utility\General::runCommand($this->output, $cmd);
	}

}
