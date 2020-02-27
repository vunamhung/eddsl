<?php

namespace vnh;

use vnh\contracts\Initable;

class License_Management implements Initable {
	public $args;

	public $settings;
	public $settings_page;
	public $updater;

	public $license_status;
	public $license_status_option_name;
	public $license_status_message;

	public function __construct($args) {
		$this->args = $args;
		$this->args['slug'] = $this->get_slug();

		$this->license_status_option_name = $this->args['slug'] . '_license_status';
		$this->license_status = get_option($this->license_status_option_name);
		$this->license_status_message = [
			'empty_key' => esc_html__('The entered license key is not valid.', 'vnh_textdomain'),
			'invalid' => esc_html__('The entered license key is not valid.', 'vnh_textdomain'),
			'expired' => esc_html__('Your key has expired and needs to be renewed.', 'vnh_textdomain'),
			'inactive' => esc_html__('Your license key is valid, but is not active.', 'vnh_textdomain'),
			'disabled' => esc_html__('Your license key is currently disabled. Please contact support.', 'vnh_textdomain'),
			'site_inactive' => esc_html__('Your license key is valid, but not active for this site.', 'vnh_textdomain'),
			'valid' => esc_html__('Your license key is valid and active for this site.', 'vnh_textdomain'),
		];

		$this->settings = new License_Settings($this);

		$this->args['license_key'] = trim($this->settings->get_option('key'));
		$this->settings_page = new License_Page($args, $this->settings);
		$this->updater = $this->get_updater($args, $this->settings);
	}

	public function init() {
		$this->settings->boot();
		$this->settings_page->boot();
		if ($this->license_status === 'valid') {
			$this->updater->boot();
		}
	}

	public function check_license() {
		update_option($this->license_status_option_name, $this->get_license_status());
	}

	private function get_license_status() {
		if (empty($this->args['license_key'])) {
			return 'empty_key';
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
			return false;
		}

		return $license_data['license'];
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
