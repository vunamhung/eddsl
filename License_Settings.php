<?php

namespace vnh;

class License_Settings extends Register_Settings {
	private $license;

	public function __construct(License_Management $license) {
		$this->license = $license;
		$this->prefix = $license->args['slug'];
		$this->option_name = 'license';
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
		$this->license->check_license();
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
				'license' => $this->license->args['license_key'],
				'item_id' => $this->license->args['item_id'],
				'url' => home_url('/'),
			],
		]);
	}

	public function display_field_license_action($field, $option) {
		$status = get_option($this->license->license_status_option_name);

		if ($status === 'inactive' || $status === 'site_inactive') {
			$output = sprintf(
				'<input type="submit" name="activate_key" class="button-secondary" value="Activate Site" /><p style="color:#ffb900;">%s</p>',
				$this->license->license_status_message[$status]
			);
		} elseif ($status === 'valid') {
			$output = sprintf(
				'<input type="submit" name="deactivate_key" class="button-secondary" value="Deactivate Site" /><p style="color:green;">%s</p>',
				$this->license->license_status_message[$status]
			);
		} else {
			$output = sprintf('<p style="color:red;">%s</p>', $this->license->license_status_message[$status]);
		}

		echo $output;
	}
}
