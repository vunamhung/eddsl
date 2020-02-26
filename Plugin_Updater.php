<?php

namespace vnh;

use vnh\contracts\Bootable;

class Plugin_Updater implements Bootable {
	private $response_key;
	private $plugin_name;
	public $remote_api_url;
	public $plugin_slug;
	public $version;
	public $api_args;

	public function __construct($args) {
		$this->plugin_slug = $args['slug'];
		$this->response_key = $this->plugin_slug . '-update-response';
		$this->plugin_name = plugin_basename($args['plugin_file']);
		$this->version = $args['version'];
		$this->api_args = [
			'edd_action' => 'get_version',
			'license' => $args['license'],
			'item_id' => $args['item_id'],
		];
	}

	public function boot() {
		add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
		add_filter('plugins_api', [$this, 'plugins_api_filter'], 10, 3);
		add_action('admin_init', [$this, 'show_changelog']);

		remove_action('after_plugin_row_' . $this->plugin_name, 'wp_plugin_update_row');
		add_action('after_plugin_row_' . $this->plugin_name, [$this, 'show_update_notification'], 10, 2);
	}

	public function get_update_data() {
		$update_data = get_transient($this->response_key);

		if ($update_data === false) {
			$update_data = request($this->remote_api_url, ['body' => $this->api_args]);

			// If the response failed, try again in 30 minutes
			if (is_wp_error($update_data)) {
				$data = new \stdClass();
				$data->new_version = $this->version;
				set_transient($this->response_key, $data, MINUTE_IN_SECONDS * 30);

				return false;
			}

			// If the status is 'ok', return the update arguments
			$update_data->sections = maybe_unserialize($update_data->sections);
			set_transient($this->response_key, $update_data, HOUR_IN_SECONDS * 12);
		}

		if (version_compare($this->version, $update_data->new_version, '>=')) {
			return false;
		}

		return (array) $update_data;
	}

	public function delete_theme_update_transient() {
		delete_transient($this->response_key);
	}
}
