<?php

namespace vnh;

class License_Setting extends Register_Settings {
	public function __construct($prefix) {
		$this->prefix = $prefix;
		$this->option_name = 'license';
		$this->setting_fields = [
			'license' => [
				'fields' => [
					[
						'id' => 'key',
						'name' => __('License Key', 'vnh_textdomain'),
						'description' => __('On/off global', 'vnh_textdomain'),
						'type' => 'license',
						'custom_attributes' => [
							'placeholder' => __('Enter your license here', 'vnh_textdomain'),
						],
					],
				],
			],
		];
	}

	public function display_field_license($field, $option) {
		$output = sprintf(
			'<input type="text" name="%1$s" id="%1$s" %3$s value="%2$s"/>%4$s',
			$this->get_name_attr($field),
			!empty($option[$field['id']]) ? esc_attr($option[$field['id']]) : null,
			$this->get_custom_attribute_html($field),
			$field['description']
		);

		echo $output;
	}
}
