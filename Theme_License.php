<?php

namespace vnh;

use DI\Container;
use DI\ContainerBuilder;
use vnh\contracts\Initable;
use function DI\autowire;
use function DI\create;
use function DI\get;

class Theme_License implements Initable {
	/**
	 * @var Container
	 */
	public $container;

	public function __construct($args) {
		$builder = new ContainerBuilder();
		$builder->addDefinitions([
			'args' => wp_parse_args($args, [
				'theme_slug' => THEME_SLUG,
				'parent_menu_slug' => THEME_SLUG,
				'name' => THEME_NAME,
				'version' => THEME_VERSION,
				'menu_title' => esc_html__('Theme License', 'vnh_textdomain'),
			]),
			License_Settings::class => create()->constructor(get('args'), THEME_SLUG),
			License_Page::class => autowire()->constructor(get('args')),
			Theme_Updater::class => autowire()->constructor(get('args')),
		]);

		$this->container = $builder->build();
	}

	public function init() {
		$this->container->get(License_Settings::class)->boot();
		$this->container->get(License_Page::class)->boot();

		if ($this->container->get(License_Settings::class)->get_option('status') === 'valid') {
			$this->container->get(Theme_Updater::class)->boot();
		}
	}
}
