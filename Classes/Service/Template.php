<?php

namespace Rosemary\Service;

class Template {

	private $template;

	private $vars;

	public function __construct($templatePathAndName) {
		$this->setTemplatePathAndName($templatePathAndName);
	}

	public function setTemplatePathAndName($templatePathAndName) {
		if (file_exists($templatePathAndName)) {
			$this->template = file_get_contents($templatePathAndName);
		} else {
			throw new \Exception('Template not found... ' . $templatePathAndName);
		}
	}

	public function setVar($var, $content) {
		$this->vars[$var] = $content;
	}

	public function render() {
		return $this->replaceAll();
	}

	private function replaceAll() {
		foreach ($this->vars as $var => $content) {
			$this->template = str_replace('{' . $var . '}', $content, $this->template);
		}
	}

//	public function includeFile() {
//		foreach ($this->vars as $var => $content) {
//			$this->template = str_replace("<-" . strtoupper($var) . "->",
//				file_get_contents($content),
//				$this->template);
//		}
//	}

}
