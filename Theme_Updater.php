<?php

namespace vnh;

use vnh\contracts\Bootable;

class Theme_Updater implements Bootable {
	public $args;
	public $settings;
	private $response_key;

	public function __construct($args, License_Settings $settings) {
		$this->args = $args;
		$this->settings = $settings;
		$this->response_key = $args['theme_slug'] . '_update_response';
	}

	public function boot() {
		add_filter('site_transient_update_themes', [$this, 'theme_update_transient']);
		add_action('load-themes.php', [$this, 'load_themes_screen']);
		add_filter('delete_site_transient_update_themes', [$this, 'delete_theme_update_transient']);
		add_action('load-update-core.php', [$this, 'delete_theme_update_transient']);
	}

	public function load_themes_screen() {
		add_thickbox();
		delete_transient($this->response_key);
		add_action('admin_notices', [$this, 'update_nag'], -1);
	}

	public function theme_update_transient($value) {
		if ($this->get_update_data()) {
			$value->response[$this->args['theme_slug']] = $this->get_update_data();
		}

		return $value;
	}

	public function get_update_data() {
		$update_data = get_transient($this->response_key);

		if ($update_data === false) {
			if (empty($this->settings->get_option('key'))) {
				return false;
			}

			$update_data = request($this->args['remote_api_url'], [
				'body' => [
					'edd_action' => 'get_version',
					'license' => $this->settings->get_option('key'),
					'item_id' => $this->args['item_id'],
				],
			]);

			// If the response failed, try again in 30 minutes
			if (is_wp_error($update_data)) {
				$data['new_version'] = $this->args['version'];
				set_transient($this->response_key, $data, MINUTE_IN_SECONDS * 30);

				return false;
			}

			// If the status is 'ok', return the update arguments
			$update_data['theme'] = $this->args['theme_slug'];
			$update_data['sections'] = maybe_unserialize($update_data['sections']);
			set_transient($this->response_key, $update_data, HOUR_IN_SECONDS * 12);
		}

		if (version_compare($this->args['version'], $update_data['new_version'], '>=')) {
			return false;
		}

		return (array) $update_data;
	}

	public function update_nag() {
		$api_response = get_transient($this->response_key);

		if ($api_response === false) {
			return;
		}

		$update_url = add_query_arg(['action' => 'upgrade-theme', 'theme' => urlencode($this->args['theme_slug'])], admin_url('update.php'));

		$update_onclick =
			' onclick="if ( confirm(\'' .
			esc_js(__("Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.", 'vnh_textdomain')) .
			'\') ) {return true;}return false;"';

		if (version_compare($this->args['version'], $api_response['new_version'], '<')) {
			$html = '<div class="notice notice-warning settings-error is-dismissible"><p>';
			$html .= sprintf(__('<strong>%1$s %2$s</strong> is available. ', 'vnh_textdomain'), $this->args['name'], $api_response['new_version']);
			if ($api_response['sections']['changelog']) {
				$html .= sprintf(
					__('<a href="%s" class="thickbox" title="%s">Check out what\'s new</a> or ', 'vnh_textdomain'),
					sprintf('#TB_inline?width=640&amp;inlineId=%s_changelog', $this->args['theme_slug']),
					$this->args['name']
				);
			}
			$html .= sprintf(
				__('<a href="%s" %s>Update now</a>.', 'vnh_textdomain'),
				wp_nonce_url($update_url, sprintf('upgrade-theme_%s', $this->args['theme_slug'])),
				$update_onclick
			);
			$html .= '</p></div>';
			if ($api_response['sections']['changelog']) {
				$html .= sprintf('<div id="%s_changelog" style="display:none;">', $this->args['theme_slug']);
				$html .= wpautop($api_response['sections']['changelog']);
				$html .= '</div>';
			}

			echo $html;
		}
	}

	public function delete_theme_update_transient() {
		delete_transient($this->response_key);
	}
}
