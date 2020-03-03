<?php

namespace vnh;

class License_Settings extends Register_Settings {
	public $args;

	public function __construct($args, $prefix) {
		$this->args = $args;

		$this->prefix = $prefix;
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

	public function boot() {
		parent::boot();
		add_action('admin_init', [$this, 'manage_license_activation']);
	}

	public function manage_license_activation() {
		if ((isset($_REQUEST['activate_key']) || isset($_REQUEST['deactivate_key'])) && check_admin_referer($this->nonce(), $this->nonce())) {
			request($this->args['remote_api_url'], [
				'body' => [
					'edd_action' => isset($_REQUEST['activate_key']) ? 'activate_license' : 'deactivate_license',
					'license' => $this->get_option('key'),
					'item_id' => $this->args['item_id'],
					'url' => home_url('/'),
				],
			]);
		}
	}

	public function check_license() {
		$options = $this->get_options();

		$options['status'] = 'invalid';

		if (empty($this->get_option('key'))) {
			$options['message'] = esc_html__('Empty license.', 'vnh_textdomain');
			$this->update_options($options);
			return;
		}

		$license_data = request($this->args['remote_api_url'], [
			'body' => [
				'edd_action' => 'check_license',
				'license' => $this->get_option('key'),
				'item_id' => $this->args['item_id'],
				'url' => home_url('/'),
			],
		]);

		if (is_wp_error($license_data)) {
			$options['message'] = esc_html__('An error occurred, please try again.', 'vnh_textdomain');
			$this->update_options($options);
			return;
		}

		if ($license_data['success']) {
			switch ($license_data['license']) {
				case 'expired':
					$message = sprintf(
						esc_html__('Your license key expired on %s. ', 'vnh_textdomain') . $this->get_renewal_link(),
						date_i18n(get_option('date_format'), strtotime($license_data['expires'], current_time('timestamp')))
					);
					break;

				case 'disabled':
				case 'revoked':
					$message = esc_html__('Your license key has been disabled.', 'vnh_textdomain');
					break;

				case 'missing':
					$message = esc_html__('Invalid license.', 'vnh_textdomain');
					break;

				case 'invalid':
				case 'site_inactive':
					$message = esc_html__('Your license is not active for this URL.', 'vnh_textdomain');
					break;

				case 'item_name_mismatch':
					$message = sprintf(esc_html__('This appears to be an invalid license key for %s.', 'vnh_textdomain'), $this->args['item_id']);
					break;

				case 'no_activations_left':
					$message = esc_html__('Your license key has reached its activation limit.', 'vnh_textdomain');
					break;

				default:
					$message = esc_html__('Your license key is valid and active for this site.', 'vnh_textdomain');
					break;
			}

			$options['message'] = $message;
		} else {
			$options['message'] = esc_html__('Invalid license.', 'vnh_textdomain');
		}

		$options['status'] = $license_data['license'];
		$this->update_options($options);
	}

	public function get_renewal_link() {
		$checkout_url = $this->args['remote_api_url'] . '/checkout';
		$renewal_url = add_query_arg(['edd_license_key' => $this->get_option('key'), 'download_id' => $this->args['item_id']], $checkout_url);

		return sprintf('<a href="%s" target="_blank">%s</a>', $renewal_url, __('Renew it now.', 'vnh_textdomain'));
	}

	public function display_field_license_action($field, $option) {
		$this->check_license();

		$status = $this->get_option('status');

		if ($status === 'inactive' || $status === 'site_inactive') {
			$output = sprintf(
				'<input type="submit" name="activate_key" class="button-secondary" value="Activate Site" /><p style="color:#ffb900;">%s</p>',
				$this->get_option('message')
			);
		} elseif ($status === 'valid') {
			$output = sprintf(
				'<input type="submit" name="deactivate_key" class="button-secondary" value="Deactivate Site" /><p style="color:green;">%s</p>',
				$this->get_option('message')
			);
		} else {
			$output = sprintf('<p style="color:red;">%s</p>', $this->get_option('message'));
		}

		echo $output;
	}
}
