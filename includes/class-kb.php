<?php
/**
 * KB class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */

namespace AffiLinks;

defined( 'ABSPATH' ) || exit;

/**
 * KB class.
 */
class KB {

	/**
	 * Hold links
	 *
	 * @var array
	 */
	private $links = [
		'contact'  => 'https://affiliates.studio/contact/?utm_source=Plugin&utm_medium=General&utm_campaign=WP',
		'doc-home' => 'https://affiliates.studio/doc/affilinks/?utm_source=Plugin&utm_medium=General&utm_campaign=WP',
	];

	/**
	 * Echo the link
	 *
	 * @param string $id Id of the link to get.
	 */
	public static function the( $id ) {
		echo esc_url( self::get( $id ) );
	}

	/**
	 * Return the link
	 *
	 * @param  string $id Id of the link to get.
	 * @return string
	 */
	public static function get( $id ) {
		static $manager = null;

		if ( null === $manager ) {
			$manager = new self();
			$manager->register();
		}

		return isset( $manager->links[ $id ] ) ? $manager->links[ $id ] : '#';
	}

	/**
	 * Register links
	 */
	private function register() {
		$links = $this->get_links();
		foreach ( $links as $id => $link ) {
			$this->links[ $id ] = $link;
		}
	}

	/**
	 * Get links
	 *
	 * @return array
	 */
	private function get_links() {
		return $this->links;
	}
}
