<?php

namespace vnh;

use vnh\contracts\Initable;

class License_Management implements Initable {
	public $args;

	public $settings;
	public $settings_page;
	public $updater;

	public function __construct($args) {
		$this->args = $args;
		$this->settings = new License_Settings($this->args);

		$this->args['license_key'] = trim($this->settings->get_option('key'));
		$this->settings_page = new License_Page($this->args, $this->settings);
		$this->updater = isset($this->args['theme_slug']) ? new Theme_Updater($this->args) : new Plugin_Updater($this->args);
	}

	public function init() {
		$this->settings->boot();
		$this->settings_page->boot();
		if ($this->settings->get_option('status') === 'valid') {
			$this->updater->boot();
		}
	}
}
