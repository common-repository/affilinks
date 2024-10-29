<?php
/**
 * Track class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */

namespace AffiLinks;

defined( 'ABSPATH' ) || exit;

/**
 * Track class.
 */
class Track {

	/**
	 * Brand ID.
	 *
	 * @var int
	 */
	private $brand_id = 0;

	/**
	 * Affiliate ID.
	 *
	 * @var int
	 */
	private $affiliate_id = 0;

	/**
	 * Affiliate type.
	 *
	 * @var string
	 */
	private $affiliate_type = '';

	/**
	 * The single instance of the class.
	 *
	 * @var Track
	 */
	protected static $instance = null;

	/**
	 * Retrieve main Track instance.
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Track
	 */
	public static function get() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof Track ) ) {
			self::$instance = new Track();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'template_redirect', [ $this, 'template_redirect' ], 1 );
		add_action( 'init', [ $this, 'start_session' ] );
	}

	/**
	 * Start session
	 */
	public function start_session() {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( ! session_id() ) {
			session_start();
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue() {
		if ( is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style( 'affilinks', AFFILINKS_URI . 'assets/front/css/track.css', [], AFFILINKS_VERSION, 'all' );
		wp_enqueue_script( 'affilinks', AFFILINKS_URI . 'assets/front/js/track.js', [ 'jquery' ], AFFILINKS_VERSION, true );
		wp_localize_script(
			'affilinks',
			'AffiLinksVars',
			[
				'rest_url'    => rest_url( 'affilinks/v1' ),
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'site_url'    => site_url( '/' ),
				'links'       => $this->get_short_links(),
				'assets'      => $this->get_short_links( 'asset' ),
				'source_id'   => get_queried_object_id(),
				'source_type' => $this->get_source_type(),
				'visit_type'  => $this->get_visit_type(),
				'nonce'       => wp_create_nonce( 'affilinks' ),
			]
		);
	}

	/**
	 * Get short links.
	 *
	 * @param string $type The type of the link.
	 */
	public function get_short_links( $type = 'link' ) {
		global $wpdb;

		$post_type = 'aff_link';
		if ( 'asset' === $type ) {
			$post_type = 'aff_asset';
		}

		$short_links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value
				FROM {$wpdb->prefix}postmeta
				WHERE meta_key = 'aff_short_link'
				AND post_id IN ( SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = %s )",
				$post_type
			),
			ARRAY_A
		);

		$links = [];

		foreach ( $short_links as $link ) {
			$links[] = site_url() . '/' . $link['meta_value'];
		}

		return $links;
	}

	/**
	 * Get the source type.
	 */
	public static function get_source_type() {
		if ( is_singular() ) {
			return 'singular';
		} elseif ( is_category() || is_tag() || is_tax() ) {
			return 'archive';
		} elseif ( is_author() ) {
			return 'user';
		}

		return 'other';
	}

	/**
	 * Get the visit type.
	 */
	public static function get_visit_type() {
		return get_queried_object_id() ? 'referer' : 'direct';
	}

	/**
	 * Impressions
	 *
	 * @param int    $affiliate_id The affiliate ID.
	 * @param string $affiliate_type The affiliate type.
	 * @param int    $placeholder_id The placeholder ID.
	 */
	public static function impressions( $affiliate_id = 0, $affiliate_type = '', $placeholder_id = 0 ) {
		if ( ! $affiliate_id || ! $affiliate_type ) {
			return;
		}

		$data = self::get_data();
		if ( empty( $data ) ) {
			return;
		}

		self::get()->track(
			[
				'action'         => 'impression',
				'affiliate_id'   => $affiliate_id,
				'affiliate_type' => $affiliate_type,
				'placeholder_id' => isset( $data['placeholder_id'] ) ? absint( $data['placeholder_id'] ) : 0,
				'brand_id'       => isset( $data['brand_id'] ) ? absint( $data['brand_id'] ) : 0,
				'group_id'       => isset( $data['group_id'] ) ? absint( $data['group_id'] ) : 0,
				'visit_type'     => isset( $data['visit_type'] ) ? sanitize_text_field( $data['visit_type'] ) : 'direct',
				'source_id'      => isset( $data['source_id'] ) ? absint( $data['source_id'] ) : 0,
				'source_type'    => isset( $data['source_type'] ) ? sanitize_text_field( $data['source_type'] ) : 'direct',
			]
		);
	}

	/**
	 * Template redirect.
	 */
	public function template_redirect() {
		// Avoid if the user agent includes the 'WP-URLDetails' string.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( false !== strpos( $user_agent, 'WP-URLDetails' ) ) {
			return;
		}

		// Avoid if the referel includes the '/wp-admin/' string.
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		if ( false !== strpos( $referer, '/wp-admin/' ) ) {
			return;
		}

		$current_path = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( ! $current_path ) {
			return;
		}

		$parse_url = wp_parse_url( $current_path );
		$url_parts = explode( '/', trim( $parse_url['path'], '/' ) );

		if ( count( $url_parts ) < 2 ) {
			return;
		}

		$short_link = implode( '/', $url_parts );

		$target_link = $this->get_asset_link( $short_link );
		if ( ! $target_link ) {
			$target_link = $this->get_link( $short_link );
		}

		if ( ! $target_link ) {
			return;
		}

		$placeholder_id = 0;
		$group_id       = 0;
		$visit_type     = 'direct';
		$source_id      = 0;
		$source_type    = 'direct';

		$data = self::get_data();
		if ( ! empty( $data ) ) {
			$placeholder_id = isset( $data['placeholder_id'] ) ? absint( $data['placeholder_id'] ) : 0;
			$group_id       = isset( $data['group_id'] ) ? absint( $data['group_id'] ) : 0;
			$visit_type     = isset( $data['visit_type'] ) ? sanitize_text_field( $data['visit_type'] ) : 'direct';
			$source_id      = isset( $data['source_id'] ) ? absint( $data['source_id'] ) : 0;
			$source_type    = isset( $data['source_type'] ) ? sanitize_text_field( $data['source_type'] ) : 'direct';

			self::reset();
		}

		self::get()->track(
			[
				'action'         => 'click',
				'brand_id'       => $this->brand_id,
				'affiliate_id'   => $this->affiliate_id,
				'affiliate_type' => $this->affiliate_type,
				'placeholder_id' => $placeholder_id,
				'group_id'       => $group_id,
				'visit_type'     => $visit_type,
				'source_id'      => $source_id,
				'source_type'    => $source_type,
			]
		);

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		wp_redirect( esc_url_raw( $target_link ), 301, 'AffiLinks' );
		exit;
	}

	/**
	 * Reset the session data.
	 */
	public static function reset() {
		unset( $_SESSION['affilinks'] );
	}

	/**
	 * Track.
	 *
	 * @param array $args The arguments.
	 */
	public function track( $args = [] ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'affilinks';

		$args = array_merge(
			[
				'entry' => current_time( 'mysql' ),
			],
			$args
		);

		$wpdb->insert( $table_name, $args );
	}

	/**
	 * Get the resource link.
	 *
	 * @param string $short_link The short link.
	 */
	public function get_asset_link( $short_link = '' ) {
		global $wpdb;

		$asset_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id
			FROM {$wpdb->prefix}postmeta
			WHERE meta_key = 'aff_short_link' AND meta_value = %s",
				$short_link
			)
		);
		if ( ! $asset_id ) {
			return false;
		}

		$this->affiliate_type = 'asset';
		$this->affiliate_id   = $asset_id;
		$this->brand_id       = get_post_meta( $asset_id, 'aff_brand_id', true );

		$link_id = get_post_meta( $asset_id, 'aff_link_id', true );
		if ( ! $link_id ) {
			return false;
		}

		$terget_link = get_post_meta( $link_id, 'aff_terget_link', true );
		if ( ! $terget_link ) {
			return false;
		}

		return $terget_link;
	}

	/**
	 * Get the link.
	 *
	 * @param string $short_link The short link.
	 */
	public function get_link( $short_link = '' ) {
		global $wpdb;

		$link_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id
				FROM {$wpdb->prefix}postmeta
				WHERE meta_key = 'aff_short_link' AND meta_value = %s",
				$short_link
			)
		);
		if ( ! $link_id ) {
			return false;
		}

		$this->affiliate_type = 'link';
		$this->affiliate_id   = $link_id;
		$this->brand_id       = get_post_meta( $link_id, 'aff_brand_id', true );

		$terget_link = get_post_meta( $link_id, 'aff_terget_link', true );
		if ( ! $terget_link ) {
			return false;
		}

		return $terget_link;
	}

	/**
	 * Set the session data.
	 *
	 * @param array $data The session data.
	 */
	public static function set_data( $data = [] ) {
		$_SESSION['affilinks'] = $data;
	}

	/**
	 * Get the session data.
	 */
	public static function get_data() {
		return isset( $_SESSION['affilinks'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_SESSION['affilinks'] ) ) : [];
	}

}
