<?php

namespace vnh;

use vnh\contracts\Initable;

class License_Management implements Initable {
	public $args;

	public $settings;
	public $settings_page;
	public $updater;

	public $license_key;

	public function __construct($args) {
		$this->args = $args;
		$this->args['slug'] = $this->get_slug();

		$this->settings = new License_Settings($this);

		$this->license_key = trim($this->settings->get_option('key'));
		$this->settings_page = new License_Page($args, $this->settings);
		$this->updater = isset($args['theme_slug']) ? new Theme_Updater($this) : new Plugin_Updater($this);
	}

	public function init() {
		$this->settings->boot();
		$this->settings_page->boot();
		if ($this->settings->license_status === 'valid') {
			$this->updater->boot();
		}
	}

	private function get_slug() {
		return isset($this->args['theme_slug']) ? $this->args['theme_slug'] : basename($this->args['plugin_file'], '.php');
	}
}
