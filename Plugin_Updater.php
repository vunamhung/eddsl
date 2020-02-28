<?php

namespace vnh;

use stdClass;
use vnh\contracts\Bootable;

class Plugin_Updater implements Bootable {
	private $license;
	private $response_key;
	private $plugin_base;

	public function __construct(License_Management $license) {
		$this->license = $license;
		$this->response_key = $license->args['slug'] . '-update-response';
		$this->plugin_base = plugin_basename($license->args['plugin_file']);
	}

	public function boot() {
		add_filter('pre_set_site_transient_update_plugins', [$this, 'plugin_update_transient']);
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @param array $_transient_data Update array build by WordPress.
	 *
	 * @return array Modified update array with custom plugin data.
	 * @uses api_request()
	 *
	 */
	public function plugin_update_transient($_transient_data) {
		global $pagenow;

		if (!is_object($_transient_data)) {
			$_transient_data = new stdClass();
		}

		if ($pagenow === 'plugins.php' && is_multisite()) {
			return $_transient_data;
		}

		if (!empty($_transient_data->response) && !empty($_transient_data->response[$this->plugin_base])) {
			return $_transient_data;
		}

		if ($this->get_update_data()) {
			$_transient_data->last_checked = current_time('timestamp');
			$_transient_data->checked[$this->plugin_base] = $this->get_update_data();
		}

		return $_transient_data;
	}

	public function get_update_data() {
		$update_data = get_transient($this->response_key);

		if ($update_data === false) {
			if (empty($this->license->license_key)) {
				return false;
			}

			$update_data = request($this->license->args['remote_api_url'], [
				'body' => [
					'edd_action' => 'get_version',
					'license' => $this->license->license_key,
					'item_id' => $this->license->args['item_id'],
				],
			]);

			// If the response failed, try again in 30 minutes
			if (is_wp_error($update_data)) {
				$data['new_version'] = $this->license->args['version'];
				set_transient($this->response_key, $data, MINUTE_IN_SECONDS * 30);

				return false;
			}

			// If the status is 'ok', return the update arguments
			$update_data['sections'] = maybe_unserialize($update_data['sections']);
			$update_data['banners'] = maybe_unserialize($update_data['banners']);
			set_transient($this->response_key, $update_data, HOUR_IN_SECONDS * 12);
		}

		if (version_compare($this->license->args['version'], $update_data['new_version'], '>=')) {
			return false;
		}

		return (array) $update_data;
	}

	public function delete_theme_update_transient() {
		delete_transient($this->response_key);
	}
}
