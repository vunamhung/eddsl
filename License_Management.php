<?php

namespace vnh;

use vnh\contracts\Initable;

class License_Management implements Initable {
	public $args;

	public $settings;
	public $settings_page;
	public $updater;

	public $license_status;

	public function __construct($args) {
		$this->args = $args;
		$this->args['slug'] = $this->get_slug();

		$this->settings = new License_Settings($this);

		$this->settings_page = new License_Page($args, $this->settings);
		$this->updater = $this->get_updater($args, $this->settings);
		$this->args['license_key'] = trim($this->settings->get_option('key'));

		$this->license_status = $this->get_license_status();
	}

	public function init() {
		$this->settings->boot();
		$this->settings_page->boot();
		if ($this->license_status === 'valid') {
			$this->updater->boot();
		}
	}

	private function get_license_status() {
		$transient = $this->args['slug'] . '-license-status-response-key';
		$license_status = get_transient($transient);

		if ($license_status === false) {
			if (empty($this->args['license_key'])) {
				return false;
			}

			$license_data = request($this->args['remote_api_url'], [
				'body' => [
					'edd_action' => 'check_license',
					'license' => $this->args['license_key'],
					'item_id' => $this->args['item_id'],
					'url' => home_url('/'),
				],
			]);

			if (is_wp_error($license_data)) {
				set_transient($transient, 'invalid', MINUTE_IN_SECONDS * 30);

				return false;
			}

			set_transient($transient, $license_data['license'], HOUR_IN_SECONDS * 12);
		}

		return $license_status;
	}

	private function get_slug() {
		return isset($this->args['theme_slug']) ? $this->args['theme_slug'] : basename($this->args['plugin_file'], '.php');
	}

	private function get_updater($args, $settings) {
		if (isset($args['theme_slug'])) {
			return new Theme_Updater($args, $settings);
		}

		return new Plugin_Updater($args, $settings);
	}
}
