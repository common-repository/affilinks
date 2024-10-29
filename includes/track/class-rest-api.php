<?php
/**
 * Rest_API class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */

namespace AffiLinks\Track;

use WP_Query;
use AffiLinks\Track;

defined( 'ABSPATH' ) || exit;

/**
 * Rest_API class.
 */
class Rest_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'init' ] );
	}

	/**
	 * Initiate api actions
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		register_rest_route(
			'affilinks/v1',
			'/track/impression',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'track_impression' ],
					'args'                => [
						'short_link'     => [
							'description' => esc_html__( 'Short link', 'affilinks' ),
							'type'        => 'string',
							'default'     => '',
							'required'    => true,
						],
						'affiliate_type' => [
							'description' => esc_html__( 'Affiliate type', 'affilinks' ),
							'type'        => 'string',
							'default'     => '',
							'required'    => true,
						],
						'source_id'      => [
							'description' => esc_html__( 'Source ID', 'affilinks' ),
							'type'        => 'integer',
							'default'     => 0,
							'required'    => true,
						],
						'source_type'    => [
							'description' => esc_html__( 'Source type', 'affilinks' ),
							'type'        => 'string',
							'default'     => 'direct',
							'required'    => true,
						],
						'visit_type'     => [
							'description' => esc_html__( 'Visit type', 'affilinks' ),
							'type'        => 'string',
							'default'     => 'direct',
							'required'    => true,
						],
						'nonce'          => [
							'description' => esc_html__( 'Nonce', 'affilinks' ),
							'type'        => 'string',
							'default'     => '',
							'required'    => true,
						],
					],
					'permission_callback' => '__return_true',
				],
			]
		);

	}

	/**
	 * Track link impression
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function track_impression( $request ) {
		$nonce = $request->get_param( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, 'affilinks' ) ) {
			return rest_ensure_response( esc_html__( 'Invalid nonce', 'affilinks' ) );
		}
		$short_link     = $request->get_param( 'short_link' );
		$source_id      = $request->get_param( 'source_id' );
		$source_type    = $request->get_param( 'source_type' );
		$visit_type     = $request->get_param( 'visit_type' );
		$affiliate_type = $request->get_param( 'affiliate_type' );

		if ( ! $short_link ) {
			return rest_ensure_response( esc_html__( 'Invalid short link', 'affilinks' ) );
		}

		global $wpdb;

		$affiliate_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id
				FROM {$wpdb->prefix}postmeta
				WHERE meta_key = 'aff_short_link'
				AND meta_value = %s",
				$short_link
			)
		);

		if ( ! $affiliate_id ) {
			return rest_ensure_response( esc_html__( 'Invalid affiliate ID', 'affilinks' ) );
		}

		$brand_id       = get_post_meta( $affiliate_id, 'aff_brand_id', true );
		$placeholder_id = 0;
		$group_id       = 0;

		$data = [
			'action'         => 'impression',
			'affiliate_id'   => $affiliate_id,
			'affiliate_type' => $affiliate_type,
			'placeholder_id' => $placeholder_id,
			'brand_id'       => $brand_id,
			'group_id'       => $group_id,
			'visit_type'     => $visit_type,
			'source_id'      => $source_id,
			'source_type'    => $source_type,
		];

		Track::get()->track( $data );

		Track::get()->set_data( $data );

		return rest_ensure_response( $data );
	}

}
