<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://github.com/MakiOmar
 * @since      1.0.0
 *
 * @package    Mokhlef
 * @subpackage Mokhlef/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mokhlef
 * @subpackage Mokhlef/admin
 * @author     Mohammad Omar <mo7amed.maki@gmail.com>
 */
class Mokhlef_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mokhlef_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mokhlef_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mokhlef-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mokhlef_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mokhlef_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mokhlef-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function add_variation_dynamic_pricing_field() {
		woocommerce_wp_checkbox( array(
			'id'            => 'mokh_variation_dynamic_pricing',
			'label'         => esc_html__( 'Variation Dynamic Pricing', 'woocommerce' ),
			'description'   => esc_html__( 'Enable dynamic pricing for this variable product', 'woocommerce' ),
			'desc_tip'      => true,
		) );
	}

	public function add_variation_dynamic_price_field( $loop, $variation_data, $variation ) {
		$variation = wc_get_product( $variation );

		woocommerce_wp_checkbox( array(
			'id'            => 'mokh_variation_dynamic_pricing_master[' . $loop . ']',
			'label'         => esc_html__( 'Dynamic Pricing master', 'woocommerce' ),
			'description'   => esc_html__( 'If cart contains this variation, other cart variations will use dynamic pricing', 'woocommerce' ),
			'desc_tip'      => true,
			'value'         => $variation->get_meta( 'mokh_variation_dynamic_pricing_master')
		) );

		woocommerce_wp_text_input( array(
			'id'            => 'mokh_variation_dynamic_price[' . $loop . ']',
			'name'          => 'mokh_variation_dynamic_price[' . $loop . ']',
			'label'         => esc_html__( 'Dynamic Price', 'woocommerce' ),
			'desc_tip'      => true,
			'description'   => esc_html__( 'Enter the dynamic price for this variation', 'woocommerce' ),
			'value'         => $variation->get_meta( 'mokh_variation_dynamic_price')
			
		) );
	}
	public function save_variable_dynamic_pricing_field( $product_id ) {
		$variation_dynamic_pricing = isset( $_POST['mokh_variation_dynamic_pricing'] ) ? 'yes' : 'no';
		update_post_meta( $product_id, 'mokh_variation_dynamic_pricing', $variation_dynamic_pricing );
	}

	public function save_variation_dynamic_price_field( $variation_id, $i ) {
		$variation = wc_get_product( $variation_id );
		if ( ! empty( $_POST['mokh_variation_dynamic_price'] ) && ! empty( $_POST['mokh_variation_dynamic_price'][ $i ] ) ) {
			$variation->update_meta_data( 'mokh_variation_dynamic_price', absint( $_POST['mokh_variation_dynamic_price'][ $i ] ) );
		}

		if ( ! empty( $_POST['mokh_variation_dynamic_pricing_master'] ) && ! empty( $_POST['mokh_variation_dynamic_pricing_master'][ $i ] ) ) {
			$dynamic_pricing_master = 'yes';
		}else{
			$dynamic_pricing_master = 'no';
		}
		
		$variation->update_meta_data( 'mokh_variation_dynamic_pricing_master', $dynamic_pricing_master );
		$variation->save();
	}
	

}
