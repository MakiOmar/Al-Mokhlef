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

	public static $dynamic_claculations = false;
	public static $dynamic_prices       = false;

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
		wp_enqueue_style('cairo-font', 'https://fonts.googleapis.com/css?family=Cairo&display=swap');

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
	function disable_distributions_loop_extra_options($passed, $product_id){
		if ( !is_singular() && has_term( 'distributions' , 'product_cat', $product_id, 'slug' ) ) {
			$passed = false;
		}
		return $passed;
	}
	function ajax_add_to_cart_action_cb() {

		$product_id = apply_filters('ql_woocommerce_add_to_cart_product_id', absint($_POST['product_id']));

		$quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);

		$variation_id = absint($_POST['variation_id']);

		$passed_validation = apply_filters('ql_woocommerce_add_to_cart_validation', true, $product_id, $quantity);

		$product_status = get_post_status($product_id);

		if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) { 

			wc_add_to_cart_message(array($product_id => $quantity), true);

			WC_AJAX :: get_refreshed_fragments();
	
		}else{
			$data = array( 
					'error' => true,
					'product_url' => get_permalink($product_id));
				wp_send_json($data);
		}
		die();
	}
	public function ajax_add_to_cart_script(){?>
	
		<script>
			jQuery(document).ready(function($) {
				
				$('body').on('click', '.single_add_to_cart_button' ,function(e){ 
					e.preventDefault();
					$thisbutton = $(this),
					$form = $thisbutton.closest('form.cart'),
					product_qty = $form.find('input[name=quantity]').val() || 1,
					product_id = $form.find('input[name=product_id]').val() || 0,
					variation_id = $form.find('input[name=variation_id]').val() || 0;
					var data = {
							action: 'ql_woocommerce_ajax_add_to_cart',
							product_id: product_id,
							product_sku: '',
							quantity: product_qty,
							variation_id: variation_id,
						};
					$.ajax({
							type: 'post',
							url: wc_add_to_cart_params.ajax_url,
							data: data,
							beforeSend: function (response) {
								$thisbutton.removeClass('added').addClass('loading');
							},
							complete: function (response) {
								$thisbutton.addClass('added').removeClass('loading');
							}, 
							success: function (response) { 
								if (response.error && response.product_ur) {
									window.location.replace(response.product_url);
									return;
								} else { 
									$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisbutton]);
								} 
							}, 
						}); 
					 }); 
			});
		</script>
	<?php

	}
	public function selected_variation_price_script(){
		add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
			// Remove the "View Cart" link from the AJAX response fragments
			unset($fragments['.added_to_cart']);
	
			return $fragments;
		});
			
		if( !function_exists('get_woocommerce_currency_symbol') ){
			return;
		}
		$currency_symbol = get_woocommerce_currency_symbol();
		?>
		<script>
		
			jQuery(document).ready(function($) {
				
			  // Listen for variation select field changes
			  $('body').on('change', '.variations select', function() {
				var variationData = $(this).closest('.product').find('.variations_form').data('product_variations'); // Get the product variations data
				
				var selectedVariation = getSelectedVariation(variationData, $(this)); // Get the selected variation based on the selected attributes
				
				if (selectedVariation) {
					var priceHtml = selectedVariation.price_html; // Get the price HTML from the selected variation
					var priceElement = $(this).closest('.product').find('.product-details .price'); // Select the price element
					console.log(priceElement.length);
					
					if (priceElement.length > 0) {
						var currencySymbol = '<?php echo $currency_symbol ?>';
						priceElement.each(function(){
							if( $(this).find('del').length > 0 ){
							
								$(this).find('del bdi').html( selectedVariation.display_regular_price + '&nbsp;<span class="woocommerce-Price-currencySymbol">'+ currencySymbol +'</span>' );
								$(this).find('ins bdi').html( selectedVariation.display_price + '&nbsp;<span class="woocommerce-Price-currencySymbol">'+ currencySymbol +'</span>' );
							}else{
								
								//priceElement.append(selectedVariation.display_price);
								$(this).find('bdi').html( selectedVariation.display_price + '&nbsp;<span class="woocommerce-Price-currencySymbol">'+ currencySymbol +'</span>' );
							}
						});
						
					}
				}
			  });
				
			  // Get the selected variation based on the selected attributes
			  function getSelectedVariation(variationData, selectField) {
				var selectedAttributes = {};
	
				// Collect selected attribute values
				selectField.closest('.variations').find('select').each(function() {
				  var attributeSlug = $(this).data('attribute_name');
				  var attributeValue = $(this).val();
	
				  if (attributeSlug && attributeValue) {
					selectedAttributes[attributeSlug] = attributeValue;
				  }
				});
				
				// Find matching variation based on selected attributes
				for (var i = 0; i < variationData.length; i++) {
				  var variation = variationData[i];
	
				  if (variationsMatch(variation.attributes, selectedAttributes)) {
					  
					return variation;
				  }
				}
	
				return null; // No matching variation found
			  }
	
			  // Check if two sets of attributes match
			  function variationsMatch(attributes1, attributes2) {
				for (var attribute in attributes1) {
				  if (!attributes2.hasOwnProperty(attribute) || attributes1[attribute] !== attributes2[attribute]) {
					return false;
				  }
				}
	
				return true;
			  }
			  
			  $('.variations select').trigger('change');
			});
		</script>
	<?php
		
	}
	public function variation_visibility_control( $children, $product ){
		if(is_admin()){
			return $children;
		}
		
		foreach($children as $index => $variation_id){
			$variation = wc_get_product($variation_id);
			
			$connected_attribute = array();
			if ($variation && $variation->is_type('variation')) {
				$variation_attributes = $variation->get_variation_attributes();
				
				if (!empty($variation_attributes)) {
					$connected_attribute = $variation_attributes;
				}
			}
			
			
			$set_hidden = false;
			
			if(!empty($connected_attribute) && is_array( $connected_attribute )){
				foreach( $connected_attribute as $attribute_slug => $term_slug ){
					
					$term = get_term_by('slug', urldecode($term_slug), urldecode(str_replace('attribute_', '', $attribute_slug)) );
					
					if ($term && !is_wp_error($term)) {
	
							$is_hidden = get_term_meta($term->term_id, 'variation-visibility', true);
							
							if( $is_hidden === 'on' ){
								$set_hidden = true;
								break;
							}
					}
				}
				
			}
			$override_global_visibility = get_post_meta( $variation_id, '_v_visibility', true );
			if($override_global_visibility && $override_global_visibility === 'yes'){
				$set_hidden = false;
			}
			
			if( $set_hidden ){
				unset($children[$index]);
			}
		}
		return $children;
	}
	public function remove_variations_reset_link_from_loop( $link ){
		if( !is_singular() ){
			return '';
		}
		
		return $link;
	}
	
	public function load_more_script(){
		$category = get_queried_object();
		$category_name = $category->name;
		$category_slug = $category->slug;
		?>
		<script>
			jQuery(document).ready(function($) {
				
				$('body').on('click', '.wc-variation-is-unavailable' , function(){
					alert('عفوًا، هذا المنتج غير متوفر. يرجى اختيار مجموعة أخرى.');
				});
				$('body').on('click',"input.tmcp-radio",function (e) {
					var current = this.id;
					$("span.tm-epo-reset-radio").click();
					$( "#" + current ).prop('checked', true);
				});
				$('#show-more-button').on('click', function(e) {
					e.preventDefault();

					var button = $(this);
					var nextPage = button.data('next-page');
					var categorySlug = '<?php echo $category_slug; ?>'; 
					var categoryName = '<?php echo $category_name; ?>'; 
					$.ajax({
						url: '<?php echo admin_url("admin-ajax.php?lang=".get_locale()); ?>',
						type: 'POST',
						data: {
							action: 'mj_load_more_products',
							category_slug: categorySlug,
							category_name: categoryName,
							page: nextPage,
						},
						beforeSend: function() {
							button.text('تحميل...');
						},
						success: function(response) {
							$('.mj-products').append(response);

							$('body').find('.variations select').trigger('change');
							button.text('عرض المزيد');

							if (response === '') {
								button.remove();
							} else {
								button.data('next-page', nextPage + 1);
							}
						},
					});
				});
			});
		</script>
	<?php

	}
	public function taxonomy_product_cat_args($paged, $category_slug){
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 8,
            'paged' => $paged,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $category_slug,
                ),
            ),
        );

		return $args;

	}
	/**
	 * Product loop item markup
	 *
	 * @param object $product Product object.
	 * @param string $categories Categories html.
	 * @return void
	 */
	public function product_loop_item_html( $product, $categories ){
		?>
		<li class="product">
			<div class="product-image">
				<a href="<?php echo get_permalink(); ?>"><?php echo $product->get_image('full'); ?></a>
			</div>

			<div class="product-details">
				<p class="category"><?php echo $categories; ?></p>
				<h3 class="product-title"><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a></h3>
				<p class="price"><?php echo $product->get_price_html(); ?></p>
				<?php
					$stk_qte = $product->get_stock_quantity();

					if ($product->is_type('variable')) {

						woocommerce_variable_add_to_cart();
					} else {
						woocommerce_template_loop_add_to_cart(array('quantity' => 1)); // Set quantity to 1 for simple products
					}
					if ($product->get_stock_status() === 'outofstock' || $stk_qte <= 0 ) {
						echo '<script>jQuery("#product-'. $product->get_id().'").find(".single_add_to_cart_button").prop("disabled", true).addClass("disabled wc-variation-is-unavailable");</script>';
					}
				?>
			</div>
		</li>
			<?php
	}

	public function products_loop_html($args, $category_name, $paged, $load_more = true){
		$products_query = new WP_Query($args);

		echo '<div class="mj-products-wrapper">';
		if ($products_query->have_posts()) {

			echo '<ul class="mj-products">';

			while ($products_query->have_posts()) {
				$products_query->the_post();
				global $product;
				$this->product_loop_item_html( $product, $category_name );
				
			}
			echo '</ul>';
			if($load_more){
				// Show More Button
				$next_page = $paged + 1;

				if ($products_query->max_num_pages > $paged) {
					echo '<button id="show-more-button" data-next-page="' . $next_page . '">عرض المزيد</button>';
				}
			}
			
		} else {
			echo '<p>No products found.</p>';
		}
		echo '</div>';

		wp_reset_postdata();
	}

	public function taxonomy_product_cat_output(){?>
		<div id="primary" class="content-area">
			<main id="main" class="site-main" role="main">

				<?php
				$category = get_queried_object();
				$category_name = $category->name;
				$category_slug = $category->slug;
				$paged = get_query_var('paged') ? get_query_var('paged') : 1;

				$args = $this->taxonomy_product_cat_args($paged, $category_slug);

				$this->products_loop_html($args, $category_name, $paged, false);
				
				?>

			</main>
		</div>
	<?php 
	}

	public function override_product_cat_template($template) {
		// Check if we are on the product category taxonomy archive
		if (is_tax('product_cat')) {
			ob_start();

			get_header();
			$this->taxonomy_product_cat_output();
			get_footer();

			// Call the taxonomy_product_cat_output method to get the custom content
			$custom_content = ob_get_clean();
	
			// Create a temporary file to hold the custom content
			$temp_file = tempnam(sys_get_temp_dir(), 'product_cat_template');
			file_put_contents($temp_file, $custom_content);
	
			// Return the path to the temporary file as the new template file
			return $temp_file;
		}
	
		return $template;
	}

	function load_more_products_action_cb() {
		$category_slug = $_POST['category_slug'];
		$category_name = $_POST['category_name'];
		$page = $_POST['page'];

		$args = $this->taxonomy_product_cat_args($page, $category_slug);
		
		$products_query = new WP_Query( $args );
	
		ob_start();
	
		if ($products_query->have_posts()) {
			while ($products_query->have_posts()) {
				$products_query->the_post();
	
				global $product;
	
				$this->product_loop_item_html( $product, $category_name );
			}
		}
	
		wp_reset_postdata();
	
		$output = ob_get_clean();
		$output = str_replace('Add to cart', 'إضافة إلى السلة' ,$output);
		$output = str_replace('Choose an option', 'تحديد أحد الخيارات' ,$output);
		echo $output;
	
		die();
	}

	public function inline_styles(){?>

		<style>
			.added_to_cart.wc-forward{
				display:none
			}
			
			<?php if(!is_singular()) {?>
			.mj-products .variations th.label, .mj-products .quantity{
				display:none!important;
			}
			
			.mj-products table:not(.has-background) tbody td {
				background-color: transparent !important;
			}
			<?php }?>

			.mj-products-wrapper{
				text-align: center;
			}
			.mj-products-wrapper .product-title{
				height: 70px;
				display: flex;
				justify-content: center;
				align-items: center;
			}
			.mj-products{
				display:flex;
				flex-wrap: wrap;
				align-items: flex-start;
				list-style-type:none!important;
				padding:0;
				max-width: 85%;
				margin: auto;
				
			}

			.mj-products li{
				width: 25%;
				margin-left: -5px;
				margin-right: -5px;
				padding: 20px;
				text-align: center;
				
			}
			.mj-products li:first-child{
				margin-right:0;
			}
			.mj-products li .add_to_cart_button{
				margin: 10px 0;
				border-radius: 10px;
			}
			#show-more-button{
				background-color: #f1ae43;
				color: #000;
				border-radius: 5px;
				margin: 20px 0;
			}
			@media screen and (min-width:480px) and (max-width:960px) {
				.mj-products li{
					width: 33.333%;
					
				}
			}
			@media screen and (max-width:479px){
				.mj-products li{
					width: 50%;
					padding: 10px;
					
				}
			}
		
			body, p, h1,h2,h3, h4, h5, h6, li, button, input, select{
				font-family: "Cairo";
			}
			select, .select-resize-ghost, .select2-container .select2-choice, .select2-container .select2-selection {
				-webkit-box-shadow: inset 0 -1.4em 1em 0 rgba(0,0,0,0.02);
				box-shadow: inset 0 -1.4em 1em 0 rgba(0,0,0,0.02);
				background-color: #fff;
				-webkit-appearance: none;
				-moz-appearance: none;
				background-image: url("data:image/svg+xml;charset=utf8, %3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-chevron-down'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
				background-position: left .45em top 50%;
				background-repeat: no-repeat;
				padding-left: 1.4em;
				background-size: auto 16px;
				border-radius: 0;
				border:none;
				outline:none;
				display: block;
			}
			input[type='email'], input[type='date'], input[type='search'], input[type='number'], input[type='text'], input[type='tel'], input[type='url'], input[type='password'], textarea, select, .select-resize-ghost, .select2-container .select2-choice, .select2-container .select2-selection {
				-webkit-box-sizing: border-box;
				box-sizing: border-box;
				border: 1px solid #ddd;
				padding: 0 .75em;
				height: 2.507em;
				font-size: .97em;
				border-radius: 0;
				max-width: 100%;
				width: 100%;
				vertical-align: middle;
				background-color: #fff;
				color: #333;
				-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
				box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
				-webkit-transition: color .3s, border .3s, background .3s, opacity .3s;
				-o-transition: color .3s, border .3s, background .3s, opacity .3s;
				transition: color .3s, border .3s, background .3s, opacity .3s;
			}
			.after-price {
				font-size: 80%;
				color: red;
			}
			.stk_qte {
				font-weight: 700;
				font-size: 0.8em;
				margin-top: 7px;
				padding: 5px 0;
				color: white;
				border-radius: 10px;
			}
			.stk_qte.low {
				background: red;
			}
			.stk_qte.mid {
				background: orange;
			}
			.stk_qte.high {
				background: #7a9c59;
			}
		</style>

	<?php 
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
		$masg = 'الاختيار الذي تم طلبه غير متاح في المخزون قم باختيار اقل من هذه الكمية.';
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
			if( $user_cart_items && is_array( $user_cart_items ) && !empty( $user_cart_items ) ){
				foreach( $user_cart_items as $user_cart_item ){
					$pre_variation_id = $user_cart_item['variation_id'];
					$pre_variation = wc_get_product( $pre_variation_id );
					$pre_multiplier = $pre_variation->get_meta( '_stock_multiplier' );
					if( $pre_multiplier && !empty( $pre_multiplier ) ){	
						$quantity +=( $user_cart_item['quantity'] *  $pre_multiplier);
					}
				}
				
			}
		
			if ( ! $variation->is_in_stock() || $quantity > $variation->get_stock_quantity() ||  $quantity > $product->get_stock_quantity() ) {
				wc_add_notice( $masg , 'error' );
				$passed = false;
			}
		} else {
			$multiplier = $product->get_meta( '_stock_multiplier' );
			
			if( $multiplier && !empty( $multiplier ) ){	
				//Adjust quantity
				$quantity = $quantity * $multiplier;
			}
			if( $user_cart_items && is_array( $user_cart_items ) && !empty( $user_cart_items ) ){
				foreach( $user_cart_items as $user_cart_item ){
					$pre_product_id = $user_cart_item['product_id'];
					$pre_product = wc_get_product( $pre_product_id );
					$pre_multiplier = $pre_product->get_meta( '_stock_multiplier' );

					if( $pre_multiplier && !empty( $pre_multiplier ) ){	
						$quantity +=( $user_cart_item['quantity'] *  $pre_multiplier);
					}
				}
			}
			// For simple and other product types, we can check the global stock quantity.
			if ( ! $product->is_in_stock() || $quantity > $product->get_stock_quantity() ) {
				wc_add_notice( $masg , 'error' );
				$passed = false;
			}
		}
		return $passed;
	}

	/**
	 * Check if a cart contains a variation that has dynamic pricing master is enabled, for a product. 
	 *
	 * @param int $product_id
	 * @return mixed Return the dynamic pricing master variation object if true, otherwise false.
	 */
	public function cart_variable_has_dynamic_pricing_master($product_id){
		$cart_items = $this->get_cart_items(get_current_user_id(), $product_id);
		foreach( $cart_items as $cart_item ){
			//If is variable product
			if( 0 != $cart_item['parent_product_id'] ){
				$variation_id = $cart_item['variation_id'];
				$variation = wc_get_product( $variation_id );
				$dynamic_pricing_master = $variation->get_meta( 'mokh_variation_dynamic_pricing_master');

				if( 'yes' == $dynamic_pricing_master ){
					return $variation;
				}
			}
		}

		return false;
	}

	public function db_get_variations_master( $product_id ){
		include_once( WC_ABSPATH . 'includes/abstracts/abstract-wc-product.php' );
		$variations_ids = $this->query_variations_ids( $product_id );
		$master_variation = false;
		foreach( $variations_ids as $var_id ){
			$variation = wc_get_product( $var_id );
			// Get variation dynamic master value
			$is_pricing_master = $variation->get_meta( 'mokh_variation_dynamic_pricing_master');

			//Check if this variation is set to be the dynamic master;
			if( 'yes' == $is_pricing_master ){
				$master_variation = $variation;
				break;
			}
		}

		return $master_variation;
	}
	public function get_variations_master( $product_id ){
		// Get the product object
		$product = wc_get_product( $product_id );

		
		$master = false;
		// Check if the product has variations
		if ( $product->is_type( 'variable' ) ) {

			// Get the variations
			$variations = $product->get_available_variations();
			
			// Loop through the variations
			foreach ( $variations as $variation ) {
				// Get variation dynamic master value
				$dynamic_pricing_master = $variation->get_meta( 'mokh_variation_dynamic_pricing_master');

				//Check if this variation is set to be the dynamic master;
				if( 'yes' == $dynamic_pricing_master ){
					$master = $variation;
					break;
				}
			}
		}

		return $master;
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
				$master_variation = $this->cart_variable_has_dynamic_pricing_master($parent_id);
				
				if( !$master_variation || 'yes' == $dynamic_pricing_master ){
					
					continue;
				}
				$master_variation_multiplier  = $master_variation->get_meta( '_stock_multiplier' );
				$current_variation_multiplier = $product->get_meta( '_stock_multiplier' );

				// Check if the variation has a Dynamic Price value set
				$variation_dynamic_price = get_post_meta( $cart_item['variation_id'], 'mokh_variation_dynamic_price', true );
				if ( $variation_dynamic_price ) {

					// Set the price of the variation to the Dynamic Price value
					$cart_item['data']->set_price( $variation_dynamic_price );

				}elseif( $master_variation_multiplier && $current_variation_multiplier && !empty( $current_variation_multiplier ) && !empty( $master_variation_multiplier ) ){
					
					$percent = $current_variation_multiplier / $master_variation_multiplier;
					$current_variation_price = $percent * $master_variation->get_price();
					$cart_item['data']->set_price( $current_variation_price );
					
				}


				// Check if the Variation Dynamic Pricing checkbox is checked for the current product
				if ( $this->is_variation_dynamic_pricing_enabled( $parent_id ) ) {
					
				}
			}
		}
	}

	function add_values_to_order_item_meta( $item, $cart_item_key, $values, $order ) {

		/**
		 * I Discovered that the plugin stores the offer id in a meta key of name `_fgf_gift_rule_id`.
		 * Plugin name: Free Gifts for WooCommerce.
		 * Author: FantasticPlugins.
		 * URI: https://woocommerce.com/products/free-gifts-for-woocommerce/
		 */

		$fgf_gift_product = isset( $values['fgf_gift_product'] ) ? $values['fgf_gift_product'] : array();
		//error_log(print_r( $item->get_meta('_fgf_gift_rule_id') ,true));
		if( !empty( $fgf_gift_product ) && is_array( $fgf_gift_product ) )
		{
			$offer_title = get_post_field('post_title', intval( $item->get_meta('_fgf_gift_rule_id') ));
			$item->update_meta_data( '_fgf_gift_rule_title', wp_strip_all_tags($offer_title) );
			$order->update_meta_data('_fgf_gift_rule_title', wp_strip_all_tags($offer_title) );
		}

	}

	/**
	 * Change displayed label for specific order item meta key.
	 *
	 * @param string $display_key Meta's display key.
	 * @param object $meta Meta's object.
	 *
	 * @return string Meta's display key.
	 */
	public function order_item_display_meta_key( $display_key, $meta ) {
		
		if( '_fgf_gift_rule_title' === $meta->key ){
			return 'Offer: ';
		}
		return $display_key;

	}

	public function display_order_meta($order) {
		$meta_value = $order->get_meta('_fgf_gift_rule_title');
		if ( !empty( $meta_value ) ) {
			
			echo '<p><strong>' . esc_html__( 'Offer:', 'woocommerce' ) . '</strong> ' . $meta_value . '</p>';
			
		}
	}
	/**
	 * Get results and make sure check cache first.
	 *
	 * @param string $prepared_query Mysql query.Must be prepared first.
	 * @param string $cache_key WP cache key.
	 * @param string $x Column to return. Indexed from 0.
	 * @param string $group Where to group the cache contents. Enables the same key to be used across groups.
	 * @param int    $expiry When to expire the cache contents, in seconds. Default 0 (no expiration).
	 * @return array Database query result. Array indexed from 0 by SQL result row number.
	 */
	public function get_col( $prepared_query, $cache_key, $x = 0, $group = '', $expiry = 0 ) {

		global $wpdb;

		$results = wp_cache_get( $cache_key );

		if ( false === $results ) {

			// phpcs:disable
			$results = $wpdb->get_col( $prepared_query, $x );
			// phpcs:enable

			wp_cache_set( $cache_key, $results, $group, $expiry );

		}

		return $results;
	}
	public function query_variations_ids( $product_id ){
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->prefix}posts
			WHERE post_parent = %d
			AND post_type = 'product_variation'
			",
			$product_id
		);
		

		return $this->get_col($query, 'query_variations_ids_' . $product_id);

	}
	public function frontend_dynamic_variation_price( $price, $product ) {
		if( is_admin() || !is_singular('product') ){
			return $price ;
		}
		$variation_id = $product->get_id();
		

		if( self::$dynamic_claculations && isset( self::$dynamic_prices[$variation_id] )){ 
			return self::$dynamic_prices[$variation_id] ;
		}
		
		$dynamic_pricing_master = $product->get_meta( 'mokh_variation_dynamic_pricing_master');

		if( 'yes' == $dynamic_pricing_master ){ 
			return $price ;
		}

		$cart = WC()->session->get('cart');

		if( !$cart || empty( $cart ) ){
			return $price ;
		}
		
		$parent_id = $product->get_parent_id();

		$master_variation = $this->db_get_variations_master( $parent_id );

		$cart_variations = array_column($cart, 'variation_id');

		if( $master_variation && in_array( $master_variation->get_id(), $cart_variations )){

			self::$dynamic_claculations = true;

			$master_variation_multiplier  = $master_variation->get_meta( '_stock_multiplier' );

			$current_variation_multiplier = $product->get_meta( '_stock_multiplier' );

			// Check if the variation has a Dynamic Price value set
			$variation_dynamic_price = get_post_meta( $variation_id , 'mokh_variation_dynamic_price', true );
			if ( $variation_dynamic_price ) {
				self::$dynamic_prices[$variation_id] = $variation_dynamic_price;
				// Set the price of the variation to the Dynamic Price value
				return $variation_dynamic_price ;

			}elseif( $master_variation_multiplier && $current_variation_multiplier && !empty( $current_variation_multiplier ) && !empty( $master_variation_multiplier ) ){
				
				$percent = $current_variation_multiplier / $master_variation_multiplier;
				$current_variation_price = $percent * $master_variation->get_price();
				self::$dynamic_prices[$variation_id] = $current_variation_price;
				return $current_variation_price;
				
			}
		}
		return $price;
	}
	

}
