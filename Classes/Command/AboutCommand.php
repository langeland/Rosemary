<?php
namespace Rosemary\Command;

class AboutCommand extends \Symfony\Component\Console\Command\Command {

	/**
	 * {@inheritdoc}
	 */
	protected function configure() {
		$this
			->setName('about')
			->setDescription('Displays help for a command');
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {

		$output->writeln(wordwrap(
			file_get_contents(ROOT_DIR . '/Resources/About.text'),
			80
		));

//		$helper = new \Symfony\Component\Console\Helper\DescriptorHelper();
//		$helper->describe($output, $this->getApplication());

	}
}
