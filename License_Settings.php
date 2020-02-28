<?php

namespace vnh;

class License_Settings extends Register_Settings {
	private $license;
	public $license_status;
	public $license_status_option_name;
	public $license_status_message;

	public function __construct(License_Management $license) {
		$this->license = $license;
		$this->prefix = $license->args['slug'];
		$this->option_name = 'license';

		$this->license_status_option_name = $this->get_prefix() . '_license_status';
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
	}

	public function register_setting_fields() {
		return [
			'license' => [
				'fields' => [
					[
						'id' => 'key',
						'name' => __('License Key', 'vnh_textdomain'),
						'type' => 'text',
						'custom_attributes' => [
							'placeholder' => __('Enter your license here', 'vnh_textdomain'),
						],
					],
					[
						'id' => 'action',
						'name' => __('License Action', 'vnh_textdomain'),
						'type' => 'license_action',
					],
				],
			],
		];
	}

	public function render() {
		$this->check_license();
		return parent::render();
	}

	public function boot() {
		parent::boot();
		add_action('admin_init', [$this, 'license_activation']);
	}

	public function license_activation() {
		if ((isset($_REQUEST['activate_key']) || isset($_REQUEST['deactivate_key'])) && check_admin_referer($this->nonce(), $this->nonce())) {
			$this->manage_license_activation();
		}
	}

	public function manage_license_activation() {
		$action = isset($_REQUEST['activate_key']) ? 'activate_license' : 'deactivate_license';

		request($this->license->args['remote_api_url'], [
			'body' => [
				'edd_action' => $action,
				'license' => $this->license->license_key,
				'item_id' => $this->license->args['item_id'],
				'url' => home_url('/'),
			],
		]);
	}

	public function check_license() {
		update_option($this->license_status_option_name, $this->get_license_status());
	}

	private function get_license_status() {
		if (empty($this->license->license_key)) {
			return 'empty_key';
		}

		$license_data = request($this->license->args['remote_api_url'], [
			'body' => [
				'edd_action' => 'check_license',
				'license' => $this->license->license_key,
				'item_id' => $this->license->args['item_id'],
				'url' => home_url('/'),
			],
		]);

		if (is_wp_error($license_data)) {
			return false;
		}

		return $license_data['license'];
	}

	public function display_field_license_action($field, $option) {
		$status = get_option($this->license_status_option_name);

		if ($status === 'inactive' || $status === 'site_inactive') {
			$output = sprintf(
				'<input type="submit" name="activate_key" class="button-secondary" value="Activate Site" /><p style="color:#ffb900;">%s</p>',
				$this->license_status_message[$status]
			);
		} elseif ($status === 'valid') {
			$output = sprintf(
				'<input type="submit" name="deactivate_key" class="button-secondary" value="Deactivate Site" /><p style="color:green;">%s</p>',
				$this->license_status_message[$status]
			);
		} else {
			$output = sprintf('<p style="color:red;">%s</p>', $this->license_status_message[$status]);
		}

		echo $output;
	}
}
