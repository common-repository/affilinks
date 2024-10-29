<?php

/**
 * Initialize Freemius class.
 *
 * @since      1.0.0
 * @package    AffiLinks
 * @author     AffiLinks <dev@affiliates.studio>
 */

if ( !function_exists( 'aff_fs' ) ) {
    /**
     * Initialize Freemius.
     *
     * @return object
     */
    function aff_fs()
    {
        global  $aff_fs ;
        
        if ( !isset( $aff_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $aff_fs = fs_dynamic_init( [
                'id'             => '14988',
                'slug'           => 'affilinks',
                'type'           => 'plugin',
                'public_key'     => 'pk_09d0d6fe72b047189ade107040f6d',
                'is_premium'     => false,
                'premium_suffix' => 'Premium',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => [
                'slug' => 'edit.php?post_type=aff_brand',
            ],
                'is_live'        => true,
            ] );
        }
        
        return $aff_fs;
    }
    
    // Init Freemius.
    aff_fs();
    // Signal that SDK was initiated.
    do_action( 'aff_fs_loaded' );
}
