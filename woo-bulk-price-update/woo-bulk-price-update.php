<?php
/**
 * Plugin Name: Bulk Price Update for Woocommerce
 * Description: WooCommerce percentage pricing by Category allows you to Change WooCommerce products Price By Category.
 * Version: 2.2.9
 * Author: TechnoCrackers
 * Author URI: https://technocrackers.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC tested up to: 8.8.2
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class WBPU_BULK_PRICE_UPDATE
{
	function __construct() 
	{
        $this->wbpu_add_actions();
  }

	public static function wbpu_activate_free_version() 
	{
		$pro_plugin_slug = 'woo-bulk-price-update-pro/woo-bulk-price-update-pro.php';
		if (is_plugin_active($pro_plugin_slug)) {
				deactivate_plugins($pro_plugin_slug);
				set_transient('free_plugin_activated_notice', true, 30);
		}
	}
	private function wbpu_add_actions() 
	{
		add_action('admin_menu', array($this,'wbpu_bulk_price_update_setup') );
		add_action('wp_ajax_techno_change_price_percentge', array($this,'wbpu_change_price_percentge_callback'));
		add_action('plugin_action_links_' . plugin_basename( __FILE__ ), array($this,'wbpu_bulk_price_setting'));
		add_action('wp_ajax_techno_change_price_product_ids', array($this,'wbpu_change_price_product_ids_callback'));
		add_action('wp_ajax_techno_get_products', array($this,'wbpu_products_callback'));
		add_action( 'before_woocommerce_init', array($this,'wbpu_hpos_compatibility') );
		add_action('admin_notices', array($this, 'wbpu_show_free_plugin_activated_notice'));
	}

	function wbpu_hpos_compatibility(){
		if( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
				'custom_order_tables', 
				__FILE__, 
				true
			);
		}
	}

	function wbpu_bulk_price_update_setup() 
	{
		add_submenu_page( 'edit.php?post_type=product', 'bulk-price-update-woocommerce', 'Change Price WC', 'manage_options', 'bulk-price-update-woocommerce', array($this,'wbpu_bulk_price_update_callback_function') ); 
	}
	function wbpu_bulk_price_setting($links) 
	{
		return array_merge(array('<a href="'.esc_url(admin_url( '/edit.php?post_type=product&page=bulk-price-update-woocommerce')).'">Settings</a>', '<a href="'.esc_url('https://technocrackers.com/woo-bulk-price-update/').'" target="_blank" style="color: #8D00B1;">Buy Premium</a>' ),$links);
	}
	function wbpu_products_callback()
	{
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'techno_products_nonce' ) ) {
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

	function wbpu_bulk_price_update_pro_html() {
    $plugin_path = plugin_dir_url(__FILE__); 
    ?>
    <form method="POST">
        <div class="col-50">
            <h2><?php echo esc_html('Bulk Price Update for WooCommerce', 'wbpu-bulk-price-update'); ?></h2>
            <h4 class="paid_color"><?php echo esc_html('WooCommerce / Premium Features:', 'wbpu-bulk-price-update'); ?></h4>
            <p class="paid_color"><?php echo esc_html('01. Support for variable products.', 'wbpu-bulk-price-update'); ?></p>
            <p class="paid_color"><?php echo esc_html('02. Update product price with fixed or percentage amount/price.', 'wbpu-bulk-price-update'); ?></p>
            <p class="paid_color"><?php echo esc_html('03. You can update price for specific products.', 'wbpu-bulk-price-update'); ?></p>
						<p class="paid_color"><?php echo esc_html('04. Option for Dry Run, no changes will be made to the database, allowing you to check the results beforehand..', 'wbpu-bulk-price-update'); ?></p>
        </div>
        <div class="col-50">
            <a href="https://technocrackers.com/woo-bulk-price-update/" target="_blank"><img src="<?php echo esc_url($plugin_path . 'img/premium.png'); ?>"></a>
            <div class="content_right">
                <p><?php echo esc_html('Buy Activation Key form Here..', 'wbpu-bulk-price-update'); ?></p>
                <p><a href="https://technocrackers.com/woo-bulk-price-update/" target="_blank"><?php echo esc_html('Buy Now...', 'wbpu-bulk-price-update'); ?></a></p>
            </div>
        </div>
    </form>
    <?php
	}

	function wbpu_bulk_price_update_callback_function() 
	{
			$categories = get_terms(array(
					'taxonomy' => 'product_cat',
					'hide_empty' => true,
					'orderby' => 'name',
					'order' => 'ASC'
			));

			$plugin_path = plugin_dir_url(__FILE__);

			wp_enqueue_style('bootstrap', $plugin_path . 'css/bootstrap-3.3.2.min.css', array(), '3.3.2');
			wp_enqueue_style('multiselect', $plugin_path . 'css/bootstrap-multiselect.css', array(), '1.1.0');
			wp_enqueue_style('bulkprice-custom-css', $plugin_path . 'css/bulkprice-custom.css', array(), '1.1.1');

			wp_enqueue_script('bootstrap', $plugin_path . 'js/bootstrap-3.3.2.min.js', array(), '1.1.0', true);
			wp_enqueue_script('multiselect', $plugin_path . 'js/bootstrap-multiselect.js', array(), '1.1.0', true);
			wp_enqueue_script('select2-min-js', $plugin_path . 'js/select2.min.js', array(), '1.1.0', true);

			wp_enqueue_style('select2-min-css', $plugin_path . 'css/select2.min.css', array(), '1.1.0');

			 // Register our script just like we would enqueue it - for WordPress references
			wp_register_script( 'wbpu-main', $plugin_path . 'js/wbpu-main.js', array( 'jquery' ), false, true );

			// Create any data in PHP that we may need to use in our JS file
			$local_arr = array(
					'ajaxurl'   => admin_url( 'admin-ajax.php' ),
					'wporg_product_ids'  => wp_create_nonce( 'wporg_product_ids' ),
					'wporg_product_update_ids'  => wp_create_nonce( 'wporg_product_update_ids' ),
					'techno_products_nonce'  => wp_create_nonce( 'techno_products_nonce' )
			);

			// Assign that data to our script as an JS object
			wp_localize_script( 'wbpu-main', 'wbpu_obj', $local_arr );

			// Enqueue our script
			wp_enqueue_script( 'wbpu-main' );
			?>
			<div class="bulk-title"><h1>Bulk Price Change</h1></div>
			<div class="wrap tab_wrapper bulk-content-area">
					<div class="main-panel">
							<div id="tab_dashbord" class="techno_main_tabs active"><a href="#dashbord">Dashbord</a></div>
							<div id="tab_premium" class="techno_main_tabs"><a href="#premium">Premium</a></div>
					</div>
					<div class="boxed" id="percentage_form">
							<div class="techno_tabs tab_dashbord">
									<form method="post">
											<?php wp_nonce_field('update-prices'); ?>
											<table class="form-table">
															<tr valign="top">
																	<th scope="row">Percentage:<br/><small>(Enter pricing percentage)</small></th>
																	<td>
																			<input style="display:none;" type="radio" checked value="by_percent" name="price_type_by_change" id="by_percent">
																			<input type="number" name="percentage" id="percentage" value="0" />%<br />
																			<span id="errmsg"></span>
																	</td>
															</tr>
													<tr>
													<input style="display:none;" type="radio" checked value="by_categories" name="price_change_method" id="by_categories">
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
									<div class="col-30">
										<div class="premium-features">
												<a href="https://technocrackers.com/woo-bulk-price-update/" target="_blank"><img src="<?php echo esc_url($plugin_path . 'img/premium.png'); ?>"></a>
												<div class="content_right">
														<p>Buy Activation Key form Here..</p>
														<p><a href="https://technocrackers.com/woo-bulk-price-update/" target="_blank">Buy Now...</a></p>
												</div>
										</div>
										<div class="premium-features-list">
												<h4 class="paid_color"><?php echo esc_html('WooCommerce / Premium Features:', 'wbpu-bulk-price-update'); ?></h4>
												<p class="paid_color"><?php echo esc_html('01. Support for variable products.', 'wbpu-bulk-price-update'); ?></p>
												<p class="paid_color"><?php echo esc_html('02. Update product price with fixed or percentage amount/price.', 'wbpu-bulk-price-update'); ?></p>
												<p class="paid_color"><?php echo esc_html('03. You can update price for specific products.', 'wbpu-bulk-price-update'); ?></p>
												<p class="paid_color"><?php echo esc_html('04. Option for Dry Run, no changes will be made to the database, allowing you to check the results beforehand..', 'wbpu-bulk-price-update'); ?></p>
										</div>
        					</div>
							</div>
							<div class="techno_tabs tab_premium" style="display:none;">
									<?php $this->wbpu_bulk_price_update_pro_html();
									echo wp_kses_post( '<div id="message" class="updated fade" style="border-left-color:#a00;"><p><strong>Premium Features</strong></p></div>' );
									?>
							</div>
					</div>
			</div>
		
	<?php
	}

	function wbpu_change_price_product_ids_callback() {
			if (isset($_POST["cat_ids"]) && $_POST["cat_ids"] != '' && isset($_POST['nonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wporg_product_ids')) {
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
	
	function wbpu_change_price_percentge_callback() {
			if (isset($_POST["product_id"]) && !empty($_POST["product_id"]) && isset($_POST['nonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wporg_product_update_ids')) {
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
							$percentage = sanitize_text_field( trim($_POST["percentage"] ));
							$price_rounds_point = sanitize_text_field(trim($_POST["price_rounds_point"]));
							$tc_dry_run = sanitize_text_field(trim($_POST["tc_dry_run"]));
							$product = wc_get_product(intval(trim($product_id)));
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

								if (($regular_price === null && $sale_price === null) || ($regular_price == 0 && $sale_price == 0)) {
									return;
								}

								// Initialize updated prices
								$sale_product_prc = $sale_price;
								$regular_product_prc = $regular_price;

								// Update logic when regular price exists but sale price is blank or 0
								if ($regular_price !== null && $regular_price > 0) {
									if ($price_type_by_change == 'by_percent') {
										$regular_price_update = $regular_price * ($percentage / 100);
									} elseif ($price_type_by_change == 'by_fixed') {
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
									} elseif ($price_type_by_change == 'by_fixed') {
										$sale_price_update = (float) $percentage;
									}

									if ($opration_type == "increase-percentge-sale" || $opration_type == 'increase-percentge') {
										$sale_product_prc = max($sale_price + $sale_price_update, 0);
									} elseif ($opration_type == "discount-percentge-sale" || $operation_type == 'discount-percentge') {
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
									} elseif ($regular_price !== null && $regular_price == 0) {
										update_post_meta($product->get_id(), '_regular_price', '');
									}

									// Update sale price if it's valid
									if ($sale_price !== null && $sale_price > 0) {
										update_post_meta($product->get_id(), '_sale_price', $sale_product_prc);
										update_post_meta($product->get_id(), '_price', $sale_product_prc); // Sale price takes precedence
									} elseif ($sale_price !== null && $sale_price == 0) {
										update_post_meta($child_id, '_sale_price', '');
									}

								}

								// Update result array with new prices
								$res['new_price_regular'] = ($regular_price !== null && $regular_price > 0) ? $regular_product_prc : '-';
								$res['new_price_sale'] = ($sale_price !== null && $sale_price > 0) ? $sale_product_prc : '-';

								// Build HTML output for updated prices
								$html .= '<tr class="' . $product_id . '"><td><strong>Old Price:</strong></td><td><code>' . ($res['old_price_regular'] !== '' ? $currency . ' ' . $res['old_price_regular'] : '-') . '</code></td></tr>';
								$html .= '<tr><td><strong>New Price:</strong></td><td><code>' . ($res['new_price_regular'] !== '' ? $currency . ' ' . $res['new_price_regular'] : '-') . '</code></td></tr>';
								$html .= '</tbody></table></td>';
								$html .= '<td><table><tbody>';
								$html .= '<tr class="' . $product_id . '"><td><strong>Old Price:</strong></td><td><code>' . ($res['old_price_sale'] !== '' ? $currency . ' ' . $res['old_price_sale'] : '-') . '</code></td></tr>';
								$html .= '<tr><td><strong>New Price:</strong></td><td><code>' . ($res['new_price_sale'] !== '' ? $currency . ' ' . $res['new_price_sale'] : '-') . '</code></td></tr>';
								$html .= '</tbody></table></td>';


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
			}
			exit();
    }


		function wbpu_show_free_plugin_activated_notice(){
			if (get_transient('free_plugin_activated_notice')) {
					?>
					<div class="notice notice-success is-dismissible">
							<p><strong>Bulk Price Update for Woocommerce Free version activated:</strong> The pro version has been deactivated automatically.</p>
					</div>
					<?php
					delete_transient('free_plugin_activated_notice');
			}
		}
}
register_activation_hook(__FILE__, array('WBPU_BULK_PRICE_UPDATE', 'wbpu_activate_free_version'));
new WBPU_BULK_PRICE_UPDATE();?>