<?php

namespace vnh;

use vnh\contracts\Bootable;

class Theme_Updater implements Bootable {
	private $response_key;
	private $theme_name;
	public $remote_api_url;
	public $theme_slug;
	public $version;
	public $api_args;

	public function __construct($args) {
		$this->theme_slug = $args['slug'];
		$this->response_key = $this->theme_slug . '-update-response';
		$this->theme_name = wp_get_theme($this->theme_slug)->get('Name');
		$this->remote_api_url = $args['remote_api_url'];
		$this->version = $args['version'];
		$this->api_args = [
			'edd_action' => 'get_version',
			'license' => $args['license'],
			'item_id' => $args['item_id'],
		];
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
		add_action('admin_notices', [$this, 'update_nag']);
	}

	public function theme_update_transient($value) {
		if ($this->get_update_data()) {
			$value->response[$this->theme_slug] = $this->get_update_data();
		}

		return $value;
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
			$update_data['theme'] = $this->theme_slug;
			$update_data->sections = maybe_unserialize($update_data->sections);
			set_transient($this->response_key, $update_data, HOUR_IN_SECONDS * 12);
		}

		if (version_compare($this->version, $update_data->new_version, '>=')) {
			return false;
		}

		return (array) $update_data;
	}

	public function update_nag() {
		$api_response = get_transient($this->response_key);

		if ($api_response === false) {
			return;
		}

		$update_url = wp_nonce_url(
			sprintf('update.php?action=upgrade-theme&amp;theme=%s', urlencode($this->theme_slug)),
			sprintf('upgrade-theme_%s', $this->theme_slug)
		);

		$update_onclick = sprintf(
			'onclick="if ( confirm("%s") ) {return true;}return false;"',
			esc_html__("Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.", 'vnh_textdomain')
		);

		if (version_compare($this->version, $api_response->new_version, '<')) {
			$html = '<div id="update-nag">';
			printf(
				__(
					'<strong>%1$s %2$s</strong> is available. <a href="%3$s" class="thickbox" title="%1$s">Check out what\'s new</a> or <a href="%4$s" %5$s>update now</a>.',
					'vnh_textdomain'
				),
				$this->theme_name,
				$api_response->new_version,
				sprintf('#TB_inline?width=640&amp;inlineId=%s_changelog', $this->theme_slug),
				$update_url,
				$update_onclick
			);
			$html .= '</div>';
			$html .= sprintf('<div id="%s_changelog" style="display:none;">', $this->theme_slug);
			$html .= wpautop($api_response->sections['changelog']);
			$html .= '</div>';

			echo $html;
		}
	}

	public function delete_theme_update_transient() {
		delete_transient($this->response_key);
	}
}
