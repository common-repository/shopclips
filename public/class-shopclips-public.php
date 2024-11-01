<?php

/**
 * The public-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Shopclips
 * @subpackage Shopclips/public
 */


/**
 * The public-specific functionality of the plugin.
 *
 * @package    Shopclips
 * @subpackage Shopclips/public
 * @author     Verbo <support@verbo.ai>
 */
class Shopclips_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }

    // don't show orders with Shopclips Draft status in My Account
    function unset_shopclips_draft_orders_from_my_account( $args ) {

        $statuses = wc_get_order_statuses();
        unset( $statuses['wc-shopclips-draft'] );
        $args['post_status'] = array_keys( $statuses );

        return $args;
    }

}
