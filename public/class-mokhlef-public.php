<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://https://github.com/MakiOmar
 * @since      1.0.0
 *
 * @package    Mokhlef
 * @subpackage Mokhlef/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Mokhlef
 * @subpackage Mokhlef/public
 * @author     Mohammad Omar <mo7amed.maki@gmail.com>
 */
class Mokhlef_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mokhlef-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mokhlef-public.js', array( 'jquery' ), $this->version, false );

	}
	// Check if Variation Dynamic Pricing is enabled for the current product
	public function is_variation_dynamic_pricing_enabled( $product_id ) {
		$variation_dynamic_pricing = get_post_meta( $product_id, 'mokh_variation_dynamic_pricing', true );
		return $variation_dynamic_pricing === 'yes';
	}
	public function get_cart_items($user_id, $_product_id = false) {
		global $woocommerce;
		
		$cart = $woocommerce->cart->get_cart();
		$items = array();
		$parent_product_id = 0;
		$variation_id = 0;
		foreach ($cart as $item) {
			$product = $item['data'];
			$product_id = $product->get_id();
			$quantity = $item['quantity'];
			$price = $product->get_price();
			
			// Check if product is a variation
			if ($product->is_type('variation')) {
				if( $_product_id && $_product_id !== $product->get_parent_id()  ){
					continue;
				}
				$parent_product_id = $product->get_parent_id();
				$variation_id = $product_id;
				$product_id = $parent_product_id;
			} else {
				if( $_product_id && $_product_id !== $product_id  ){
					continue;
				}
				$parent_product_id = 0;
				$variation_id = 0;
			}
			
			$items[] = array(
				'user_id' => $user_id,
				'product_id' => $product_id,
				'parent_product_id' => $parent_product_id,
				'variation_id' => $variation_id,
				'quantity' => $quantity,
				'price' => $price
			);
		}
		return $items;
	}
	
	public function add_to_cart_validation( $passed, $product_id, $quantity ) {
		$product = wc_get_product( $product_id );
		if (!$product->get_manage_stock()) {
			return $passed;
		}
		$user_cart_items = $this->get_cart_items(get_current_user_id(), $product_id);
		//error_log($product->is_type( 'variable' ));
		if ( $product->is_type( 'variable' ) ) {
			// For variable products, we need to check the stock quantity of the selected variation.
			$variation_id = $_POST['variation_id'];
			$variation = wc_get_product( $variation_id );
			$multiplier = $variation->get_meta( '_stock_multiplier' );
			
			if( $multiplier && !empty( $multiplier ) ){
				//Adjust quantity
				$quantity = $quantity * $multiplier;
			}
			
			
			if ( ! $variation->is_in_stock() || $quantity > $variation->get_stock_quantity() ||  $quantity > $product->get_stock_quantity() ) {
				wc_add_notice( 'الاختيار الذي تم طلبه غير متاح في المخزون قم باختيار اقل من هذه الكمية.', 'error' );
				$passed = false;
			}
		} else {
			$multiplier = $product->get_meta( '_stock_multiplier' );
			
			if( $multiplier && !empty( $multiplier ) ){
				//Adjust quantity
				$quantity = $quantity * $multiplier;
			}
			
			// For simple and other product types, we can check the global stock quantity.
			if ( ! $product->is_in_stock() || $quantity > $product->get_stock_quantity() ) {
				wc_add_notice( 'الاختيار الذي تم طلبه غير متاح في المخزون قم باختيار اقل من هذه الكمية', 'error' );
				$passed = false;
			}
		}
		foreach( $user_cart_items as $user_cart_item ){
			//If is variable product
			if( 0 != $user_cart_item['parent_product_id'] ){
				$variation_id = $user_cart_item['variation_id'];
				$variation = wc_get_product( $variation_id );
				$multiplier = $variation->get_meta( '_stock_multiplier' );
				if( $multiplier && !empty( $multiplier ) ){
					//Adjust quantity
					$quantity = ($quantity + $user_cart_item['quantity']) * $multiplier;
				}
				

				if ( ! $variation->is_in_stock() || $quantity > $variation->get_stock_quantity() ||  $quantity > $product->get_stock_quantity() ) {
					wc_add_notice( 'الاختيار الذي تم طلبه غير متاح في المخزون قم باختيار اقل من هذه الكمية.', 'error' );
					$passed = false;
				}
			}else{
				$multiplier = $product->get_meta( '_stock_multiplier' );
			
				if( $multiplier && !empty( $multiplier ) ){
					//Adjust quantity
					$quantity = ($quantity + $user_cart_item['quantity']) * $multiplier;
				}

				// For simple and other product types, we can check the global stock quantity.
				if ( ! $product->is_in_stock() || $quantity > $product->get_stock_quantity() ) {
					wc_add_notice( 'الاختيار الذي تم طلبه غير متاح في المخزون قم باختيار اقل من هذه الكمية', 'error' );
					$passed = false;
				}
			}
		}
		return $passed;
	}
	public function cart_variable_has_dynamic_pricing_master($product_id){
		$cart_items = $this->get_cart_items(get_current_user_id(), $product_id);
		foreach( $cart_items as $cart_item ){
			//If is variable product
			if( 0 != $cart_item['parent_product_id'] ){
				$variation_id = $cart_item['variation_id'];
				$variation = wc_get_product( $variation_id );
				$dynamic_pricing_master = $variation->get_meta( 'mokh_variation_dynamic_pricing_master');

				if( 'yes' == $dynamic_pricing_master ){
					return true;
				}
			}
		}

		return false;
	}
	public function conditional_variation_dynamic_pricing( $cart ) {
		// Check if we are on the cart or checkout page
		if ( is_admin() ) {
			return;
		}
	
		// Loop through each cart item
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			// Check if the product is a variable product
			if ( $product->is_type( 'variation' ) ) {
				$parent_id = $product->get_parent_id();
				$dynamic_pricing_master = $product->get_meta( 'mokh_variation_dynamic_pricing_master');
				if( !$this->cart_variable_has_dynamic_pricing_master($parent_id) || 'yes' == $dynamic_pricing_master ){
					continue;
				}

				$variation_id = $cart_item['variation_id'];
				// Check if the variation has a Dynamic Price value set
				$variation_dynamic_price = get_post_meta( $variation_id, 'mokh_variation_dynamic_price', true );
				if ( $variation_dynamic_price ) {
					// Set the price of the variation to the Dynamic Price value
					$cart_item['data']->set_price( $variation_dynamic_price );
				}
				// Check if the Variation Dynamic Pricing checkbox is checked for the current product
				if ( $this->is_variation_dynamic_pricing_enabled( $parent_id ) ) {
					
				}
			}
		}
	}

}
