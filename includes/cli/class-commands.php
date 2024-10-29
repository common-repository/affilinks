<?php
/**
 * Core CLI commands.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @subpackage AffiLinks\WP_CLI
 * @author     AffiLinks <dev@affiliates.studio>
 */

namespace AffiLinks\CLI;

use WP_CLI;
use WP_CLI_Command;

defined( 'ABSPATH' ) || exit;

/**
 * Commands class.
 */
class Commands extends WP_CLI_Command {

	/**
	 * Flush the cache.
	 *
	 * @param array $args Arguments passed.
	 */
	public function flush( $args ) {

		flush_rewrite_rules();

		WP_CLI::success( esc_html__( 'Cache flushed.', 'affilinks' ) );
	}
}
