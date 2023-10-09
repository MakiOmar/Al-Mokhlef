<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://https://github.com/MakiOmar
 * @since      1.0.0
 *
 * @package    Mokhlef
 * @subpackage Mokhlef/includes
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
 * @package    Mokhlef
 * @subpackage Mokhlef/includes
 * @author     Mohammad Omar <mo7amed.maki@gmail.com>
 */
class Mokhlef {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Mokhlef_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
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
		if ( defined( 'MOKHLEF_VERSION' ) ) {
			$this->version = MOKHLEF_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'mokhlef';

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
	 * - Mokhlef_Loader. Orchestrates the hooks of the plugin.
	 * - Mokhlef_i18n. Defines internationalization functionality.
	 * - Mokhlef_Admin. Defines all hooks for the admin area.
	 * - Mokhlef_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mokhlef-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mokhlef-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-mokhlef-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-mokhlef-public.php';

		$this->loader = new Mokhlef_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Mokhlef_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Mokhlef_i18n();

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

		$plugin_admin = new Mokhlef_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'woocommerce_product_options_sku', $plugin_admin, 'add_variation_dynamic_pricing_field' );
		// Save the Variation Dynamic Pricing checkbox field value
		$this->loader->add_action( 'woocommerce_process_product_meta_variable', $plugin_admin, 'save_variable_dynamic_pricing_field', 10, 1 );

		// Add the Dynamic Price text field to the variation options
		$this->loader->add_action( 'woocommerce_variation_options_pricing', $plugin_admin, 'add_variation_dynamic_price_field', 10, 3 );
		// Save the Dynamic Price text field value
		$this->loader->add_action( 'woocommerce_admin_process_variation_object', $plugin_admin, 'save_variation_dynamic_price_field', 10, 2 );

		$this->loader->add_action( 'admin_footer', $plugin_admin, 'scripts', 10, 2 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Mokhlef_Public( $this->get_plugin_name(), $this->get_version() );

		//Actions
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_head', $plugin_public, 'inline_styles' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'load_more_script' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'selected_variation_price_script' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'ajax_add_to_cart_script' );
		$this->loader->add_action( 'wp_ajax_ql_woocommerce_ajax_add_to_cart', $plugin_public, 'ajax_add_to_cart_action_cb' );
		$this->loader->add_action( 'wp_ajax_nopriv_ql_woocommerce_ajax_add_to_cart', $plugin_public, 'ajax_add_to_cart_action_cb' );
		$this->loader->add_action( 'wp_ajax_mj_load_more_products', $plugin_public, 'load_more_products_action_cb' );
		$this->loader->add_action( 'wp_ajax_nopriv_mj_load_more_products', $plugin_public, 'load_more_products_action_cb' );

		/**
		 * Disabled for future fix
		 */
		//$this->loader->add_action( 'woocommerce_before_calculate_totals', $plugin_public, 'conditional_variation_dynamic_pricing', 10, 1 );
		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $plugin_public,'add_values_to_order_item_meta', 10, 4 );
		$this->loader->add_action('woocommerce_admin_order_data_after_billing_address', $plugin_public, 'display_order_meta');
		//Filters
		$this->loader->add_filter( 'woocommerce_order_item_display_meta_key', $plugin_public, 'order_item_display_meta_key', 99, 2 );
		$this->loader->add_filter( 'woocommerce_get_children', $plugin_public, 'variation_visibility_control', 99, 2 );
		$this->loader->add_filter( 'woocommerce_reset_variations_link', $plugin_public, 'remove_variations_reset_link_from_loop', 99, 2 );
		$this->loader->add_filter( 'template_include', $plugin_public, 'override_product_cat_template', 99, 2 );
		$this->loader->add_filter( 'ql_woocommerce_add_to_cart_validation', $plugin_public, 'disable_distributions_loop_extra_options', 99, 2 );
		/**
		 * Disabled for future fix
		 */
		//$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $plugin_public, 'add_to_cart_validation', 10, 3 );
		//$this->loader->add_filter( 'woocommerce_product_variation_get_price', $plugin_public, 'frontend_dynamic_variation_price', 10, 2 );

		/**
		 * Disabled because no need
		 */
		//$this->loader->add_filter( 'woocommerce_variable_price_html', $plugin_public, 'frontend_dynamic_variation_price', 10, 2 );
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
	 * @return    Mokhlef_Loader    Orchestrates the hooks of the plugin.
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
