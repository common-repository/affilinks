<?php // @codingStandardsIgnoreLine
/**
 * AffiLinks Plugin.
 *
 * @package      AffiLinks
 * @copyright    Copyright (C) 2014-2024, AffiLinks dev@affiliates.studio
 * @link         https://affiliates.studio
 * @since        1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       AffiLinks
 * Version:           1.1.0
 * Plugin URI:        https://affiliates.studio/affilinks/
 * Description:       Effortlessly manage, track, and optimize affiliate links 🔗 and brands 💼 with comprehensive analytics 📈, all within your WordPress site. 🚀
 * Author:            AffiLinks
 * Author URI:        https://affiliates.studio/
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       affilinks
 * Domain Path:       /languages
 *
  */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'aff_fs' ) ) {
	aff_fs()->set_basename( false, __FILE__ );
} else {

	/**
	 * AffiLinks class.
	 *
	 * @class Main class of the plugin.
	 */
	final class AffiLinks {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = '1.1.0';

		/**
		 * The single instance of the class.
		 *
		 * @var AffiLinks
		 */
		protected static $instance = null;

		/**
		 * Retrieve main AffiLinks instance.
		 *
		 * Ensure only one instance is loaded or can be loaded.
		 *
		 * @see affilinks()
		 * @return AffiLinks
		 */
		public static function get() {
			if ( is_null( self::$instance ) && ! ( self::$instance instanceof AffiLinks ) ) {
				self::$instance = new AffiLinks();
				self::$instance->setup();
			}

			return self::$instance;
		}

		/**
		 * Instantiate the plugin.
		 */
		private function setup() {
			// Define plugin constants.
			$this->define_constants();

			// Include required files.
			$this->includes();

			// Instantiate classes.
			$this->instantiate();

			// Loaded action.
			do_action( 'affilinks/loaded' );
		}

		/**
		 * Define the plugin constants.
		 */
		private function define_constants() {
			define( 'AFFILINKS_VERSION', $this->version );
			define( 'AFFILINKS_FILE', __FILE__ );
			define( 'AFFILINKS_BASE', plugin_basename( AFFILINKS_FILE ) );
			define( 'AFFILINKS_DIR', plugin_dir_path( AFFILINKS_FILE ) );
			define( 'AFFILINKS_URI', plugins_url( '/', AFFILINKS_FILE ) );
		}

		/**
		 * Include the required files.
		 */
		private function includes() {
			include dirname( __FILE__ ) . '/vendor/autoload.php';
		}

		/**
		 * Instantiate classes.
		 */
		private function instantiate() {
			new \AffiLinks\Analytics();
			new \AffiLinks\KB();
			new \AffiLinks\Post();
			new \AffiLinks\Rest_API();
			new \AffiLinks\Shortcodes();
			new \AffiLinks\Track();
			new \AffiLinks\Track\Rest_API();
			new \AffiLinks\Cron();

			// Initialize the action and filter hooks.
			$this->init_actions();

			// WP_CLI.
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				add_action( 'plugins_loaded', [ $this, 'init_wp_cli' ], 20 );
			}

			// DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
			if ( ! function_exists( 'aff_fs' ) ) {
				require_once AFFILINKS_DIR . 'lib/freemius-init.php';
			}
		}

		/**
		 * Add our custom WP-CLI commands.
		 */
		public function init_wp_cli() {
			WP_CLI::add_command( 'affilinks flush', [ '\AffiLinks\CLI\Commands', 'flush' ] );
		}

		/**
		 * Initialize WordPress action and filter hooks.
		 */
		private function init_actions() {
			register_activation_hook( AFFILINKS_FILE, [ $this, 'activation' ] );

			// Add plugin action links.
			add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
			add_filter( 'plugin_action_links_' . AFFILINKS_BASE, [ $this, 'plugin_action_links' ] );
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @param  mixed $links Plugin Action links.
		 * @return array
		 */
		public function plugin_action_links( $links ) {
			$options = [
				'edit.php?post_type=aff_brand' => esc_html__( 'Brands', 'affilinks' ),
				'edit.php?post_type=aff_link'  => esc_html__( 'Links', 'affilinks' ),
				'edit.php?post_type=aff_asset' => esc_html__( 'Assets', 'affilinks' ),
				'edit.php?post_type=aff_brand&page=affilinks-analytics' => esc_html__( 'Analytics', 'affilinks' ),
			];

			foreach ( $options as $link => $label ) {
				$plugin_links[] = '<a href="' . esc_url( admin_url( $link ) ) . '">' . esc_html( $label ) . '</a>';
			}

			return array_merge( $links, $plugin_links );
		}

		/**
		 * Add extra links as row meta on the plugin screen.
		 *
		 * @param  mixed $links Plugin Row Meta.
		 * @param  mixed $file  Plugin Base file.
		 * @return array
		 */
		public function plugin_row_meta( $links, $file ) {

			if ( plugin_basename( AFFILINKS_FILE ) !== $file ) {
				return $links;
			}

			$more = [
				'<a href="https://affiliates.studio/doc/affilinks/getting-started/">' . esc_html__( 'Getting Started', 'affilinks' ) . '</a>',
				'<a href="https://affiliates.studio/">' . esc_html__( 'Affiliates Studio', 'affilinks' ) . '</a>',
			];

			return array_merge( $links, $more );
		}

		/**
		 * Activation hook.
		 */
		public function activation() {
			update_option( 'affilinks_flush_rewrite_rules', 'yes' );

			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name      = $wpdb->prefix . 'affilinks';

			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				brand_id mediumint(9) NOT NULL,
				affiliate_id mediumint(9) NOT NULL,
				affiliate_type varchar(255) DEFAULT '' NOT NULL,
				placeholder_id mediumint(9) NOT NULL,
				group_id mediumint(9) NOT NULL,
				action varchar(255) DEFAULT '' NOT NULL,
				visit_type varchar(255) DEFAULT '' NOT NULL,
				source_id mediumint(9) NOT NULL,
				source_type varchar(255) DEFAULT '' NOT NULL,
				entry datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			dbDelta( $sql );
		}

	}

	/**
	 * Returns the main instance of AffiLinks to prevent the need to use globals.
	 *
	 * @return AffiLinks
	 */
	function affilinks() {
		return AffiLinks::get();
	}

	affilinks();
}
