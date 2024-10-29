<?php
/**
 * Post class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */

namespace AffiLinks;

use WP_Query;
use AffiLinks\Track;

defined( 'ABSPATH' ) || exit;

/**
 * Post class.
 */
class Post {

	/**
	 * The single instance of the class.
	 *
	 * @var Post
	 */
	protected static $instance = null;

	/**
	 * Retrieve main Post instance.
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Post
	 */
	public static function get() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof Post ) ) {
			self::$instance = new Post();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue' ] );
		add_action( 'save_post_aff_brand', [ $this, 'save_brand_fields' ] );
		add_action( 'save_post_aff_link', [ $this, 'save_link_fields' ] );
		add_action( 'save_post_aff_asset', [ $this, 'save_asset_fields' ] );
		add_filter( 'manage_aff_link_posts_columns', [ $this, 'add_link_columns' ] );
		add_action( 'manage_aff_link_posts_custom_column', [ $this, 'add_link_columns_markup' ], 10, 2 );
		add_filter( 'manage_aff_asset_posts_columns', [ $this, 'add_asset_columns' ] );
		add_action( 'manage_aff_asset_posts_custom_column', [ $this, 'add_asset_columns_markup' ], 10, 2 );
	}

	/**
	 * Add link columns markup.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function add_asset_columns_markup( $column, $post_id ) {
		if ( 'link' === $column ) {
			$link = self::get_link( $post_id );
			echo '<input type="text" value="' . esc_attr( $link ) . '" style="width: 100%;" />';
		}

		if ( 'shortcode' === $column ) {
			$shortcode = "[aff_asset id='{$post_id}']";
			echo '<input type="text" value="' . esc_html( $shortcode ) . '" />';
		}
	}

	/**
	 * Add asset columns.
	 *
	 * @param array $columns Columns.
	 */
	public function add_asset_columns( $columns ) {
		unset( $columns['date'] );

		$columns['link']      = esc_html__( 'Link', 'affilinks' );
		$columns['shortcode'] = esc_html__( 'Shortcode', 'affilinks' );
		$columns['date']      = esc_html__( 'Date', 'affilinks' );

		return $columns;
	}

	/**
	 * Add link columns.
	 *
	 * @param array $columns Columns.
	 */
	public function add_link_columns( $columns ) {
		unset( $columns['date'] );

		$columns['link']      = esc_html__( 'Link', 'affilinks' );
		$columns['shortcode'] = esc_html__( 'Shortcode', 'affilinks' );
		$columns['date']      = esc_html__( 'Date', 'affilinks' );

		return $columns;
	}

	/**
	 * Add link columns markup.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function add_link_columns_markup( $column, $post_id ) {
		if ( 'shortcode' === $column ) {
			$shortcode = "[aff_link id='{$post_id}']Content[/aff_link]";
			echo '<input type="text" value="' . esc_attr( $shortcode ) . '" style="width: 100%;" />';
		}

		if ( 'link' === $column ) {
			$link = self::get_link( $post_id );
			echo '<input type="text" value="' . esc_attr( $link ) . '" style="width: 100%;" />';
		}
	}

	/**
	 * Register post types.
	 */
	public function register_post_type() {

		$labels = [
			'name'               => esc_html_x( 'Brands', 'post type general name', 'affilinks' ),
			'singular_name'      => esc_html_x( 'Brand', 'post type singular name', 'affilinks' ),
			'add_new'            => esc_html_x( '→ Add Brand', 'affiliate brand', 'affilinks' ),
			'add_new_item'       => esc_html__( 'Add New Affiliate Brand', 'affilinks' ),
			'edit_item'          => esc_html__( 'Edit Affiliate Brand', 'affilinks' ),
			'new_item'           => esc_html__( 'New Affiliate Brand', 'affilinks' ),
			'all_items'          => esc_html__( 'All Brands', 'affilinks' ),
			'view_item'          => esc_html__( 'View Affiliate Brand', 'affilinks' ),
			'search_items'       => esc_html__( 'Search Affiliate Brands', 'affilinks' ),
			'not_found'          => esc_html__( 'No Affiliate Brands found', 'affilinks' ),
			'not_found_in_trash' => esc_html__( 'No Affiliate Brands found in the Trash', 'affilinks' ),
			'menu_name'          => esc_html__( 'AffiLinks', 'affilinks' ),
		];

		$args = [
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-admin-links',
			'supports'            => [ 'title' ],
			'has_archive'         => false,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
		];

		register_post_type( 'aff_brand', $args );

		$labels = [
			'name'               => esc_html_x( 'Affiliate Links', 'post type general name', 'affilinks' ),
			'singular_name'      => esc_html_x( 'Affiliate Link', 'post type singular name', 'affilinks' ),
			'add_new'            => esc_html_x( 'Add New Link', 'affiliate link', 'affilinks' ),
			'add_new_item'       => esc_html__( 'Add New Affiliate Link', 'affilinks' ),
			'edit_item'          => esc_html__( 'Edit Affiliate Link', 'affilinks' ),
			'new_item'           => esc_html__( 'New Affiliate Link', 'affilinks' ),
			'all_items'          => esc_html__( 'All Links', 'affilinks' ),
			'view_item'          => esc_html__( 'View Affiliate Link', 'affilinks' ),
			'search_items'       => esc_html__( 'Search Affiliate Links', 'affilinks' ),
			'not_found'          => esc_html__( 'No affiliate links found', 'affilinks' ),
			'not_found_in_trash' => esc_html__( 'No affiliate links found in the Trash', 'affilinks' ),
		];

		$args = [
			'labels'              => $labels,
			'menu_icon'           => 'dashicons-admin-links',
			'supports'            => [ 'title' ],
			'has_archive'         => true,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
		];

		register_post_type( 'aff_link', $args );

		$labels = [
			'name'               => _x( 'Affiliate Assets', 'post type general name', 'affilinks' ),
			'singular_name'      => _x( 'Affiliate Asset', 'post type singular name', 'affilinks' ),
			'add_new'            => _x( 'Add New Asset', 'affiliate Asset', 'affilinks' ),
			'add_new_item'       => __( 'Add New Affiliate Asset', 'affilinks' ),
			'edit_item'          => __( 'Edit Affiliate Asset', 'affilinks' ),
			'new_item'           => __( 'New Affiliate Asset', 'affilinks' ),
			'all_items'          => __( 'All Affiliate Assets', 'affilinks' ),
			'view_item'          => __( 'View Affiliate Asset', 'affilinks' ),
			'search_items'       => __( 'Search Affiliate Assets', 'affilinks' ),
			'not_found'          => __( 'No affiliate Assets found', 'affilinks' ),
			'not_found_in_trash' => __( 'No affiliate Assets found in the Trash', 'affilinks' ),
			'menu_name'          => esc_html__( 'Affiliate Assets', 'affilinks' ),
		];

		$args = [
			'labels'              => $labels,
			'menu_position'       => 5,
			'supports'            => [ 'title', 'editor' ],
			'has_archive'         => true,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
		];

		register_post_type( 'aff_asset', $args );

		if ( 'yes' === get_option( 'affilinks_flush_rewrite_rules', 'no' ) ) {
			flush_rewrite_rules();
			update_option( 'affilinks_flush_rewrite_rules', 'no' );
		}

	}

	/**
	 * Admin menu.
	 */
	public function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=aff_brand',
			esc_html__( 'Links', 'affilinks' ),
			esc_html__( 'Links', 'affilinks' ),
			'manage_options',
			'edit.php?post_type=aff_link'
		);

		add_submenu_page(
			'edit.php?post_type=aff_brand',
			esc_html__( '→ Add Link', 'affilinks' ),
			esc_html__( '→ Add Link', 'affilinks' ),
			'manage_options',
			'post-new.php?post_type=aff_link'
		);

		add_submenu_page(
			'edit.php?post_type=aff_brand',
			esc_html__( 'Assets', 'affilinks' ),
			esc_html__( 'Assets', 'affilinks' ),
			'manage_options',
			'edit.php?post_type=aff_asset'
		);

		add_submenu_page(
			'edit.php?post_type=aff_brand',
			esc_html__( '→ Add Asset', 'affilinks' ),
			esc_html__( '→ Add Asset', 'affilinks' ),
			'manage_options',
			'post-new.php?post_type=aff_asset'
		);

		remove_menu_page( 'edit.php?post_type=aff_link' );
		remove_menu_page( 'edit.php?post_type=aff_asset' );
	}

	/**
	 * Admin enqueue.
	 *
	 * @param string $hook Hook.
	 */
	public function admin_enqueue( $hook = '' ) {
		$post_type = get_current_screen()->post_type;
		if ( ! in_array( $post_type, [ 'aff_brand', 'aff_link', 'aff_asset' ], true ) ) {
			return;
		}

		wp_enqueue_style( 'affilinks-metabox', AFFILINKS_URI . 'assets/admin/css/metabox.css', [], AFFILINKS_VERSION, 'all' );

		wp_enqueue_script( 'affilinks-shortlink', AFFILINKS_URI . 'assets/admin/js/shortlink.js', [ 'jquery' ], AFFILINKS_VERSION, true );
		wp_localize_script(
			'affilinks-shortlink',
			'AffiLinksShortLinkVars',
			[
				'site_url' => site_url(),
			]
		);
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'aff_brand_fields',
			esc_html__( 'Brand Details', 'affilinks' ),
			[ $this, 'render_brand_fields' ],
			'aff_brand',
			'normal',
			'default'
		);

		add_meta_box(
			'aff_link_fields',
			esc_html__( 'Link Details', 'affilinks' ),
			[ $this, 'render_link_fields' ],
			'aff_link',
			'normal',
			'default'
		);

		add_meta_box(
			'aff_asset_fields',
			esc_html__( 'Link Details', 'affilinks' ),
			[ $this, 'render_asset_fields' ],
			'aff_asset',
			'normal'
		);
	}

	/**
	 * Render Brand Fields
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_brand_fields( $post ) {
		$brand_url          = get_post_meta( $post->ID, 'aff_brand_url', true );
		$affiliate_page_url = get_post_meta( $post->ID, 'aff_affiliate_page_url', true );
		?>
		<div class="affilinks-fields">
			<div class="affilinks-field">
				<div class="affilinks-field-label"><label for="brand_url"><?php esc_html_e( 'Brand URL', 'affilinks' ); ?></label></div>
				<div class="affilinks-field-content">
					<input type="text" name="brand_url" value="<?php echo esc_attr( $brand_url ); ?>" placeholder="E.g. https://affiliates.studio/" />
				</div>
			</div>
			<div class="affilinks-field">
				<div class="affilinks-field-label"><label for="affiliate_page_url"><?php esc_html_e( 'Affiliate Page URL', 'affilinks' ); ?></label></div>
				<div class="affilinks-field-content">
					<input type="text" name="affiliate_page_url" value="<?php echo esc_attr( $affiliate_page_url ); ?>" placeholder="E.g. https://affiliates.studio/" />
				</div>
			</div>
		</div>
		<input type="hidden" name="affilinks_nonce" value="<?php echo esc_attr( wp_create_nonce( 'affiliate_brand_meta' ) ); ?>" />
		<?php
	}

	/**
	 * Save Brand Fields
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_brand_fields( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['affilinks_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['affilinks_nonce'] ) ), 'affiliate_brand_meta' ) ) {
			return;
		}

		$brand_url          = isset( $_POST['brand_url'] ) ? sanitize_text_field( $_POST['brand_url'] ) : '';
		$affiliate_page_url = isset( $_POST['affiliate_page_url'] ) ? sanitize_text_field( $_POST['affiliate_page_url'] ) : '';

		update_post_meta( $post_id, 'aff_brand_url', $brand_url );
		update_post_meta( $post_id, 'aff_affiliate_page_url', $affiliate_page_url );
	}

	/**
	 * Render affiliate link custom fields
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_link_fields( $post ) {
		$brand_id    = get_post_meta( $post->ID, 'aff_brand_id', true );
		$terget_link = get_post_meta( $post->ID, 'aff_terget_link', true );
		$short_link  = get_post_meta( $post->ID, 'aff_short_link', true );

		$link_prefix = 'go';
		$link_slug   = $post->post_name;
		if ( $short_link ) {
			$parts       = explode( '/', $short_link );
			$link_prefix = $parts[0];
			$link_slug   = $parts[1];
		} else {
			$short_link = site_url( '/' ) . $link_prefix . '/' . $link_slug;
		}

		$brand_ids = get_posts(
			[
				'post_type'      => 'aff_brand',
				'posts_per_page' => 10,
				'fields'         => 'ids',
			]
		);
		?>
		<div class="affilinks-fields">
			<div class="affilinks-field affilinks-field-affiliate-link">
				<div class="affilinks-field-label"><label for="brand_id"><?php esc_html_e( 'Affiliate Brand', 'affilinks' ); ?></label></div>
				<div class="affilinks-field-content">
					<select name="brand_id">
						<option value=''><?php esc_html_e( 'Select Brand', 'affilinks' ); ?></option>
						<?php foreach ( $brand_ids as $stored_brand_id ) : ?>
							<option value='<?php echo esc_attr( $stored_brand_id ); ?>' <?php selected( $stored_brand_id, $brand_id ); ?>>
								<?php echo esc_html( get_the_title( $stored_brand_id ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="affilinks-field affilinks-field-affiliate-link">
				<div class="affilinks-field-label"><label for="terget_link"><?php esc_html_e( 'Affiliate Target Link', 'affilinks' ); ?></label></div>
				<div class="affilinks-field-content">
					<input type="text" name="terget_link" value="<?php echo esc_attr( $terget_link ); ?>" placeholder="E.g. https://affiliates.studio/?refer=xxxxxx" />
				</div>
			</div>

			<div class="affilinks-field affilinks-field-cloaked-link">
				<div class="affilinks-field-label"><?php esc_html_e( 'Cloaked Link', 'affilinks' ); ?></div>
				<div class="affilinks-field-content">
					<div>
						<label for="link_prefix">/</label>
						<select name="link_prefix" id="link_prefix">
							<?php foreach ( self::get_link_prefixes() as $pattern ) : ?>
								<option value="<?php echo esc_attr( $pattern ); ?>" <?php selected( $pattern, $link_prefix ); ?>>
									<?php echo esc_html( $pattern ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<label for="link_slug">/</label>
						<input type="text" name="link_slug" id="link_slug" value="<?php echo esc_attr( $link_slug ); ?>" />
					</div>
					<div>
						<input type="hidden" name="short_link" id="short-link" value="<?php echo esc_url( $short_link ); ?>" />
						<span id="affiliate-link"><?php echo esc_url( $short_link ); ?></span> <button class="button button-small" id="affiliate-copy"><?php esc_html_e( 'Copy', 'affilinks' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="affilinks_nonce" value="<?php echo esc_attr( wp_create_nonce( 'affiliate_link_fields' ) ); ?>" />
		<?php
	}

	/**
	 * Save Link Fields
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_link_fields( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['affilinks_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['affilinks_nonce'] ) ), 'affiliate_link_fields' ) ) {
			return;
		}

		$brand_id    = isset( $_POST['brand_id'] ) ? sanitize_text_field( $_POST['brand_id'] ) : '';
		$terget_link = isset( $_POST['terget_link'] ) ? sanitize_text_field( $_POST['terget_link'] ) : '';
		$short_link  = isset( $_POST['short_link'] ) ? sanitize_text_field( $_POST['short_link'] ) : '';

		update_post_meta( $post_id, 'aff_brand_id', $brand_id );
		update_post_meta( $post_id, 'aff_terget_link', $terget_link );
		update_post_meta( $post_id, 'aff_short_link', $short_link );
	}

	/**
	 * Render Asset Fields
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_asset_fields( $post ) {
		$brand_id    = get_post_meta( $post->ID, 'aff_brand_id', true );
		$link_id     = get_post_meta( $post->ID, 'aff_link_id', true );
		$terget_link = get_post_meta( $post->ID, 'aff_terget_link', true );
		$short_link  = get_post_meta( $post->ID, 'aff_short_link', true );

		$link_prefix = 'go';
		$link_slug   = $post->post_name;
		if ( $short_link ) {
			$parts       = explode( '/', $short_link );
			$link_prefix = $parts[0];
			$link_slug   = $parts[1];
		} else {
			$short_link = site_url( '/' ) . $link_prefix . '/' . $link_slug;
		}

		$brand_ids = get_posts(
			[
				'post_type'      => 'aff_brand',
				'posts_per_page' => 10,
				'fields'         => 'ids',
			]
		);
		$link_ids  = get_posts(
			[
				'post_type'      => 'aff_link',
				'posts_per_page' => 10,
				'fields'         => 'ids',
			]
		);
		?>
		<div class="affilinks-fields">
			<div class="affilinks-field affilinks-field-affiliate-link">
				<div class="affilinks-field-label"><label for="brand_id"><?php esc_html_e( 'Affiliate Brand', 'affilinks' ); ?></label></div>
				<div class="affilinks-field-content">
					<select name="brand_id">
						<option value=''><?php esc_html_e( 'Select Brand', 'affilinks' ); ?></option>
						<?php foreach ( $brand_ids as $stored_brand_id ) : ?>
							<option value='<?php echo esc_attr( $stored_brand_id ); ?>' <?php selected( $stored_brand_id, $brand_id ); ?>>
								<?php echo esc_html( get_the_title( $stored_brand_id ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="affilinks-field affilinks-field-affiliate-link">
				<div class="affilinks-field-label"><label for="link_id"><?php esc_html_e( 'Affiliate Link', 'affilinks' ); ?></label></div>
				<div class="affilinks-field-content">
					<select name="link_id">
						<option value=''><?php esc_html_e( 'Select Brand', 'affilinks' ); ?></option>
						<?php foreach ( $link_ids as $stored_link_id ) : ?>
							<option value='<?php echo esc_attr( $stored_link_id ); ?>' <?php selected( $stored_link_id, $link_id ); ?>>
								<?php echo esc_html( get_the_title( $stored_link_id ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="affilinks-field affilinks-field-cloaked-link">
				<div class="affilinks-field-label"><?php esc_html_e( 'Cloaked Link', 'affilinks' ); ?></div>
				<div class="affilinks-field-content">
					<div>
						<label for="link_prefix">/</label>
						<select name="link_prefix" id="link_prefix">
							<?php foreach ( self::get_link_prefixes() as $pattern ) : ?>
								<option value="<?php echo esc_attr( $pattern ); ?>" <?php selected( $pattern, $link_prefix ); ?>>
									<?php echo esc_html( $pattern ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<label for="link_slug">/</label>
						<input type="text" name="link_slug" id="link_slug" value="<?php echo esc_attr( $link_slug ); ?>" />
					</div>
					<div>
						<input type="hidden" name="short_link" id="short-link" value="<?php echo esc_url( $short_link ); ?>" />
						<span id="affiliate-link"><?php echo esc_url( $short_link ); ?></span> <button class="button button-small" id="affiliate-copy"><?php esc_html_e( 'Copy', 'affilinks' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" name="affilinks_nonce" value="<?php echo esc_attr( wp_create_nonce( 'affiliate_asset_fields' ) ); ?>" />
		<?php
	}

	/**
	 * Save Asset Fields
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_asset_fields( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['affilinks_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['affilinks_nonce'] ) ), 'affiliate_asset_fields' ) ) {
			return;
		}

		$brand_id   = isset( $_POST['brand_id'] ) ? sanitize_text_field( $_POST['brand_id'] ) : '';
		$link_id    = isset( $_POST['link_id'] ) ? sanitize_text_field( $_POST['link_id'] ) : '';
		$short_link = isset( $_POST['short_link'] ) ? sanitize_text_field( $_POST['short_link'] ) : '';

		update_post_meta( $post_id, 'aff_brand_id', $brand_id );
		update_post_meta( $post_id, 'aff_link_id', $link_id );
		update_post_meta( $post_id, 'aff_short_link', $short_link );
	}

	/**
	 * Get Link
	 *
	 * @param int $post_id Post ID.
	 */
	public static function get_link( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return '';
		}

		$short_link = get_post_meta( $post_id, 'aff_short_link', true );

		if ( ! $short_link ) {
			return '';
		}

		return site_url( '/' ) . $short_link;
	}

	/**
	 * Get link prefixes
	 */
	public static function get_link_prefixes() {
		return [ 'go', 'referer', 'visit' ];
	}

}
