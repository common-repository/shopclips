<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.0.0
 *
 * @package    Shopclips
 * @subpackage Shopclips/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shopclips
 * @subpackage Shopclips/admin
 * @author     Verbo <support@verbo.ai>
 */
class Shopclips_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Shopclips_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Shopclips_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/shopclips-admin.css', array(), $this->version, 'all');

    }

    /*
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    ;;                                                                            ;;
    ;;                         ----==| U T I L S |==----                          ;;
    ;;                                                                            ;;
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    */

    // check is empty
    function is_empty($str)
    {
        if (is_null($str) || trim($str, '') == '') {
            return true;
        } else {
            return false;
        }
    }

    // make field not editable if already exists
    function is_enabled($order)
    {
        $shopclips_tag_id = $order->get_meta('verbo/shopclips-tag-id');
        $status = $order->get_status(); // order status

        if (!$this->is_empty($shopclips_tag_id) || $status != 'processing') {
            return " disabled";
        }
    }

    // Register custom order status
    function register_shopclips_draft_order_status()
    {
        register_post_status('wc-shopclips-draft', array('label' => esc_html__('shopclips draft', 'shopclips'), 'public' => false, 'show_in_admin_status_list' => true, 'show_in_admin_all_list' => false, 'exclude_from_search' => false, 'publicly_queryable' => false, 'label_count' => _n_noop('shopclips draft <span class="count">(%s)</span>', 'shopclips drafts <span class="count">(%s)</span>', 'shopclips')));
    }

    function shopclips_wc_add_order_statuses($order_statuses)
    {
        $order_statuses['wc-shopclips-draft'] = _x('shopclips draft', 'WooCommerce Order status', 'shopclips');

        return $order_statuses;
    }

    /*
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    ;;                                                                            ;;
    ;;          ----==| C U S T O M  M E T A  B O X E S |==----                   ;;
    ;;                                                                            ;;
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    */
    /*
     * Add new inputs to each variation
     *
     * @param string $loop
     * @param array $variation_data
     * @return print HTML
     */
    function shopclips_add_to_variations_metabox( $loop, $variation_data, $variation )
    {
        $shopclips_disabled_meta = get_post_meta($variation->ID, 'verbo/shopclips-disabled', true);
        $shopclips_enabled = empty($shopclips_disabled_meta) ? "true" : ($shopclips_disabled_meta === "true" ? "false" : "true");

        ?>
        <div class="shopclips-variation-panel">
            <h4>shopclips</h4>
            <?php woocommerce_wp_checkbox(
                array('id' => 'shopclips_variation_enabled[' . $loop . ']',
                'label' => esc_html__("&nbsp; Enabled on shopclips?", 'shopclips'), 'value' => $shopclips_enabled, 'cbvalue' => "true", 'desc_tip' => true, 'description' => esc_html__('Enable this option to show this variation in shopclips', 'shopclips'))
            ); ?>
          </div>
        <?php
    }

    function shopclips_save_product_variation( $variation_id, $i )
    {
        if (isset($_POST['shopclips_variation_enabled'][$i])) {
            if ($_POST['shopclips_variation_enabled'][$i] === 'true') {
                update_post_meta($variation_id, 'verbo/shopclips-disabled', 'false');
            } else {
                update_post_meta($variation_id, 'verbo/shopclips-disabled', 'true');
            }
        } else {
            update_post_meta($variation_id, 'verbo/shopclips-disabled', 'true');
        }
    }


    function shopclips_add_custom_meta_boxes()
    {
        // ORDER | N F C   L I N K   M E T A   B O X
        add_meta_box(
            'shopclips_order_metabox_001',          // Unique ID
            esc_html__('shopclips - link NFC tag', 'shopclips'),             // Box title
            array($this, 'shopclips_add_link_tag_id_meta_box_html'),    // Content callback, must be of type callable
            'shop_order', 'side', 'high'
        );

        // PRODUCT | E N A B L E   P R O D U C T   M E T A   B O X
        add_meta_box(
            'shopclips_product_metabox_001',          // Unique ID
            esc_html__('shopclips', 'shopclips'),             // Box title
            array($this, 'shopclips_add_product_meta_box_html'),    // Content callback, must be of type callable
            'product', 'side', 'high'
        );
    }

    // order tag link html
    function shopclips_add_link_tag_id_meta_box_html($post)
    {
        $order = wc_get_order($post->ID);
        $shopclips_tag_id = $order->get_meta('verbo/shopclips-tag-id');
        ?>

        <div class="shopclips-tag-metabox">
            <div>
                <p>
                    <label for="add_order_note"><?php esc_html_e('NFC Tag ID', 'shopclips'); ?>
                        <?php echo wc_help_tip(__('Add NFC Tag Id here and click update to prelink the tag to the customer.', 'shopclips')); ?>
                    </label>
                    <input type="text" name="shopclips_tag_id" id="add_order_note" class="input-text"
                           value="<?php esc_attr_e($shopclips_tag_id, 'shopclips'); ?>"
                        <?php echo $this->is_enabled($order); ?>
                    />
                </p>
            </div>
            <div class="shopclips-submit-box">
                <button type="submit" class="button save_order button-primary" name="save"
                        value="<?php esc_html_e('Update', 'shopclips'); ?>"
                    <?php echo $this->is_enabled($order); ?>>
                    <?php esc_html_e('Update', 'shopclips'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    // product in shopclips meta html
    function shopclips_add_product_meta_box_html($post)
    {
        $product = wc_get_product($post->ID);
        $shopclips_enabled = $product->get_meta('verbo/shopclips-enabled');
		$shopclips_product_title = $product->get_meta('verbo/shopclips-product-title');
        ?>
        <div class="shopclips-product-metabox">
            <div>
                <input type="checkbox" name="shopclips_enabled" id="shopclips_enabled"
                       value="true" <?php checked($shopclips_enabled, 'true'); ?> />
                <label for="shopclips_enabled"><?php esc_html_e('Enabled on shopclips', 'shopclips'); ?>
                    <?php echo wc_help_tip(esc_html__('Make this product available for puchase via shopclips.', 'shopclips')); ?>
                </label>
            </div>
            <p></p>
            <div class="clear"></div>

            <div>
                <label for="shopclips_product_title"><?php esc_html_e('Product Title Override', 'shopclips'); ?>
                  <?php echo wc_help_tip(__('Override the title displayed for this product in shopclips.', 'shopclips')); ?>
                </label>
                <input type="text"
                      name="shopclips_product_title"
                      id="shopclips_product_title"
                      class="input-text"
                      value="<?php esc_attr_e($shopclips_product_title, 'shopclips'); ?>"/>
            </div>
            <p></p>
            <div class="clear"></div>

            <div class="shopclips-submit-box">
                <button type="submit" class="button save_order button-primary" name="save"
                        value="<?php esc_html_e('Update', 'shopclips'); ?>">
                    <?php esc_html_e('Update', 'shopclips'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    // save post meta
    function shopclips_save_custom_meta_boxes($post_id, $post)
    {

        // check current use permissions
        $post_type = get_post_type_object($post->post_type);

        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        // Do not save the data if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // define your own post type here
        if ($post->post_type == 'shop_order') {
            if (isset($_POST['shopclips_tag_id'])) {
                update_post_meta($post_id, 'verbo/shopclips-tag-id', sanitize_text_field($_POST['shopclips_tag_id']));
            }
        }

        // define your own post type here
        if ($post->post_type == 'product') {
            if (isset($_POST['shopclips_enabled'])) {
                update_post_meta($post_id, 'verbo/shopclips-enabled', sanitize_text_field($_POST['shopclips_enabled']));
            } else {
                update_post_meta($post_id, 'verbo/shopclips-enabled', 'false');
            }

            if (isset($_POST['shopclips_product_title'])) {
                update_post_meta($post_id, 'verbo/shopclips-product-title', sanitize_text_field($_POST['shopclips_product_title']));
			}
        }

        return $post_id;
    }

    /*
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    ;;                                                                            ;;
    ;;        ----==| H I D E   V E R B O   M E T A   F I E L D S |==----         ;;
    ;;                                                                            ;;
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    */

    function protect_verbo_meta_fields($protected, $meta_key)
    {
        if (stripos($meta_key, "verbo", 0) === false) {
            return $protected;
        }

        return true;
    }

    /*
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    ;;                                                                            ;;
    ;;               ----==| O R D E R   L I S T   P A G E |==----                ;;
    ;;                                                                            ;;
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    */

    // Adding the custom column into the existing List Table Column
    function shopclips_add_columns($columns)
    {
        // find status column index
        $status_key = array_search("order_status", array_keys($columns)) + 1;

        // paste new column after status
        $res = array_slice($columns, 0, $status_key, true) + array("shopclips_order_status" => esc_html__('shopclips', 'shopclips')) + array_slice($columns, $status_key, count($columns) - 1, true);

        return $res;
    }

    // Displaying Content in the Custom Column
    function shopclips_column_cb_data($colname, $cptid)
    {
        if ($colname === 'shopclips_order_status') {
            $shopclips_order = get_post_meta($cptid, 'verbo/shopclips-order', true);
            if (!$shopclips_order) {
                return;
            }

            $shopclips_trigger = get_post_meta($cptid, 'verbo/shopclips-trigger', true);

            switch ($shopclips_trigger) {
            case 'nfc':
                ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22.7" height="22.7" overflow="visible">
                        <circle cx="11.3" cy="11.3" r="11.3" fill="#444"/>
                        <path d="M8.4 14.6c-.1 0-.2 0-.3-.1-.3-.2-.4-.5-.3-.9.4-.8.6-1.5.6-2.3 0-.8-.2-1.5-.6-2.3-.2-.3 0-.7.3-.9.4-.1.8.1.9.4.5.9.7 1.9.7 2.9s-.2 1.9-.7 2.9c-.1.1-.3.3-.6.3z"
                              fill="#fff"/>
                        <path d="M10.4 16.5c-.1 0-.2 0-.3-.1-.3-.2-.4-.6-.2-.9.8-1.3 1.2-2.4 1.2-4.2 0-1.8-.4-2.9-1.2-4.2-.3-.3-.2-.6.1-.8.3-.2.7-.1.9.2.8 1.5 1.4 2.8 1.4 4.8s-.5 3.4-1.4 4.8c-.1.3-.3.4-.5.4z"
                              fill="#fff"/>
                        <path d="M12.3 18.4c-.1 0-.2 0-.4-.1-.3-.2-.4-.6-.2-.9 1.1-1.7 1.8-3.4 1.8-6.1 0-2.7-.7-4.4-1.8-6.1-.2-.3-.1-.7.2-.9.3-.2.7-.1.9.2 1.2 1.9 2 3.8 2 6.8 0 2.6-.6 4.6-2 6.8-.1.2-.3.3-.5.3z"
                              fill="#fff"/>
                    </svg>
                <?php
                break;
            case 'sms':
                ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22.7" height="22.7" overflow="visible">
                        <circle cx="11.3" cy="11.3" r="11.3" fill="#444"/>
                        <path d="M15 6.4H7.7c-.9 0-1.7.7-1.7 1.7v4.6c0 .9.8 1.7 1.7 1.7h.6v1.5c0 .2.2.4.4.4.1 0 .2 0 .2-.1l2.2-1.8H15c.9 0 1.7-.8 1.7-1.7V8.1c0-1-.8-1.7-1.7-1.7zm-6 4.7c-.4 0-.8-.3-.8-.8s.4-.7.8-.7.8.3.8.8-.3.7-.8.7zm2.3 0c-.4 0-.8-.3-.8-.8s.3-.8.8-.8.8.3.8.8-.3.8-.8.8zm2.3 0c-.4 0-.8-.3-.8-.8s.3-.8.8-.8.8.3.8.8-.4.8-.8.8z"
                              fill="#fff"/>
                    </svg>
                <?php
                break;
            case 'qr':
                ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="22.7" height="22.7" overflow="visible">
                        <path d="M11.3 0C5.1 0 0 5.1 0 11.3s5.1 11.3 11.3 11.3 11.3-5.1 11.3-11.3S17.6 0 11.3 0zM9.2 17.4H7.6c-1.3 0-2.4-1.1-2.4-2.4v-1.5c0-.5.4-.9.9-.9s.9.4.9.9V15c0 .4.3.7.7.7h1.5c.5 0 .9.4.9.9-.1.5-.5.8-.9.8zM9.2 7H7.6c-.3 0-.6.3-.6.6v1.5c0 .5-.4.9-.9.9s-.9-.4-.9-.8V7.6c0-1.3 1.1-2.4 2.4-2.4h1.5c.5 0 .9.4.9.9s-.4.9-.8.9zm8.2 8c0 1.3-1.1 2.4-2.4 2.4h-1.5c-.5 0-.9-.4-.9-.9s.4-.9.9-.9H15c.4 0 .7-.3.7-.7v-1.5c0-.5.4-.9.9-.9s.9.4.9.9V15zm0-5.8c0 .5-.4.9-.9.9s-.9-.4-.9-.9V7.6c.1-.3-.2-.6-.6-.6h-1.5c-.5 0-.9-.4-.9-.9s.4-.9.9-.9H15c1.3 0 2.4 1.1 2.4 2.4v1.6z"
                              fill="#444"/>
                    </svg>
                <?php
                break;
            default:
                ?>
                    <svg width="22.7" height="22.7" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.3 22.6c6.24 0 11.3-5.06 11.3-11.3C22.6 5.06 17.54 0 11.3 0 5.06 0 0 5.06 0 11.3c0 6.24 5.06 11.3 11.3 11.3z" fill="#444"/><path d="M7.915 9.433L6.06 11.66a.311.311 0 00-.053.242.305.305 0 00.133.194.32.32 0 00.166.047h4.226l-.831 2.71-.222.721-.426 1.39-.035.116-.534 1.74a.203.203 0 00-.003.035v.01c0 .004 0 .017.003.024.004.008 0 .006 0 .01a.133.133 0 00.03.052l.006.006.017.015.007.005.027.015a.143.143 0 00.028.008H8.669a.137.137 0 00.029-.01l.009-.004.02-.013.008-.006a.153.153 0 00.025-.026l1.234-1.482.901-1.08.675-.805 4.37-5.24a.346.346 0 00.054-.119.272.272 0 00.005-.08.285.285 0 00-.04-.131.315.315 0 00-.134-.122.319.319 0 00-.13-.027H11.47l2.045-6.675a.206.206 0 000-.035v-.01a.138.138 0 00-.005-.023v-.009a.134.134 0 00-.011-.028.227.227 0 00-.018-.024l-.006-.006-.017-.015A.154.154 0 0013.396 3h-.009a.14.14 0 00-.023 0h-.009l-.03.003a.15.15 0 00-.028.01l-.01.005-.02.013-.008.005a.153.153 0 00-.024.026l-1.6 1.91-1.201 1.443-2.519 3.017z" fill="#fff"/></svg>
                <?php
            }
        }
    }

}
