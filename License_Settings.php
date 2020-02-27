<?php

namespace vnh;

class License_Settings extends Register_Settings {
	private $license;
	public $activate_errors;
	public $last_activation_error;

	public function __construct(License_Management $license) {
		$this->license = $license;
		$this->prefix = $license->args['slug'];
		$this->option_name = 'license';
		$this->activate_errors = [
			'missing' => esc_html__('The provided license key does not seem to exist.'),
			'revoked' => esc_html__('The provided license key has been revoked. Please contact support.'),
			'no_activations_left' => esc_html__('This license key has been activated the maximum number of times.'),
			'expired' => esc_html__('This license key has expired.'),
			'key_mismatch' => esc_html__('An unknown error has occurred: key_mismatch'),
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

	public function boot() {
		parent::boot();
		add_action('admin_init', function () {
			if (isset($_REQUEST['activate_key']) || isset($_REQUEST['deactivate_key'])) {
				$this->manage_license_activation();
			} else {
				$this->license->check_license();
			}
		});
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

	public function manage_license_activation() {
		$action = isset($_REQUEST['activate_key']) ? 'activate_license' : 'deactivate_license';

		$license_data = request($this->license->args['remote_api_url'], [
			'body' => [
				'edd_action' => $action,
				'license' => $this->license->args['license_key'],
				'item_id' => $this->license->args['item_id'],
				'url' => home_url('/'),
			],
		]);

		if (is_wp_error($license_data)) {
			$this->last_activation_error = $license_data->error;
			add_action('admin_notices', [$this, 'notice_license_activate_error']);

			return;
		}

		if ($action === 'activate_license') {
			if ($license_data->license === 'invalid') {
				add_action('admin_notices', [$this, 'notice_license_invalid']);
			} else {
				add_action('admin_notices', [$this, 'notice_license_valid']);
			}
		} else {
			if ($license_data->license === 'failed') {
				add_action('admin_notices', [$this, 'notice_license_deactivate_failed']);
			} else {
				add_action('admin_notices', [$this, 'notice_license_deactivate_success']);
			}
		}
	}

	public function notice_license_activate_error($error) {
		$html = '<div class="error"><p>';
		$html .= sprintf(
			__('%s license activation failed: %s', 'vnh_textdomain'),
			$this->license->args['name'],
			$this->activate_errors[$this->last_activation_error]
		);
		$html .= '</p></div>';

		echo $html;
	}

	public function notice_license_invalid() {
		$html = '<div class="error"><p>';
		$html .= sprintf(
			__('%s license activation was not successful. Please check your key status below for more information.', 'vnh_textdomain'),
			$this->license->args['name']
		);
		$html .= '</p></div>';

		echo $html;
	}

	public function notice_license_valid() {
		$html = '<div class="updated"><p>';
		$html .= sprintf(__('%s license successfully activated.', 'vnh_textdomain'), $this->license->args['name']);
		$html .= '</p></div>';

		echo $html;
	}

	public function notice_license_deactivate_success() {
		$html = '<div class="updated"><p>';
		$html .= sprintf(__('%s license deactivated successfully.', 'vnh_textdomain'), $this->license->args['name']);
		$html .= '</p></div>';

		echo $html;
	}
}
