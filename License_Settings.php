<?php

namespace vnh;

class License_Settings extends Register_Settings {
	public $license;

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
						'description' => $this->license->license_status,
						'type' => 'license_key',
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

	public function display_field_license_key($field, $option) {
		$output = sprintf(
			'<input type="text" name="%1$s" id="%1$s" %3$s value="%2$s"/><p>%4$s</p>',
			$this->get_name_attr($field),
			!empty($option[$field['id']]) ? esc_attr($option[$field['id']]) : null,
			$this->get_custom_attribute_html($field),
			$field['description']
		);

		$output .= sprintf(
			'<input type="hidden" name="%1$s" id="%1$s" value="%2$s"/>',
			'base-dev_license_settings[status]',
			!empty($option[$field['id']]) ? esc_attr($option[$field['id']]) : null
		);

		echo $output;
	}

	public function display_field_license_action($field, $option) {
		$output = '<input type="submit" name="activate_key" class="button-secondary" value="Activate Site" />';

		echo $output;
	}
}
