<?php

/**
 * Analytics class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */
namespace AffiLinks;

use  WP_Query ;
defined( 'ABSPATH' ) || exit;
/**
 * Analytics class.
 */
class Analytics
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action( 'admin_menu', [ $this, 'add_menu' ], 15 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }
    
    /**
     * Enqueue scripts.
     *
     * @param string $hook Hook.
     */
    public function enqueue_scripts( $hook = '' )
    {
        if ( 'aff_brand_page_affilinks-analytics' !== $hook ) {
            return;
        }
        wp_enqueue_style(
            'affilinks-analytics',
            AFFILINKS_URI . 'assets/admin/css/analytics.css',
            [],
            AFFILINKS_VERSION,
            'all'
        );
        wp_enqueue_script(
            'affilinks-analytics',
            AFFILINKS_URI . 'assets/admin/js/analytics.js',
            [
            'jquery',
            'wp-element',
            'wp-hooks',
            'lodash',
            'wp-api-fetch'
        ],
            AFFILINKS_VERSION,
            true
        );
        wp_localize_script( 'affilinks-analytics', 'AffiLinksVars', [
            'uri'       => AFFILINKS_URI,
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'affilinks' ),
            'listLimit' => 5,
        ] );
    }
    
    /**
     * Add menu.
     */
    public function add_menu()
    {
        add_submenu_page(
            'edit.php?post_type=aff_brand',
            esc_html__( 'Analytics', 'affilinks' ),
            esc_html__( 'Analytics', 'affilinks' ),
            'manage_options',
            'affilinks-analytics',
            [ $this, 'page_markup' ]
        );
    }
    
    /**
     * Page markup.
     */
    public function page_markup()
    {
        ?>
		<div id="affilinks-analytics-root"></div>
		<?php 
    }

}