<?php
/**
 * Cron class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */

namespace AffiLinks;

use AffiLinks\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Cron class.
 */
class Cron {

	/**
	 * API URL.
	 *
	 * @var string
	 */
	private $api_url = 'https://affiliates.studio/wp-json/core/v1/stats';

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @return object initialized object of class.
	 */
	public static function get() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * The single instance of the class.
	 */
	public function __construct() {
		$this->create_cron_jobs();

		if ( Helper::is_request( 'cron' ) ) {
			add_action( 'affilinks_tracker_send_event', [ $this, 'send_tracking_data' ] );
		}
	}

	/**
	 * Create cron jobs (clear them first).
	 */
	private function create_cron_jobs() {
		wp_clear_scheduled_hook( 'affilinks_tracker_send_event' );

		wp_schedule_event( time() + 10, apply_filters( 'affilinks_tracker_event_recurrence', 'weekly' ), 'affilinks_tracker_send_event' );
	}

	/**
	 * Send tracking data.
	 */
	public function send_tracking_data() {
		if ( Helper::is_request( 'ajax' ) ) {
			return;
		}

		// Send a maximum of once per week by default.
		$last_send = $this->get_last_send_time();
		if ( $last_send && $last_send > apply_filters( 'affilinks_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
			return;
		}

		// Update time first before sending to ensure it is set.
		update_option( 'affilinks_tracker_last_send', time() );

		$params = $this->get_tracking_data();
		wp_safe_remote_post(
			$this->api_url,
			[
				'method'  => 'POST',
				'timeout' => 45,
				'body'    => $params,
				'cookies' => [],
			]
		);
	}

	/**
	 * Get the last time tracking data was sent.
	 *
	 * @return int|bool
	 */
	private function get_last_send_time() {
		return apply_filters( 'affilinks_tracker_last_send_time', get_option( 'affilinks_tracker_last_send', false ) );
	}

	/**
	 * Get all the tracking data.
	 *
	 * @return array
	 */
	public function get_tracking_data() {
		$data = [];

		// General site info.
		$data['url']             = home_url();
		$data['email']           = apply_filters( 'affilinks_tracker_admin_email', get_option( 'admin_email' ) );
		$data['active_themes']   = $this->get_active_themes();
		$data['inactive_themes'] = $this->get_inactive_themes();

		// WordPress Info.
		$data['wp'] = $this->get_wordpress_info();

		// Server Info.
		$data['server'] = $this->get_server_info();
		$data['media']  = (array) wp_count_attachments();

		// Plugin info.
		$all_plugins              = $this->get_all_plugins();
		$data['active_plugins']   = $all_plugins['active_plugins'];
		$data['inactive_plugins'] = $all_plugins['inactive_plugins'];

		// Count info.
		$data['users'] = $this->get_user_counts();

		$products = [];
		$orders   = [];
		if ( class_exists( 'WC_Tracker' ) ) {
			$woo_data = WC_Tracker::get_tracking_data();
			$products = $woo_data['products'];
			$orders   = $woo_data['orders'];
		}
		$data['wc-products'] = $products;
		$data['wc-orders']   = $orders;

		return apply_filters( 'affilinks_tracker_data', $data );
	}

	/**
	 * Get user totals based on user role.
	 *
	 * @return array
	 */
	private function get_user_counts() {
		$user_count          = [];
		$user_count_data     = count_users();
		$user_count['total'] = $user_count_data['total_users'];

		// Get user count based on user role.
		foreach ( $user_count_data['avail_roles'] as $role => $count ) {
			$user_count[ $role ] = $count;
		}

		return $user_count;
	}

	/**
	 * Get all plugins grouped into activated or not.
	 *
	 * @return array
	 */
	private function get_all_plugins() {
		// Ensure get_plugins function is loaded.
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins             = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', [] );
		$active_plugins      = [];

		foreach ( $plugins as $k => $v ) {
			// Take care of formatting the data how we want it.
			$formatted         = [];
			$formatted['name'] = wp_strip_all_tags( $v['Name'] );
			if ( isset( $v['Version'] ) ) {
				$formatted['version'] = wp_strip_all_tags( $v['Version'] );
			}
			if ( isset( $v['Author'] ) ) {
				$formatted['author'] = wp_strip_all_tags( $v['Author'] );
			}
			if ( isset( $v['Network'] ) ) {
				$formatted['network'] = wp_strip_all_tags( $v['Network'] );
			}
			if ( isset( $v['PluginURI'] ) ) {
				$formatted['plugin_uri'] = wp_strip_all_tags( $v['PluginURI'] );
			}
			if ( in_array( $k, $active_plugins_keys ) ) { // phpcs:ignore
				// Remove active plugins from list so we can show active and inactive separately.
				unset( $plugins[ $k ] );
				$active_plugins[ $k ] = $formatted;
			} else {
				$plugins[ $k ] = $formatted;
			}
		}

		return [
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => $plugins,
		];
	}

	/**
	 * Get server related info.
	 *
	 * @return array
	 */
	private function get_server_info() {
		$server_data = [];

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_data['software'] = $_SERVER['SERVER_SOFTWARE']; // @phpcs:ignore
		}

		if ( function_exists( 'phpversion' ) ) {
			$server_data['php_version'] = phpversion();
		}

		if ( function_exists( 'ini_get' ) ) {
			$server_data['php_post_max_size']  = size_format( $this->let_to_num( ini_get( 'post_max_size' ) ) );
			$server_data['php_time_limt']      = ini_get( 'max_execution_time' );
			$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
			$server_data['php_suhosin']        = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		$database_version             = $this->get_server_database_version();
		$server_data['mysql_version'] = $database_version['number'];

		$server_data['php_max_upload_size']  = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap']             = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data['php_fsockopen']        = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl']             = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}

	/**
	 * Retrieves the MySQL server version. Based on $wpdb.
	 *
	 * @since 3.4.1
	 * @return array Vesion information.
	 */
	public function get_server_database_version() {
		global $wpdb;

		if ( empty( $wpdb->is_mysql ) ) {
			return [
				'string' => '',
				'number' => '',
			];
		}

		// phpcs:disable WordPress.DB.RestrictedFunctions, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
		if ( $wpdb->use_mysqli ) {
			$server_info = mysqli_get_server_info( $wpdb->dbh );
		} else {
			$server_info = mysql_get_server_info( $wpdb->dbh ); // phpcs:ignore
		}
		// phpcs:enable WordPress.DB.RestrictedFunctions, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved

		return [
			'string' => $server_info,
			'number' => preg_replace( '/([^\d.]+).*/', '', $server_info ),
		];
	}

	/**
	 * Get WordPress related data.
	 *
	 * @return array
	 */
	private function get_wordpress_info() {
		$wp_data = [];

		$memory = $this->let_to_num( WP_MEMORY_LIMIT );

		if ( function_exists( 'memory_get_usage' ) ) {
			$system_memory = $this->let_to_num( @ini_get( 'memory_limit' ) ); // phpcs:ignore
			$memory        = max( $memory, $system_memory );
		}

		// WordPress 5.5+ environment type specification.
		// 'production' is the default in WP, thus using it as a default here, too.
		$environment_type = 'production';
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$environment_type = wp_get_environment_type();
		}

		$wp_data['memory_limit'] = size_format( $memory );
		$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale']       = get_locale();
		$wp_data['version']      = get_bloginfo( 'version' );
		$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';
		$wp_data['env_type']     = $environment_type;

		return $wp_data;
	}

	/**
	 * Notation to numbers.
	 *
	 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
	 *
	 * @param  string $size Size value.
	 * @return int
	 */
	public function let_to_num( $size ) {
		$l   = substr( $size, -1 );
		$ret = (int) substr( $size, 0, -1 );
		switch ( strtoupper( $l ) ) {
			case 'P':
				$ret *= 1024;
				// No break.
			case 'T':
				$ret *= 1024;
				// No break.
			case 'G':
				$ret *= 1024;
				// No break.
			case 'M':
				$ret *= 1024;
				// No break.
			case 'K':
				$ret *= 1024;
				// No break.
		}
		return $ret;
	}

	/**
	 * Get the current theme info, theme name and version.
	 *
	 * @return array
	 */
	public function get_active_themes() {
		$theme_data           = wp_get_theme();
		$theme_child_theme    = is_child_theme();
		$theme_wc_support     = current_theme_supports( 'woocommerce' );
		$theme_is_block_theme = $this->current_theme_is_fse();

		$theme_slug = sanitize_title( $theme_data->Name ); // phpcs:ignore

		$data = [];

		$data[ $theme_slug ] = [
			'name'        => $theme_data->Name, // @phpcs:ignore
			'version'     => $theme_data->Version, // @phpcs:ignore
			'child_theme' => $theme_child_theme,
			'wc_support'  => $theme_wc_support,
			'block_theme' => $theme_is_block_theme,
		];

		return $data;
	}

	/**
	 * Get the current theme info, theme name and version.
	 *
	 * @return array
	 */
	public function get_inactive_themes() {
		$themes = wp_get_themes();
		if ( empty( $themes ) ) {
			return [];
		}

		$inactive_themes = [];

		foreach ( $themes as $theme_slug => $theme_data ) {
			$inactive_themes[ $theme_slug ] = [
				'name'        => $theme_data->Name, // @phpcs:ignore
				'version'     => $theme_data->Version, // @phpcs:ignore
				'child_theme' => '',
				'wc_support'  => '',
				'block_theme' => '',
			];
		}

		return $inactive_themes;
	}

	/**
	 * Get the current theme info, theme name and version.
	 */
	public function current_theme_is_fse() {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return (bool) wp_is_block_theme();
		}
		if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
			return (bool) gutenberg_is_fse_theme();
		}

		return false;
	}
}
