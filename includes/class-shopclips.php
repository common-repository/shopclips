<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    Shopclips
 * @subpackage Shopclips/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Shopclips
 * @subpackage Shopclips/includes
 * @author     Verbo <support@verbo.ai>
 */
class Shopclips {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Shopclips_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'SHOPCLIPS_VERSION' ) ) {
            $this->version = SHOPCLIPS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'shopclips';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Shopclips_Loader. Orchestrates the hooks of the plugin.
     * - Shopclips_i18n. Defines internationalization functionality.
     * - Shopclips_Admin. Defines all hooks for the admin area.
     * - Shopclips_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shopclips-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shopclips-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shopclips-admin.php';

        /**
         * The class responsible for defining all actions that occur in public area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shopclips-public.php';

        $this->loader = new Shopclips_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Shopclips_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Shopclips_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Shopclips_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'shopclips_add_custom_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'shopclips_save_custom_meta_boxes', 10, 2 );
        $this->loader->add_filter( 'is_protected_meta', $plugin_admin, 'protect_verbo_meta_fields', 10, 2 );
        $this->loader->add_action( "manage_shop_order_posts_custom_column", $plugin_admin, 'shopclips_column_cb_data', 10, 2 );
        $this->loader->add_filter( "manage_edit-shop_order_columns", $plugin_admin, 'shopclips_add_columns' );
        $this->loader->add_action( 'init', $plugin_admin, 'register_shopclips_draft_order_status' );
        $this->loader->add_filter( 'wc_order_statuses', $plugin_admin, 'shopclips_wc_add_order_statuses' );

        $this->loader->add_action( 'woocommerce_product_after_variable_attributes', $plugin_admin, 'shopclips_add_to_variations_metabox', 10, 3 );
        $this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'shopclips_save_product_variation', 10 , 3 );
    }

    /**
     * Register all of the hooks related to the public area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Shopclips_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'woocommerce_my_account_my_orders_query', $plugin_public, 'unset_shopclips_draft_orders_from_my_account' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Shopclips_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
