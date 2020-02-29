<?php

namespace vnh;

use vnh\contracts\Bootable;

class License_Page implements Bootable {
	public $settings;
	public $parent_menu_slug;
	public $menu_slug;
	public $page_title;
	public $menu_title;

	public function __construct($args, $settings) {
		$this->settings = $settings;
		$this->parent_menu_slug = $args['parent_menu_slug'];
		$this->menu_slug = $this->parent_menu_slug . '-license';
		$this->page_title = isset($args['theme_slug'])
			? esc_html__('Theme License', 'vnh_textdomain')
			: esc_html__('Plugin License', 'vnh_textdomain');
		$this->menu_title = $this->page_title;
	}

	public function boot() {
		add_action('admin_menu', [$this, 'license_page'], 99);
	}

	public function license_page() {
		add_submenu_page($this->parent_menu_slug, $this->page_title, $this->menu_title, 'manage_options', $this->menu_slug, [$this, 'callback']);
	}

	public function callback() {
		$html = '<div class="wrap">';
		$html .= '<h3>' . __('Welcome to <strong>License page</strong>', 'vnh_textdomain') . '</h3>';
		$html .= '<p>' . __('Enter your license to receive automatic update', 'vnh_textdomain') . '</p>';
		$html .= $this->settings;
		$html .= '</div>';

		echo $html;
	}
}
