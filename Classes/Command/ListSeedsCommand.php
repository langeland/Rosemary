<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;

/**
 * Class ListSeedsCommand
 *
 * @author Jon Klixbüll Langeland <jon@moc.net>
 * @package Rosemary\Command
 */
class ListSeedsCommand extends \Rosemary\Command\AbstractCommand {

	/**
	 *
	 */
	protected function configure() {
		$this
			->setName('list-seeds')
			->setDescription('List all site seeds');
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

		$output->writeln('Available seeds');
		$table = new \Symfony\Component\Console\Helper\Table($output);
		$table->setHeaders(array('Name', 'Description', 'Type'));
		$seeds = General::getSeeds();
		ksort($seeds);
		foreach ($seeds as $seed => $seedConfiguration) {
			if ($seedConfiguration['type'] == 'cms') {
				$typeField = 'CMS';
				if ($seedConfiguration['version']) {
					$typeField .= ' (' . $seedConfiguration['version'] . ')';
				}
			} elseif ($seedConfiguration['type'] == 'flow') {
				$typeField = 'Flow/NEOS';
			} else {
				$typeField = 'N/A';
			}
			$table->addRow(array($seed, $seedConfiguration['description'], $typeField));
		}
		$table->render();
	}
}