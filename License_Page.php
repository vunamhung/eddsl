<?php

namespace vnh;

use vnh\contracts\Bootable;

class License_Page implements Bootable {
	public $settings;
	public $parent_menu_slug;
	public $menu_slug;

	public function __construct($args, $settings) {
		$this->settings = $settings;
		$this->parent_menu_slug = $args['parent_menu_slug'];
		$this->menu_slug = $this->parent_menu_slug . '-license';
	}

	public function boot() {
		add_action('admin_menu', [$this, 'license_page'], 99);
	}

	public function license_page() {
		add_submenu_page(
			$this->parent_menu_slug,
			esc_html__('License', 'vnh_textdomain'),
			esc_html__('License', 'vnh_textdomain'),
			'manage_options',
			$this->menu_slug,
			[$this, 'callback']
		);
	}

	public function callback() {
		$html = '<div class="wrap about-wrap theme_info_wrapper">';
		$html .= '<h1>' . esc_html__('Welcome to License page', 'vnh_textdomain') . '</h1>';
		$html .= $this->settings;
		$html .= '</div>';

		echo $html;
	}
}
