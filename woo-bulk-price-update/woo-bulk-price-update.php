<?php
/**
 * Plugin Name: Bulk Price Update for Woocommerce
 * Description: WooCommerce percentage pricing by Category allows you to Change WooCommerce products Price By Category.
 * Version: 2.2.8
 * Author: TechnoCrackers
 * Author URI: https://technocrackers.com
 * WC tested up to: 8.8.2
 */
require_once(plugin_dir_path(__FILE__).'js/techno_live.php');
class woocommerce_bulk_price_update
{
	function __construct() 
	{
        $this->add_actions();
  }
	private function add_actions() 
	{
		add_action('admin_menu', array($this,'woocommerce_bulk_price_update_setup') );
		add_action('wp_ajax_techno_change_price_percentge', array($this,'techno_change_price_percentge_callback'));
		add_action('plugin_action_links_' . plugin_basename( __FILE__ ), array($this,'woocommerce_bulk_price_setting'));
		add_action('wp_ajax_techno_change_price_product_ids', array($this,'techno_change_price_product_ids_callback'));
		add_action('wp_ajax_techno_get_products', array($this,'techno_products_callback'));
		add_action( 'before_woocommerce_init', array($this,'techno_hpos_compatibility') );
	}

	function techno_hpos_compatibility(){
		if( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
				'custom_order_tables', 
				__FILE__, 
				true
			);
		}
	}

	function woocommerce_bulk_price_update_setup() 
	{
		add_submenu_page( 'edit.php?post_type=product', 'bulk-price-update-woocommerce', 'Change Price WC', 'manage_options', 'bulk-price-update-woocommerce', array($this,'woocommerce_bulk_price_update_callback_function') ); 
	}
	function woocommerce_bulk_price_setting($links) 
	{
		return array_merge(array('<a href="'.esc_url(admin_url( '/edit.php?post_type=product&page=bulk-price-update-woocommerce')).'">Settings</a>'),$links);
	}
	function techno_products_callback()
	{
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'techno_products_nonce' ) ) {
			$return = array();
			$search_results = new WP_Query( array(
					'post_type'      => 'product',
					's'              => sanitize_text_field( $_REQUEST['s'] ),
					'paged'          => sanitize_text_field( $_REQUEST['page'] ),
					'posts_per_page' => 50,
					'orderby'        => 'ID', // Order by post ID for better performance
					'order'          => 'DESC', // Use DESC order for better performance
					'fields'         => 'ids', // Only retrieve post IDs to reduce memory usage
			) );
			if( $search_results->have_posts() ) :
				while( $search_results->have_posts() ) : $search_results->the_post();	
					$return[] = array('id'=>get_the_ID(), 'text'=>get_the_title());
				endwhile;
			endif;
			echo wp_json_encode(array('results' => $return, 'count_filtered' => $search_results->found_posts, 'page' => sanitize_text_field($_REQUEST['page']), 'pagination' => array("more" => true)));
			exit();
		}
	}

	function techno_wc_bulk_price_update_pro_html() {
    $plugin_path = plugin_dir_url(__FILE__); 
    ?>
    <form method="POST">
        <div class="col-50">
            <h2><?php echo esc_html('Bulk Price Update for WooCommerce', 'your-text-domain'); ?></h2>
            <h4 class="paid_color"><?php echo esc_html('WooCommerce / Premium Features:', 'your-text-domain'); ?></h4>
            <p class="paid_color"><?php echo esc_html('01. You can update price of variable products.', 'your-text-domain'); ?></p>
            <p class="paid_color"><?php echo esc_html('02. Update product price with fixed amount/price.', 'your-text-domain'); ?></p>
            <p class="paid_color"><?php echo esc_html('03. You can update price for specific products.', 'your-text-domain'); ?></p>
            <p><label for="techno_wc_bulk_price_updatekey"><?php echo esc_html('License Key:', 'your-text-domain'); ?></label><input class="regular-text" type="text" id="techno_wc_bulk_price_update_license_key" name="techno_wc_bulk_price_update_license_key"></p>
            <p class="submit"><input type="submit" name="activate_license_techno" value="<?php echo esc_html('Activate', 'your-text-domain'); ?>" class="button button-primary"></p>
        </div>
        <div class="col-50">
            <a href="https://technocrackers.com/woo-bulk-price-update/" target="_blank"><img src="<?php echo esc_url($plugin_path . 'img/premium.png'); ?>"></a>
            <div class="content_right">
                <p><?php echo esc_html('Buy Activation Key form Here..', 'your-text-domain'); ?></p>
                <p><a href="https://technocrackers.com/woo-bulk-price-update/" target="_blank"><?php echo esc_html('Buy Now...', 'your-text-domain'); ?></a></p>
            </div>
        </div>
    </form>
    <?php
	}

	function woocommerce_bulk_price_update_callback_function() 
	{
			ini_set('memory_limit', '2048M');
			defined('WP_MEMORY_LIMIT') or define('WP_MEMORY_LIMIT', '2048M');
		
			$categories = get_terms(array(
					'taxonomy' => 'product_cat',
					'hide_empty' => true,
					'orderby' => 'name',
					'order' => 'ASC'
			));

			$plugin_path = plugin_dir_url(__FILE__);

			wp_enqueue_style('bootstrap', $plugin_path . 'css/bootstrap-3.3.2.min.css', array(), '3.3.2');
			wp_enqueue_style('multiselect', $plugin_path . 'css/bootstrap-multiselect.css', array(), '1.1.0');
			wp_enqueue_style('bulkprice-custom-css', $plugin_path . 'css/bulkprice-custom.css', array(), '1.1.0');

			wp_enqueue_script('bootstrap', $plugin_path . 'js/bootstrap-3.3.2.min.js', array(), '1.1.0', true);
			wp_enqueue_script('multiselect', $plugin_path . 'js/bootstrap-multiselect.js', array(), '1.1.0', true);
			wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js', array(), true);
			?>
			<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
			<div class="bulk-title"><h1>Bulk Price Change</h1></div>
			<div class="wrap tab_wrapper bulk-content-area">
					<div class="main-panel">
							<div id="tab_dashbord" class="techno_main_tabs active"><a href="#dashbord">Dashbord</a></div>
							<div id="tab_premium" class="techno_main_tabs"><a href="#premium">Premium</a></div>
					</div>
					<div class="boxed" id="percentage_form">
							<div class="techno_tabs tab_dashbord">
									<?php
									$lic_chk = new techno_wc_bulk_price_update_lic_class();
									if (isset($_REQUEST['deactivate_techno_wc_bulk_price_update_license'])) {
											if ($lic_chk->techno_wc_bulk_price_update_deactive()) {
													echo wp_kses_post( '<div id="message" class="updated fade"><p><strong>You license Deactivated successfully...!!!</strong></p></div>' );
											} else {
													echo wp_kses_post( '<div id="message" class="updated fade" style="border-left-color:#a00;"><p><strong>' . $lic_chk->err . '</strong></p></div>' );
											}
									}
									$lic_chk_stateus = $lic_chk->is_techno_wc_bulk_price_update_act_lic();
									if (isset($_REQUEST['activate_license_techno']) && isset($_POST['techno_wc_bulk_price_update_license_key'])) {
											$license_key = $_POST['techno_wc_bulk_price_update_license_key'];
											$lic_chk_stateus = $lic_chk->techno_wc_bulk_price_update_act_call($license_key);
									}
									?>
									<form method="post">
											<?php wp_nonce_field('update-prices'); ?>
											<table class="form-table">
													<?php if ($lic_chk_stateus) : ?>
															<tr valign="top">
																	<th scope="row">Price Change Type:<br/></th>
																	<td>
																			<input type="radio" checked value="by_percent" name="price_type_by_change" id="by_percent">
																			<label for="by_percent">Percentage</label>
																			<input type="radio" value="by_fixed" name="price_type_by_change" id="by_fixed">
																			<label for="by_fixed">Fixed</label>
																	</td>
															</tr>
															<tr valign="top">
																	<th scope="row">Amount:<br/></th>
																	<td>
																			<input type="number" name="percentage" id="percentage" value="0" step="0.01"/><br />
																			<span id="errmsg"></span>
																	</td>
															</tr>
													<?php else : ?>
															<tr valign="top">
																	<th scope="row">Percentage:<br/><small>(Enter pricing percentage)</small></th>
																	<td>
																			<input style="display:none;" type="radio" checked value="by_percent" name="price_type_by_change" id="by_percent">
																			<input type="number" name="percentage" id="percentage" value="0" />%<br />
																			<span id="errmsg"></span>
																	</td>
															</tr>
													<?php endif; ?>
													<tr>
															<?php if ($lic_chk_stateus) : ?>
																	<th>Please select between following methods:<br></th>
																	<td>
																			<input type="radio" checked value="by_categories" name="price_change_method" id="by_categories">
																			<label for="by_categories">Categories</label>
																			<input type="radio" value="by_products" name="price_change_method" id="by_products">
																			<label for="by_products">Specific Products</label>
																	</td>
															<?php else : ?>
																	<input style="display:none;" type="radio" checked value="by_categories" name="price_change_method" id="by_categories">
															<?php endif; ?>
													</tr>
													<tr id="method_by_categories" class="method_aria_tc" style="display: none;">
															<th>Please select categories<br></th>
															<td>
																	<select id="techno_product_select" name="techno_product_select[]" multiple="multiple">
																			<?php foreach ($categories as $key => $cat) : ?>
																					<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
																			<?php endforeach; ?>
																	</select>
															</td>
													</tr>
													<?php if ($lic_chk_stateus) : ?>
															<tr id="method_by_products" class="method_aria_tc" style="display: none;">
																	<th>Please select Products<br></th>
																	<td><label class="sellectall-label"><input type="checkbox" id="select-all-checkbox"> Select All</label><select multiple id="add_products" class="chosen-select"></select></td>
															</tr>
													<?php endif; ?>
													<tr>
															<th scope="row">Round Up Prices.</th>
															<td>
																	<input type="checkbox" value="price_rounds_point" name="price_rounds_point" id="price_rounds_point" class="percentge-submit"><label class="lbl_tc" for="price_rounds_point">( $5.2 => $5 or $5.9 => $6 )</label>
															</td>
													</tr>
													<tr>
															<th scope="row">Increase Prices</th>
															<td>
																	<input type="radio" checked value="increase-percentge" name="price_change_type" id="increase-percentge-submit" class="percentge-submit"><label class="lbl_tc" for="increase-percentge-submit">(Regular price and sale price)</label>
																	<input type="radio" value="increase-percentge-regular" name="price_change_type" id="increase-percentge-submit" class="percentge-submit"><label class="lbl_tc" for="increase-percentge-submit">(Regular price only)</label>
																	<input type="radio" value="increase-percentge-sale" name="price_change_type" id="increase-percentge-submit" class="percentge-submit"><label class="lbl_tc" for="increase-percentge-submit">(Sale price only)</label>
															</td>
													</tr>
													<tr>
															<th scope="row">Decrease Prices</th>
															<td>
																	<input type="radio" value="discount-percentge" name="price_change_type" id="discount-percentge-submit" class="percentge-submit"><label class="lbl_tc" for="discount-percentge-submit">(Regular price and sale price)</label>
																	<input type="radio" value="discount-percentge-regular" name="price_change_type" id="discount-percentge-submit" class="percentge-submit"><label class="lbl_tc" for="discount-percentge-submit">(Regular price only)</label>
																	<input type="radio" value="discount-percentge-sale" name="price_change_type" id="discount-percentge-submit" class="percentge-submit"><label class="lbl_tc" for="discount-percentge-submit">(Sale price only)</label>
															</td>
													</tr>
													<?php if ($lic_chk_stateus) : ?>
															<tr>
																	<th>Run as dry run?<br></th>
																	<td>
																			<input type="checkbox" value="tc_dry_run" name="tc_dry_run" id="tc_dry_run">
																			<label class="lbl_tc" for="tc_dry_run"><b>If checked, no changes will be made to the database, allowing you to check the results beforehand.</b></label>
																	</td>
															</tr>
													<?php endif; ?>
											</table>
											<p class="submit"><label class="button button-primary" id="percentge_submit" onclick="techno_chage_price();">Submit</label></p>
											<div style="display:none;" id="loader"><progress class="techno-progress" max="100" value="0"></progress></div>
											<div style="display:none;" id="update_product_results">
													<table class="widefat striped">
															<thead><tr><td>No.</td><td>Thumb</td><td>Product ID</td><td>Product Name</td><td>Product Type</td><td>Regular Price</td><td>Sale Price</td></tr></thead>
															<tbody id="update_product_results_body"></tbody>
													</table>
											</div>
									</form>
							</div>
							<div class="techno_tabs tab_premium" style="display:none;">
									<?php
									if ($lic_chk_stateus) {
											if (isset($_REQUEST['activate_license_techno'])) {
													echo '<div id="message" class="updated fade"><p><strong>You license Activated successfully...!!!</strong></p></div>';
											}
											?>
											<form method="POST">
													<div class="col-50">
															<h2><?php echo esc_html('Thank You Purchasing ...!!!', 'your-text-domain'); ?></h2>
															<h4 class="paid_color"><?php echo esc_html('Deactivate Your License:', 'your-text-domain'); ?></h4>
															<p class="submit"><input type="submit" name="deactivate_techno_wc_bulk_price_update_license" value="<?php echo esc_html('Deactivate', 'your-text-domain'); ?>" class="button button-primary"></p>
													</div>
											</form>
									<?php
									} else {
											$this->techno_wc_bulk_price_update_pro_html();
											if (!empty($lic_chk->err)) {
													echo wp_kses_post( '<div id="message" class="updated fade" style="border-left-color:#a00;"><p><strong>' . $lic_chk->err . '</strong></p></div>' );
											}
									}
									?>
							</div>
					</div>
			</div>
		<script type="text/javascript">
			var ajaxurl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
			var wp_product_update_ids = { action: 'techno_change_price_percentge'};		
			var wp_product_get_ids = { action: 'techno_change_price_product_ids'};		
			var arr = [];
		   	var opration_type='';
		   	var price_type_by_change='';
		   	var percentage='';
		   	var tc_dry_run = '';
		   	var price_rounds_point='';
			function tc_start_over() 
			{					
				jQuery('#percentge_submit').css({'opacity':0.5});
				jQuery('#percentge_submit').attr('disable',true);
				jQuery('#update_product_results_body').html('');
				jQuery('#loader').show();				
			}
			function techno_chage_price() 
			{				
				Array.prototype.chunk = function(n) {
					return (!this.length) ? [] : [this.slice(0, n)].concat(this.slice(n).chunk(n));
				};
				jQuery('.techno-progress').attr('value',0);
				if(arr.length == 0)
				{
					percentage=jQuery("#percentage").val();	
					if(percentage > 0)
					{	
						opration_type = jQuery("input[name='price_change_type']:checked").val();	
						price_type_by_change = jQuery("input[name='price_type_by_change']:checked").val();	
						price_rounds_point = (jQuery("#price_rounds_point").is(":checked")) ? 'true' : 'false';
						tc_dry_run = (jQuery("#tc_dry_run").is(":checked")) ? 'true' : 'false';
						if(jQuery("input[name='price_change_method']:checked").val()=='by_categories')
						{
							if(jQuery('#techno_product_select').val() !== null && jQuery('#techno_product_select').val().length > 0){
								tc_start_over();
								wp_product_get_ids['cat_ids'] = jQuery('#techno_product_select').val();	
								wp_product_get_ids['nonce'] = "<?php echo esc_attr( wp_create_nonce('wporg_product_ids')) ?>";			
								jQuery.post( ajaxurl, wp_product_get_ids, function(res_cat) 
								{
									arr = JSON.parse(res_cat);
									arr = arr.chunk(5);
									recur_loop();
									jQuery('.techno-progress').attr('max',arr.length);
								});
							}
							else{
								alert('Please select a Category...!!');
							}			
						}
						else{
							if(jQuery('#add_products').val() != null){
								arr = jQuery('#add_products').val();
									arr = arr.chunk(5);
								tc_start_over();
								recur_loop(); 
								jQuery('.techno-progress').attr('max',arr.length);
								return false;
							}
							else{
								alert('Please select a Product...!!');								
							}
						}
					}			
					else
					{
						alert('Please provide a Amount more-than Zero...!!');
					}
				}				
			}	
			var recur_loop = function(i) 
			{
			    var num = i || 0; 
			    if(num < arr.length) 
			    {
			        wp_product_update_ids['product_id'] = arr[num];
			        wp_product_update_ids['opration_type'] = opration_type;
			        wp_product_update_ids['price_type_by_change'] = price_type_by_change;
			        wp_product_update_ids['percentage'] = percentage;
			        wp_product_update_ids['price_rounds_point'] = price_rounds_point;
			        wp_product_update_ids['tc_dry_run'] = tc_dry_run;
			        wp_product_update_ids['tc_req_count'] = num;
			        wp_product_update_ids['nonce'] = "<?php echo esc_attr( wp_create_nonce('wporg_product_update_ids')) ?>";
				   	jQuery.post( ajaxurl, wp_product_update_ids, function(response) 
				   	{
				   		jQuery('#update_product_results').show();
				   		var count=num+1;
			        	recur_loop(num+1);
				   		jQuery('.techno-progress').attr('value',count);
				   		jQuery('#update_product_results_body').append(response);
					});  
			    }
			    else
			    {
			    	arr = [];
					jQuery('#loader').hide();
					if(tc_dry_run=='true'){
						alert('Dry Run Complete...!!');
					}
					else{
						jQuery('#techno_product_select').val('');
						jQuery("#percentage").val('');	
						jQuery('#techno_product_select').multiselect('refresh');
						if(jQuery('.chosen-select').length > 0){
							jQuery('.search-choice-close').trigger('click');
						}
						alert('Operation Complete...!!');
					}
					jQuery('#percentge_submit').css({'opacity':''});
					jQuery('#percentge_submit').removeAttr('disable');
			    }
			};
			jQuery(document).ready(function(jQuery) 
			{
				jQuery('#method_'+jQuery('input[name="price_change_method"]').val()).show();
				jQuery('input[name="price_change_method"]').change(function(e)
				{
					jQuery('.method_aria_tc').hide();
					jQuery('#method_'+jQuery(this).val()).show();
				});
				var nonce = "<?php echo esc_attr( wp_create_nonce( 'techno_products_nonce' ) ); ?>";
				jQuery("#techno_product_select").multiselect({enableClickableOptGroups: true,enableCollapsibleOptGroups: true,enableFiltering: true,includeSelectAllOption: true });
	            jQuery("select.chosen-select").select2({
			        ajax: {
					    url: ajaxurl,
					    dataType: 'json',
					    delay: 250,
					    data: function (params) {
					      	return {
					        	s: params.term,
										nonce: nonce,
					        	action: 'techno_get_products',
					        	page: params.page || 1
					      	};
					    },
					    processResults: function (data, params) {
					      	params.page = params.page || 1;
						    return {
						        results: data.results,
						        pagination: {
						            more: (params.page * 50) < data.count_filtered
						        }
						    };
					    },
					    cache: true
					},				
			        placeholder: "Select Products...",
			        width: "90%",
	  				minimumInputLength: 0,
					templateResult: formatRepo,
					templateSelection: formatRepoSelection
			    });


					// Handle Select All Checkbox Change
    jQuery("#select-all-checkbox").change(function() {
        if (this.checked) {
            // Get currently visible options
            var visibleOptions = jQuery("#add_products").data('select2').$results.find(".select2-results__option[aria-selected='false']");
						console.log("visibleOptions: ",visibleOptions);
            visibleOptions.each(function() {
                var optionData = jQuery(this).data('data');
                if (optionData) {
                    jQuery("#add_products").append(new Option(optionData.text, optionData.id, true, true));
                }
            });
            jQuery("#add_products").trigger('change');
        } else {
            // Deselect all currently visible options
						jQuery("#add_products").val(null).trigger("change");
        }
    });

    // Function to check and update the "Select All" checkbox status
    function checkSelectAll() {
        var visibleOptions = jQuery("#add_products").data('select2').$results.find(".select2-results__option");
        var allSelected = true;
        visibleOptions.each(function() {
            if (jQuery(this).attr('aria-selected') === 'false') {
                allSelected = false;
            }
        });
				jQuery("#select-all-checkbox").prop("checked", allSelected);
    }

    // Listen to select and unselect events to update "Select All" checkbox status
    jQuery("#add_products").on('select2:select select2:unselect', function() {
        checkSelectAll();
    });


			  	jQuery("#percentage").keypress(function(e) 
			  	{
					if (e.keyCode === 46 && this.value.split('.').length === 2)
					{
						return false;
					}
			   	});
			   	jQuery('div.techno_main_tabs').click(function(e){
			   		jQuery('.techno_main_tabs').removeClass('active');
			   		jQuery(this).addClass('active');
					jQuery('.techno_tabs').hide();
					jQuery('.'+this.id).show();
				});
				if(window.location.hash)
			  	{
				    var tab_active=window.location.hash.substring(1);
				    jQuery("#tab_"+tab_active).trigger('click');   
			  	}
			});			
			function formatRepo (repo) {
			  if (repo.loading) {
			    return repo.text;
			  }
			  var $container = jQuery("<div class='select2-result-repository clearfix'><div class='select2-result-repository__meta'><div class='select2-result-repository__title'>"+repo.text+"</div></div></div>");
			  return $container;
			}
			function formatRepoSelection (repo) {
			  return repo.name || repo.text;
			}
		</script><?php
	}

	function techno_change_price_product_ids_callback() {
			if (isset($_POST["cat_ids"]) && $_POST["cat_ids"] != '' && isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wporg_product_ids')) {
					$posts_array = get_posts(array(
							'fields'      => 'ids',
							'numberposts' => -1,
							'post_type'   => 'product',
							'status'      => 'publish',
							'order'       => 'ASC',
							'tax_query'   => array(
									array(
											'taxonomy' => 'product_cat',
											'field'    => 'term_id',
											'terms'    => array_map('sanitize_text_field', $_POST["cat_ids"])
									)
							)
					));
					echo wp_json_encode($posts_array);
			}
			exit();
  }
	
	function techno_change_price_percentge_callback() {
			if (isset($_POST["product_id"]) && !empty($_POST["product_id"]) && isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wporg_product_update_ids')) {
					$product_count = sanitize_text_field($_POST['tc_req_count']);
					$product_count = $product_count + 1;
					$product_count = 5 * $product_count;
					$temp_i = 4;
					$product_ids = array_map('sanitize_text_field', $_POST["product_id"]);
					foreach ($product_ids as $key => $product_id) {
						if (!empty($product_id)) {
							$res = array();
							$opration_type = sanitize_text_field(trim($_POST["opration_type"]));
							$price_type_by_change = sanitize_text_field(trim($_POST["price_type_by_change"]));
							$lic_obj = new techno_wc_bulk_price_update_lic_class();
							$percentage = trim($_POST["percentage"]);
							$price_rounds_point = sanitize_text_field(trim($_POST["price_rounds_point"]));
							$tc_dry_run = sanitize_text_field(trim($_POST["tc_dry_run"]));
							$product = wc_get_product(intval(trim($product_id)));
							$lic_state = $lic_obj->is_techno_wc_bulk_price_update_act_lic();
							$product_id = $product->get_id();
							$currency = get_woocommerce_currency_symbol();
							$thumbnail = wp_get_attachment_image($product->get_image_id(), array(50, 50));
							if (!$product->is_type('variable')) {
								$html = '<td>' . (($thumbnail) ? $thumbnail : wc_placeholder_img(array(50, 50))) . '</td>';
								$html .= '<td>' . $product_id . '</td>';
								$html .= '<td>' . $product->get_name() . '</td>';
								$html .= '<td>' . $product->get_type() . '</td>';
								$html .= '<td><table><tbody>';
							}

							if (!$product->is_type('variable')) {
                                $product_prc = get_post_meta($product->get_id(), '_price', true);
                                $sale_price = get_post_meta($product->get_id(), '_sale_price', true);
                                $regular_price = get_post_meta($product->get_id(), '_regular_price', true);
                                // Convert prices to float or null if empty
                                $sale_price = is_numeric($sale_price) ? (float) $sale_price : null; // Keep null if it's blank
                                $regular_price = is_numeric($regular_price) ? (float) $regular_price : null; // Keep null if it's blank
                            
                                $res['old_price_regular'] = $regular_price;
                                $res['old_price_sale'] = $sale_price;
                            
                                // Skip if both prices are blank or 0
                                if (($regular_price === null || $regular_price == 0) && ($sale_price === null || $sale_price == 0)) {
                                    // Do nothing
                                    return;
                                }
                            
                                // Initialize updated prices
                                $sale_product_prc = $sale_price;
                                $regular_product_prc = $regular_price;
                            
                                // Update logic when regular price exists but sale price is blank or 0
                                if ($regular_price !== null && $regular_price > 0) {
                                    if ($price_type_by_change == 'by_percent') {
                                        $regular_price_update = $regular_price * ($percentage / 100);
                                    } elseif ($price_type_by_change == 'by_fixed' && $lic_state) {
                                        $regular_price_update = (float) $percentage;
                                    }
                            
                                    if ($opration_type == "increase-percentge") {
                                        $regular_product_prc = max($regular_price + $regular_price_update, 0);
                                    } elseif ($opration_type == "discount-percentge") {
                                        $regular_product_prc = max($regular_price - $regular_price_update, 0);
                                    }
                            
                                    // Update sale price as regular price if sale price is blank
                                    if ($sale_price === null || $sale_price == 0) {
                                        $sale_product_prc = $regular_product_prc;
                                    }
                                }
                            
                                // Update logic when sale price exists but regular price is blank or 0
                                if ($sale_price !== null && $sale_price > 0) {
                                    if ($price_type_by_change == 'by_percent') {
                                        $sale_price_update = $sale_price * ($percentage / 100);
                                    } elseif ($price_type_by_change == 'by_fixed' && $lic_state) {
                                        $sale_price_update = (float) $percentage;
                                    }
                            
                                    if ($opration_type == "increase-percentge-sale") {
                                        $sale_product_prc = max($sale_price + $sale_price_update, 0);
                                    } elseif ($opration_type == "discount-percentge-sale") {
                                        $sale_product_prc = max($sale_price - $sale_price_update, 0);
                                    }
                            
                                    // If regular price is 0 or blank, set it to the sale price
                                    if ($regular_price === null || $regular_price == 0) {
                                        $regular_product_prc = $sale_product_prc;
                                    }
                                }
                            
                                // Round prices if required
                                if ($price_rounds_point == 'true') {
                                    if ($regular_price !== null && $regular_price > 0) {
                                        $regular_product_prc = round($regular_product_prc);
                                    }
                                    if ($sale_price !== null && $sale_price > 0) {
                                        $sale_product_prc = round($sale_product_prc);
                                    }
                                }
                            
                                // Always round prices to 2 decimal places for consistency
                                if ($regular_price !== null && $regular_price > 0) {
                                    $regular_product_prc = round($regular_product_prc, 2);
                                }
                                if ($sale_price !== null && $sale_price > 0) {
                                    $sale_product_prc = round($sale_product_prc, 2);
                                }
                            
                                // If dry run is false, update the prices
                                if ($tc_dry_run == 'false') {
                                    // Update regular price if it's valid
                                    if ($regular_price !== null && $regular_price > 0) {
                                        update_post_meta($product->get_id(), '_regular_price', $regular_product_prc);
                                        update_post_meta($product->get_id(), '_price', $regular_product_prc); // Regular price in '_price' if no sale price
                                    }
                                    elseif( $regular_price !== null && $regular_price == 0 ){
                                        update_post_meta($product->get_id(), '_regular_price', '');
                                    }
                            
                                    // Update sale price if it's valid
                                    if ($sale_price !== null && $sale_price > 0) {
                                        update_post_meta($product->get_id(), '_sale_price', $sale_product_prc);
                                        update_post_meta($product->get_id(), '_price', $sale_product_prc); // Sale price takes precedence
                                    }
                                    elseif( $sale_price !== null && $sale_price == 0 ){
                                            update_post_meta($child_id, '_sale_price', '');
                                        }
                                    
                                }
                            
                                // Update result array with new prices
                                $res['new_price_regular'] = ($regular_price !== null && $regular_price > 0) ? $regular_product_prc : '-';
                                $res['new_price_sale'] = ($sale_price !== null && $sale_price > 0) ? $sale_product_prc : '-';
                            
                                // Build HTML output for updated prices
                                $html .= '<tr class="'.$product_id.'"><td><strong>Old Price:</strong></td><td><code>' . ($res['old_price_regular'] !== '' ? $currency . ' ' . $res['old_price_regular'] : '-') . '</code></td></tr>';
                                $html .= '<tr><td><strong>New Price:</strong></td><td><code>' . ($res['new_price_regular'] !== '' ? $currency . ' ' . $res['new_price_regular'] : '-') . '</code></td></tr>';
                                $html .= '</tbody></table></td>';
                                $html .= '<td><table><tbody>';
                                $html .= '<tr class="'.$product_id.'"><td><strong>Old Price:</strong></td><td><code>' . ($res['old_price_sale'] !== '' ? $currency . ' ' . $res['old_price_sale'] : '-') . '</code></td></tr>';
                                $html .= '<tr><td><strong>New Price:</strong></td><td><code>' . ($res['new_price_sale'] !== '' ? $currency . ' ' . $res['new_price_sale'] : '-') . '</code></td></tr>';
                                $html .= '</tbody></table></td>';
                            }
                            elseif ($lic_state) {
                                $res['is_type'] = 'variable';
                                $var_new_price = array();
                                $variation_count = 0;
                            
                                foreach ($product->get_children() as $child_id) {
                                    $variation_res = array();
                                    $variation_count++;
                            
                                    // Fetch prices from meta
                                    $sale_price = get_post_meta($child_id, '_sale_price', true);
                                    $regular_price = get_post_meta($child_id, '_regular_price', true);

                                    // Convert prices to float or null if empty
                                    $sale_price = is_numeric($sale_price) ? (float) $sale_price : null; // Keep null if blank
                                    $regular_price = is_numeric($regular_price) ? (float) $regular_price : null; // Keep null if blank
                            
                                    // Skip if both prices are 0 or null
                                    if (($regular_price === null || $regular_price == 0) && ($sale_price === null || $sale_price == 0)) {
                                        continue; // Skip this variation, nothing to update
                                    }
                            
                                    // Initialize updated prices
                                    $sale_product_prc = $sale_price;
                                    $regular_product_prc = $regular_price;
                            
                                    // Calculate price updates
                                    if ($price_type_by_change == 'by_percent') {
                                        $sale_price_update = ($sale_price !== null && $sale_price > 0) ? $sale_price * ($percentage / 100) : 0;
                                        $regular_price_update = ($regular_price !== null && $regular_price > 0) ? $regular_price * ($percentage / 100) : 0;
                                    } elseif ($price_type_by_change == 'by_fixed' && $lic_state) {
                                        $sale_price_update = ($sale_price !== null && $sale_price > 0) ? (float) $percentage : 0;
                                        $regular_price_update = ($regular_price !== null && $regular_price > 0) ? (float) $percentage : 0;
                                    }
                            
                                    // Apply operation type for regular price (only if regular price exists)
                                    if ($regular_price !== null && $regular_price > 0) {
																			 $variation_res['old_price_regular'] = $regular_price;
                                        if ($opration_type == "increase-percentge") {
                                            $regular_product_prc = max($regular_price + $regular_price_update, 0);
                                        } elseif ($opration_type == "discount-percentge") {
                                            $regular_product_prc = max($regular_price - $regular_price_update, 0);
                                        }
                                    }
                            
                                    // Apply operation type for sale price (only if sale price exists)
                                    if ($sale_price !== null && $sale_price > 0) {
																				$variation_res['old_price_sale'] = $sale_price;
                                        if ($opration_type == "increase-percentge-sale") {
                                            $sale_product_prc = max($sale_price + $sale_price_update, 0);
                                        } elseif ($opration_type == "discount-percentge-sale") {
                                            $sale_product_prc = max($sale_price - $sale_price_update, 0);
                                        }
                                    }
                            
                                    // Rounding prices if required
                                    if ($price_rounds_point == 'true') {
                                        if ($regular_price !== null && $regular_price > 0) {
                                            $regular_product_prc = round($regular_product_prc);
                                        }
                                        if ($sale_price !== null && $sale_price > 0) {
                                            $sale_product_prc = round($sale_product_prc);
                                        }
                                    }
                            
                                    // Always round to 2 decimal places for consistency
                                    if ($regular_price !== null && $regular_price > 0) {
                                        $regular_product_prc = round($regular_product_prc, 2);
                                    }
                                    if ($sale_price !== null && $sale_price > 0) {
                                        $sale_product_prc = round($sale_product_prc, 2);
                                    }
                            
                                    // If dry run is false, update only valid prices
                                    if ($tc_dry_run == 'false') {
                                        // Update regular price if it's valid
                                        if ($regular_price !== null && $regular_price > 0) {
                                            update_post_meta($child_id, '_regular_price', $regular_product_prc);
                                            update_post_meta($child_id, '_price', $regular_product_prc); // Set regular price in '_price' if no sale price
                                            $var_new_price[] = $regular_product_prc;
                                        }
                                        elseif ($regular_price !== null && $regular_price == 0) {
                                            update_post_meta($child_id, '_regular_price', '');
                                        }
                                        // Update sale price if it's valid
                                        if ($sale_price !== null && $sale_price > 0) {
                                            update_post_meta($child_id, '_sale_price', $sale_product_prc);
                                            update_post_meta($child_id, '_price', $sale_product_prc); // Sale price takes precedence over regular price
                                            $var_new_price[] = $sale_product_prc;
                                        }
                                        elseif( $sale_price !== null && $sale_price == 0 ){
                                            update_post_meta($child_id, '_sale_price', '');
                                        }
                                    }
                            
                                    // Save updated prices in result array
                                    $variation_res['new_price_regular'] = ($regular_price !== null && $regular_price > 0) ? $regular_product_prc : '-';
                                    $variation_res['new_price_sale'] = ($sale_price !== null && $sale_price > 0) ? $sale_product_prc : '-';
																		$variation_res['child_id'] = $child_id;
                                    // Save results for this variation
                                    $res['variation_' . $variation_count] = $variation_res;
                                }
                            
                                // HTML display for the updated prices
                                $html = '<td>' . (($thumbnail) ? $thumbnail : wc_placeholder_img(array(50, 50))) . '</td>';
                                $html .= '<td>' . $product_id . '</td>';
                                $html .= '<td>' . $product->get_name() . '</td>';
                                $html .= '<td>' . $product->get_type() . '</td>';
                                $html .= '<td><table><tbody>';
                            
                                foreach ($res as $key => $value) {
                                    if ($key != 'is_type') {
                                        $html .= '<tr><td><strong>Variation ID:</strong></td><td><code>' . ($value['child_id'] !== '' ? $value['child_id'] : '-') . '</code></td></tr>';
																				$html .= '<tr class="'.$product_id.'"><td><strong>Old Price:</strong></td><td><code>' . ($value['old_price_regular'] !== '' ? $currency . ' ' . $value['old_price_regular'] : '-') . '</code></td></tr>';
                                        $html .= '<tr><td><strong>New Price:</strong></td><td><code>' . ($value['new_price_regular'] !== '' ? $currency . ' ' . $value['new_price_regular'] : '-') . '</code></td></tr>';
                                    }
                                }
                            
                                $html .= '</tbody></table></td>';
                            
                                $html .= '<td><table><tbody>';
                                foreach ($res as $key => $value) {
                                    if ($key != 'is_type') {
																				$html .= '<tr><td><strong>Variation ID:</strong></td><td><code>' . ($value['child_id'] !== '' ? $value['child_id'] : '-') . '</code></td></tr>';
                                        $html .= '<tr class="'.$product_id.'"><td><strong>Old Price:</strong></td><td><code>' . ($value['old_price_sale'] !== '' ? $currency . ' ' . $value['old_price_sale'] : '-') . '</code></td></tr>';
                                        $html .= '<tr><td><strong>New Price:</strong></td><td><code>' . ($value['new_price_sale'] !== '' ? $currency . ' ' . $value['new_price_sale'] : '-') . '</code></td></tr>';
                                    }
                                }
                                $html .= '</tbody></table></td>';
                            
                                // If dry run is disabled, update the main product price with the lowest new price from variations
                                if ($tc_dry_run == 'false' && !empty($var_new_price)) {
                                    update_post_meta($product->get_id(), '_price', min($var_new_price));
                                }
                            }


							if (sizeof($res) == 0) {
								$html = '<td>' . (($thumbnail) ? $thumbnail : wc_placeholder_img(array(50, 50))) . '</td>';
								$html .= '<td>' . $product_id . '</td>';
								$html .= '<td>' . $product->get_name() . '</td>';
								$html .= '<td>' . $product->get_type() . '</td>';
								$html .= '<td><table><tbody>';
								$html .= '<tr><td><a href="https://technocrackers.com/woo-bulk-price-update/" target="_blank">Buy Premium!</a></td></tr></tbody></table></td>';
							}

							if ($tc_dry_run == 'false') {
								$product->save();
							}

							$product_count_1 = $product_count - $temp_i;
							echo wp_kses_post('<tr><td>' . $product_count_1 . '</td>' . $html . '</tr>');
							$temp_i--;
						}
					}
			}
			exit();
    }

}
new woocommerce_bulk_price_update();?>