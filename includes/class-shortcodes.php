<?php
/**
 * Shortcodes
 *
 * @package      AffiLinks
 * @copyright    Copyright (C) 2014-2024, AffiLinks - dev@affiliates.studio
 * @link         https://affiliates.studio
 * @since        1.0.0
 */

namespace AffiLinks;

use AffiLinks\Track;
use AffiLinks\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Page class.
 */
class Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$shortcodes = [
			'aff_link'  => 'get_link_markup',
			'aff_asset' => 'get_asset_markup',
		];

		foreach ( $shortcodes as $shortcode => $callback ) {
			add_shortcode( $shortcode, [ $this, $callback ] );
		}
	}

	/**
	 * Shortcode callback to retrieve affiliate link content by ID.
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string     Affiliate link content.
	 */
	public function get_link_markup( $atts, $content ) {
		$atts = shortcode_atts(
			[
				'id'             => 0,
				'placeholder_id' => 0,
				'group_id'       => 0,
			],
			$atts
		);

		// Retrieve affiliate resource content by ID.
		$link_id = intval( $atts['id'] );
		if ( ! $link_id ) {
			return '';
		}

		$short_link = get_post_meta( $link_id, 'aff_short_link', true );
		if ( ! $short_link ) {
			return '';
		}

		$short_link = site_url() . '/' . $short_link;

		$data = [];

		// Set track data.
		if ( ! is_user_logged_in() ) {
			$data = [
				'affiliate_id'   => $link_id,
				'affiliate_type' => 'link',
				'source_id'      => get_queried_object_id(),
				'source_type'    => Track::get_source_type(),
				'visit_type'     => Track::get_visit_type(),
				'brand_id'       => get_post_meta( $link_id, 'aff_brand_id', true ),
				'placeholder_id' => $atts['placeholder_id'],
				'group_id'       => $atts['group_id'],
			];

			Track::set_data( $data );
		}

		ob_start();
		?>
		<a
			href="<?php echo esc_attr( $link ); ?>"
			class="affilinks-link affilinks-type"
			<?php
			foreach ( $data as $k => $v ) {
				echo esc_attr( $k ) . '="' . esc_attr( $v ) . '" ';
			}
			?>
		>
			<?php echo wp_kses_post( $content ); ?>
		</a>
		<?php if ( is_user_logged_in() ) { ?>
			<a href="<?php echo esc_url( get_edit_post_link( $link_id ) ); ?>">
				<?php esc_html_e( 'Edit', 'affilinks' ); ?>
			</a>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Shortcode callback to retrieve affiliate resource content by ID.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string     Affiliate resource content.
	 */
	public function get_asset_markup( $atts ) {
		$atts = shortcode_atts(
			[
				'id'             => 0,
				'placeholder_id' => 0,
				'group_id'       => 0,
			],
			$atts
		);

		// Retrieve affiliate resource content by ID.
		$asset_id = intval( $atts['id'] );
		if ( ! $asset_id ) {
			return '';
		}

		$short_link = get_post_meta( $asset_id, 'aff_short_link', true );
		if ( ! $short_link ) {
			return '';
		}

		$short_link = site_url() . '/' . $short_link;

		$link_id = get_post_meta( $asset_id, 'aff_link_id', true );
		$link    = '';
		if ( empty( $short_link ) && ! $link_id ) {
			$short_link = get_post_meta( $link_id, 'aff_short_link', true );
			if ( ! $short_link ) {
				return '';
			}

			$short_link = site_url() . '/' . $short_link;
		}

		$data = [];

		// Set track data.
		if ( ! is_user_logged_in() ) {
			$data = [
				'affiliate_id'   => $asset_id,
				'affiliate_type' => 'asset',
				'source_id'      => get_queried_object_id(),
				'source_type'    => Track::get_source_type(),
				'visit_type'     => Track::get_visit_type(),
				'brand_id'       => get_post_meta( $asset_id, 'aff_brand_id', true ),
				'placeholder_id' => $atts['placeholder_id'],
				'group_id'       => $atts['group_id'],
			];

			Track::set_data( $data );
		}

		ob_start();
		?>
		<div
			class="affilinks-asset affilinks-type"
			<?php
			foreach ( $data as $k => $v ) {
				echo esc_attr( $k ) . '="' . esc_attr( $v ) . '" ';
			}
			?>
		>
			<a href="<?php echo esc_attr( $short_link ); ?>">
				<?php echo wp_kses_post( get_post_field( 'post_content', $asset_id ) ); ?>
			</a>
			<?php if ( is_user_logged_in() ) { ?>
				<a href="<?php echo esc_url( get_edit_post_link( $asset_id ) ); ?>">
					<?php esc_html_e( 'Edit', 'affilinks' ); ?>
				</a>
			<?php } ?>
		</div>
		<?php
		return ob_get_clean();
	}

}
