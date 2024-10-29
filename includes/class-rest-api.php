<?php
/**
 * Rest_API class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */

namespace AffiLinks;

use WP_Query;

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
			'/core-stats',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'get_core_stats' ],
					'args'                => $this->get_core_stats_args(),
					'permission_callback' => [ $this, 'has_permission' ],
				],
			]
		);

		register_rest_route(
			'affilinks/v1',
			'/brands',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'get_brands' ],
					'args'                => $this->get_brands_args(),
					'permission_callback' => [ $this, 'has_permission' ],
				],
			]
		);

		register_rest_route(
			'affilinks/v1',
			'/pages',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'get_pages' ],
					'args'                => $this->get_pages_args(),
					'permission_callback' => [ $this, 'has_permission' ],
				],
			]
		);

		register_rest_route(
			'affilinks/v1',
			'/links',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'get_links' ],
					'args'                => $this->get_links_args(),
					'permission_callback' => [ $this, 'has_permission' ],
				],
			]
		);

		register_rest_route(
			'affilinks/v1',
			'/assets',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'get_assets' ],
					'args'                => $this->get_assets_args(),
					'permission_callback' => [ $this, 'has_permission' ],
				],
			]
		);

	}

	/**
	 * Check if user has permission
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get core stats args
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_core_stats_args() {
		return [
			'range' => [
				'description' => esc_html__( 'Range', 'affilinks' ),
				'type'        => 'string',
				'required'    => true,
			],
		];
	}

	/**
	 * Get get brands args
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_brands_args() {
		return [
			'range' => [
				'description' => esc_html__( 'Range', 'affilinks' ),
				'type'        => 'string',
				'required'    => true,
			],
			'limit' => [
				'description' => esc_html__( 'Limit', 'affilinks' ),
				'type'        => 'integer',
				'required'    => true,
			],
		];
	}

	/**
	 * Get get pages args
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_pages_args() {
		return [
			'range' => [
				'description' => esc_html__( 'Range', 'affilinks' ),
				'type'        => 'string',
				'required'    => true,
			],
			'limit' => [
				'description' => esc_html__( 'Limit', 'affilinks' ),
				'type'        => 'integer',
				'required'    => true,
			],
		];
	}

	/**
	 * Get get links args
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_links_args() {
		return [
			'range' => [
				'description' => esc_html__( 'Range', 'affilinks' ),
				'type'        => 'string',
				'required'    => true,
			],
			'limit' => [
				'description' => esc_html__( 'Limit', 'affilinks' ),
				'type'        => 'integer',
				'required'    => true,
			],
		];
	}

	/**
	 * Get get assets args
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_assets_args() {
		return [
			'range' => [
				'description' => esc_html__( 'Range', 'affilinks' ),
				'type'        => 'string',
				'required'    => true,
			],
			'limit' => [
				'description' => esc_html__( 'Limit', 'affilinks' ),
				'type'        => 'integer',
				'required'    => true,
			],
		];
	}

	/**
	 * Get list
	 *
	 * @param array $range Range.
	 */
	public function get_time( $range = '' ) {
		switch ( $range ) {
			case '7_days':
				$start          = strtotime( '-7 days' );
				$old_start_date = strtotime( '-14 days' );
				break;
			case '28_days':
				$start          = strtotime( '-28 days' );
				$old_start_date = strtotime( '-56 days' );
				break;
			case '3_months':
				$start          = strtotime( '-90 days' );
				$old_start_date = strtotime( '-180 days' );
				break;
			case '6_months':
				$start          = strtotime( '-180 days' );
				$old_start_date = strtotime( '-360 days' );
				break;
			case '12_months':
				$start          = strtotime( '-365 days' );
				$old_start_date = strtotime( '-730 days' );
				break;
			case '16_months':
				$start          = strtotime( '-480 days' );
				$old_start_date = strtotime( '-960 days' );
				break;
			case '30_days':
			default:
				$start          = strtotime( '-30 days' );
				$old_start_date = strtotime( '-60 days' );
				break;
		}

		$end          = strtotime( 'today' );
		$old_end_date = strtotime( '-1 days' );

		// Period.
		return [
			'end_date'       => gmdate( 'Y-m-d 23:59:59', $end ),
			'start_date'     => gmdate( 'Y-m-d 00:00:00', $start ),
			'old_end_date'   => gmdate( 'Y-m-d 23:59:59', $old_end_date ),
			'old_start_date' => gmdate( 'Y-m-d 00:00:00', $old_start_date ),
		];
	}

	/**
	 * Get summery
	 *
	 * @param string $range Range.
	 * @param string $where_clause Where.
	 */
	public function get_summery( $range = '', $where_clause = '' ) {
		$period = $this->get_time( $range );

		$end_date       = $period['end_date'];
		$start_date     = $period['start_date'];
		$old_end_date   = $period['old_end_date'];
		$old_start_date = $period['old_start_date'];

		global $wpdb;

		// Current Clicks.
		$current_clicks       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(entry) AS click_date,
					COUNT(*) AS total_clicks
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND action = 'click'
					{$where_clause}
				GROUP BY
					click_date
				ORDER BY
					click_date DESC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);  // phpcs:ignore unprepared SQL ok.
		$current_clicks_count = 0;
		foreach ( $current_clicks as $current_click ) {
			$current_clicks_count += absint( $current_click['total_clicks'] );
		}

		// Old Clicks.
		$old_clicks       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(entry) AS click_date,
					COUNT(*) AS total_clicks
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND action = 'click'
					{$where_clause}
				GROUP BY
					click_date
				ORDER BY
					click_date DESC",
				$old_start_date,
				$old_end_date
			),
			ARRAY_A
		); // phpcs:ignore unprepared SQL ok.
		$old_clicks_count = 0;
		foreach ( $old_clicks as $old_click ) {
			$old_clicks_count += absint( $old_click['total_clicks'] );
		}

		// Current Impressions.
		$current_impressions       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(entry) AS impression_date,
					COUNT(*) AS total_impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND action = 'impression'
					{$where_clause}
				GROUP BY
					impression_date
				ORDER BY
					impression_date DESC",
				$start_date,
				$end_date
			),
			ARRAY_A
		); // phpcs:ignore unprepared SQL ok.
		$current_impressions_count = 0;
		foreach ( $current_impressions as $current_impression ) {
			$current_impressions_count += absint( $current_impression['total_impressions'] );
		}

		// Old Impressions.
		$old_impressions       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(entry) AS impression_date,
					COUNT(*) AS total_impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND action = 'impression'
					{$where_clause}
				GROUP BY
					impression_date
				ORDER BY
					impression_date DESC",
				$old_start_date,
				$old_end_date
			),
			ARRAY_A
		); // phpcs:ignore unprepared SQL ok.
		$old_impressions_count = 0;
		foreach ( $old_impressions as $old_impression ) {
			$old_impressions_count += absint( $old_impression['total_impressions'] );
		}

		$ctr = $current_impressions_count ? number_format( $current_clicks_count / $current_impressions_count, 2 ) * 100 : 0;

		$current_ctr = $current_impressions_count ? number_format( $current_clicks_count / $current_impressions_count, 2 ) * 100 : 0;
		$old_ctr     = $old_impressions_count ? number_format( $old_clicks_count / $old_impressions_count, 2 ) * 100 : 0;

		$current_ctr_dates = [];
		foreach ( $current_impressions as $current_impression ) {

			$impression_date = $current_impression['impression_date'];
			$clicks          = 0;
			$impressions     = (int) $current_impression['total_impressions'];

			foreach ( $current_clicks as $current_click ) {
				if ( $impression_date === $current_click['click_date'] ) {
					$clicks = (int) $current_click['total_clicks'];
				}
			}

			$ctr = $impressions ? number_format( $clicks / $impressions, 2 ) * 100 : 0;

			$current_ctr_dates[] = [
				'ctr_date' => $impression_date,
				'ctr'      => $ctr,
			];
		}

		return [
			'clicks'      => [
				'count'     => $current_clicks_count,
				'old_count' => $old_clicks_count,
				'diff'      => $current_clicks_count - $old_clicks_count,
				'dates'     => $current_clicks,
				'old_dates' => $old_clicks,
			],
			'impressions' => [
				'count'     => $current_impressions_count,
				'old_count' => $old_impressions_count,
				'diff'      => $current_impressions_count - $old_impressions_count,
				'dates'     => $current_impressions,
				'old_dates' => $old_impressions,
			],
			'ctr'         => [
				'count'     => $current_ctr,
				'old_count' => $old_ctr,
				'diff'      => $current_ctr - $old_ctr,
				'dates'     => $current_ctr_dates,
			],
			'period'      => $period,
		];
	}

	/**
	 * Get core stats
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_core_stats( $request ) {
		$range = $request->get_param( 'range' );

		$summery = $this->get_summery( $range );

		return rest_ensure_response( $summery );
	}

	/**
	 * Get top brands
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_brands( $request ) {
		$range = $request->get_param( 'range' );
		$limit = $request->get_param( 'limit' );

		$period = $this->get_time( $range );

		$end_date       = $period['end_date'];
		$start_date     = $period['start_date'];
		$old_end_date   = $period['old_end_date'];
		$old_start_date = $period['old_start_date'];

		global $wpdb;

		$current_top_brands = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					brand_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND brand_id > 0
				GROUP BY
					brand_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$start_date,
				$end_date,
				$limit
			),
			ARRAY_A
		);

		$old_top_brands = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					brand_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND brand_id > 0
				GROUP BY
					brand_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$old_start_date,
				$old_end_date,
				$limit
			),
			ARRAY_A
		);

		$top_brands = [];
		foreach ( $current_top_brands as $current_top_brand ) {
			$id          = $current_top_brand['brand_id'];
			$clicks      = (int) $current_top_brand['clicks'];
			$impressions = (int) $current_top_brand['impressions'];
			$ctr         = $impressions ? number_format( ( $clicks / $impressions ) * 100, 2 ) : 0;

			$old_clicks      = 0;
			$old_impressions = 0;
			$old_ctr         = 0;

			foreach ( $old_top_brands as $old_top_brand ) {
				if ( $id === $old_top_brand['brand_id'] ) {
					$old_clicks      = (int) $old_top_brand['clicks'];
					$old_impressions = (int) $old_top_brand['impressions'];
					$old_ctr         = $old_impressions ? number_format( ( $old_clicks / $old_impressions ) * 100, 2 ) : 0;
				}
			}

			$top_brands[] = [
				'id'              => $id,
				'title'           => get_the_title( $id ),
				'editLink'        => get_edit_post_link( $id ),
				'clicks'          => $clicks,
				'clicksDiff'      => $clicks - $old_clicks,
				'oldClicks'       => $old_clicks,
				'impressions'     => $impressions,
				'impressionsDiff' => $impressions - $old_impressions,
				'oldImpressions'  => $old_impressions,
				'ctr'             => $ctr,
				'ctrDiff'         => number_format( $ctr - $old_ctr, 2 ),
				'oldCtr'          => $old_ctr,
			];
		}

		return rest_ensure_response(
			[
				'current_top_brands' => $current_top_brands,
				'old_top_brands'     => $old_top_brands,
				'top_brands'         => $this->get_list( $top_brands ),
				'period'             => $period,
				'summery'            => $this->get_summery( $range, ' AND brand_id > 0 ' ),
			]
		);
	}

	/**
	 * Get top pages
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_pages( $request ) {
		$range = $request->get_param( 'range' );
		$limit = $request->get_param( 'limit' );

		$period = $this->get_time( $range );

		$end_date       = $period['end_date'];
		$start_date     = $period['start_date'];
		$old_end_date   = $period['old_end_date'];
		$old_start_date = $period['old_start_date'];

		global $wpdb;

		$current_top_pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					source_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND source_id > 0
					AND source_type = 'singular'
				GROUP BY
					source_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$start_date,
				$end_date,
				$limit
			),
			ARRAY_A
		);

		$old_top_pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					source_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND source_id > 0
					AND source_type = 'singular'
				GROUP BY
					source_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$old_start_date,
				$old_end_date,
				$limit
			),
			ARRAY_A
		);

		$top_pages = [];
		foreach ( $current_top_pages as $current_top_page ) {
			$id          = $current_top_page['source_id'];
			$clicks      = (int) $current_top_page['clicks'];
			$impressions = (int) $current_top_page['impressions'];
			$ctr         = $impressions ? number_format( ( $clicks / $impressions ) * 100, 2 ) : 0;

			$old_clicks      = 0;
			$old_impressions = 0;
			$old_ctr         = 0;

			foreach ( $old_top_pages as $old_top_page ) {
				if ( $id === $old_top_page['source_id'] ) {
					$old_clicks      = (int) $old_top_page['clicks'];
					$old_impressions = (int) $old_top_page['impressions'];
					$old_ctr         = $old_impressions ? number_format( ( $old_clicks / $old_impressions ) * 100, 2 ) : 0;
				}
			}

			$top_pages[] = [
				'id'              => $id,
				'title'           => get_the_title( $id ),
				'editLink'        => get_edit_post_link( $id ),
				'clicks'          => $clicks,
				'clicksDiff'      => $clicks - $old_clicks,
				'oldClicks'       => $old_clicks,
				'impressions'     => $impressions,
				'impressionsDiff' => $impressions - $old_impressions,
				'oldImpressions'  => $old_impressions,
				'ctr'             => $ctr,
				'ctrDiff'         => number_format( $ctr - $old_ctr, 2 ),
				'oldCtr'          => $old_ctr,
			];
		}

		return rest_ensure_response(
			[
				'current_top_pages' => $current_top_pages,
				'old_top_pages'     => $old_top_pages,
				'top_pages'         => $this->get_list( $top_pages ),
				'period'            => $period,
				'summery'           => $this->get_summery( $range, " AND source_id > 0 AND source_type = 'singular' " ),
			]
		);
	}

	/**
	 * Get top links
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_links( $request ) {
		$range = $request->get_param( 'range' );
		$limit = $request->get_param( 'limit' );

		$period = $this->get_time( $range );

		$end_date       = $period['end_date'];
		$start_date     = $period['start_date'];
		$old_end_date   = $period['old_end_date'];
		$old_start_date = $period['old_start_date'];

		global $wpdb;

		$current_top_links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					affiliate_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND affiliate_id > 0
					AND affiliate_type = 'link'
				GROUP BY
					affiliate_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$start_date,
				$end_date,
				$limit
			),
			ARRAY_A
		);

		$old_top_links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					affiliate_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND affiliate_id > 0
					AND affiliate_type = 'link'
				GROUP BY
					affiliate_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$old_start_date,
				$old_end_date,
				$limit
			),
			ARRAY_A
		);

		$top_links = [];
		foreach ( $current_top_links as $current_top_link ) {
			$id          = $current_top_link['affiliate_id'];
			$clicks      = (int) $current_top_link['clicks'];
			$impressions = (int) $current_top_link['impressions'];
			$ctr         = $impressions ? number_format( ( $clicks / $impressions ) * 100, 2 ) : 0;

			$old_clicks      = 0;
			$old_impressions = 0;
			$old_ctr         = 0;

			foreach ( $old_top_links as $old_top_link ) {
				if ( $id === $old_top_link['affiliate_id'] ) {
					$old_clicks      = (int) $old_top_link['clicks'];
					$old_impressions = (int) $old_top_link['impressions'];
					$old_ctr         = $old_impressions ? number_format( ( $old_clicks / $old_impressions ) * 100, 2 ) : 0;
				}
			}

			$top_links[] = [
				'id'              => $id,
				'title'           => get_the_title( $id ),
				'editLink'        => get_edit_post_link( $id ),
				'clicks'          => $clicks,
				'clicksDiff'      => $clicks - $old_clicks,
				'oldClicks'       => $old_clicks,
				'impressions'     => $impressions,
				'impressionsDiff' => $impressions - $old_impressions,
				'oldImpressions'  => $old_impressions,
				'ctr'             => $ctr,
				'ctrDiff'         => number_format( $ctr - $old_ctr, 2 ),
				'oldCtr'          => $old_ctr,
			];
		}

		return rest_ensure_response(
			[
				'current_top_links' => $current_top_links,
				'old_top_links'     => $old_top_links,
				'top_links'         => $this->get_list( $top_links ),
				'period'            => $period,
				'summery'           => $this->get_summery( $range, " AND affiliate_id > 0 AND affiliate_type = 'link' " ),
			]
		);
	}

	/**
	 * Get top assets
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_assets( $request ) {
		$range = $request->get_param( 'range' );
		$limit = $request->get_param( 'limit' );

		$period = $this->get_time( $range );

		$end_date       = $period['end_date'];
		$start_date     = $period['start_date'];
		$old_end_date   = $period['old_end_date'];
		$old_start_date = $period['old_start_date'];

		global $wpdb;

		$current_top_assets = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					affiliate_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND affiliate_id > 0
					AND affiliate_type = 'asset'
				GROUP BY
					affiliate_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$start_date,
				$end_date,
				$limit
			),
			ARRAY_A
		);

		$old_top_assets = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					affiliate_id,
					SUM(CASE WHEN action = 'click' THEN 1 ELSE 0 END) AS clicks,
					SUM(CASE WHEN action = 'impression' THEN 1 ELSE 0 END) AS impressions
				FROM
					{$wpdb->prefix}affilinks
				WHERE
					entry BETWEEN %s AND %s
					AND affiliate_id > 0
					AND affiliate_type = 'asset'
				GROUP BY
					affiliate_id
				ORDER BY
					clicks DESC
				LIMIT %d",
				$old_start_date,
				$old_end_date,
				$limit
			),
			ARRAY_A
		);

		$top_assets = [];
		foreach ( $current_top_assets as $current_top_asset ) {
			$id          = $current_top_asset['affiliate_id'];
			$clicks      = (int) $current_top_asset['clicks'];
			$impressions = (int) $current_top_asset['impressions'];
			$ctr         = $impressions ? number_format( ( $clicks / $impressions ) * 100, 2 ) : 0;

			$old_clicks      = 0;
			$old_impressions = 0;
			$old_ctr         = 0;

			foreach ( $old_top_assets as $old_top_asset ) {
				if ( $id === $old_top_asset['affiliate_id'] ) {
					$old_clicks      = (int) $old_top_asset['clicks'];
					$old_impressions = (int) $old_top_asset['impressions'];
					$old_ctr         = $old_impressions ? number_format( ( $old_clicks / $old_impressions ) * 100, 2 ) : 0;
				}
			}

			$top_assets[] = [
				'id'              => $id,
				'title'           => get_the_title( $id ),
				'editLink'        => get_edit_post_link( $id ),
				'clicks'          => $clicks,
				'clicksDiff'      => $clicks - $old_clicks,
				'oldClicks'       => $old_clicks,
				'impressions'     => $impressions,
				'impressionsDiff' => $impressions - $old_impressions,
				'oldImpressions'  => $old_impressions,
				'ctr'             => $ctr,
				'ctrDiff'         => number_format( $ctr - $old_ctr, 2 ),
				'oldCtr'          => $old_ctr,
			];
		}

		return rest_ensure_response(
			[
				'current_top_assets' => $current_top_assets,
				'old_top_assets'     => $old_top_assets,
				'top_assets'         => $this->get_list( $top_assets ),
				'period'             => $period,
				'summery'            => $this->get_summery( $range, " AND affiliate_id > 0 AND affiliate_type = 'asset' " ),
			]
		);
	}

	/**
	 * Get list
	 *
	 * @param array $data Data.
	 */
	public function get_list( $data = [] ) {

		// Clicks.
		$clicks_data = array_filter(
			$data,
			function ( $item ) {
				return $item['clicks'] > 0;
			}
		);
		usort(
			$clicks_data,
			function ( $a, $b ) {
				return $b['clicks'] - $a['clicks'];
			}
		);

		// Impressions.
		$impressions_data = array_filter(
			$data,
			function ( $item ) {
				return $item['impressions'] > 0;
			}
		);
		usort(
			$impressions_data,
			function ( $a, $b ) {
				return $b['impressions'] - $a['impressions'];
			}
		);

		// Initialize an array to keep track of seen IDs.
		$seen_ids = [];

		// Combine most-clicked items and most impressions items without duplicates.
		$new_data = [];

		foreach ( $clicks_data as $click ) {
			if ( ! in_array( $click['id'], $seen_ids, true ) ) {
				$new_data[] = $click;
				$seen_ids[] = $click['id'];
			}
		}

		foreach ( $impressions_data as $impression ) {
			if ( ! in_array( $impression['id'], $seen_ids, true ) ) {
				$new_data[] = $impression;
				$seen_ids[] = $impression['id'];
			}
		}

		return $new_data;
	}

}
