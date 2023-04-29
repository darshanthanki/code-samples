<?php 
/**
 * Dokan get products by seller in selected category in woodmart theme.
 */
function woodmart_child_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );


// Add slider of product categories on single store
function category_slider_single_store(){
	$store_user   = dokan()->vendor->get( get_query_var( 'author' ) );

	$store_user_id = $store_user->id;
	
	?>
	<style>
		.category-slider {
			display: flex;
			flex-direction: row;
			overflow-x: auto;
		}

		.category-slide {
			flex-shrink: 0;
			margin-right: 20px;
		}
		
		.product{
			margin-bottom:10px;
			padding: 20px;
		}
		
		.product_list{
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			grid-gap: 20px;
		}
		
		.prod_name{
			margin-bottom: 5px;
			font-size: inherit;
			display: block;
			color: #333333;
			word-wrap: break-word;
		}
		.prod_price{
			color : #83b735;
		}

	</style>
	<script>
		jQuery(document).ready(function() {
			jQuery('.category-slider').scrollLeft(0);
				
			jQuery('.prod_cat').click(function(){
				var cat_id = jQuery(this).find('.term_id').val();
				
				jQuery.ajax({
					url : '<?php echo admin_url( "admin-ajax.php" ); ?>',
					type : 'POST',
					data : {
						cat_id : cat_id,
						store_user_id : <?php echo $store_user_id;?>,
						action : 'product_list_by_category'
						
					},
					success : function(data) {
						console.log(data);
						jQuery('.me24-vendor-products .elementor-widget-container').empty().html(data);
					}
				});
			});	
		
		});
	</script>

	<?php
		$seller_id =  get_query_var( 'author' );
		$vendor    = dokan()->vendor->get( $seller_id );
		
		$categories = $vendor->get_store_categories();
		
		if ($categories) {
			echo '<div class="category-slider">';
		
			foreach ($categories as $category) {
			 
				$thumbnail_url = wp_get_attachment_image_src($category->term_id, 'thumbnail')[0];
				
				if(empty($thumbnail_url)){
					$url = $vendor->get_avatar();
				}else{
					$url = $thumbnail_url;
				}
			
				$store_url = get_site_url().'/store/'.$vendor->data->user_nicename.'/section/'.$category->term_id;
			  
			
				echo '<div class="category-slide">';
					echo '<a class="prod_cat">';
						echo '<img src="' . $url . '" alt="' . $category->name . '">';
						echo '<h3>' . $category->name . '</h3>';
						echo '<input type="hidden" value="'.$category->term_id.'" class="term_id" />';
					echo '</a>';
				echo '</div>';
			  
			}

			echo '</div>';
		 
			//echo '<div class="product_list " style="display: grid;grid-template-columns: repeat(4, 1fr);grid-gap: 20px;"></div>';
			echo '<div class="product-grid-item"><div class="product-wrapper"><div class="product_list " ></div></div></div>';
		}
		
	}
add_shortcode('category_slider','category_slider_single_store');

function product_list_by_category(){

	$out = "";
	
	$store_user_id	= $_POST['store_user_id'];
	$cat_id = $_POST['cat_id'];
	
	$args = array(
		'post_type' => 'product',
		'posts_per_page' => 10,
		'tax_query' => array(
			array(
				'taxonomy' => 'product_cat',
				'terms' => $cat_id 
			),
		),
		'meta_query' => array(
			'key' => 'post_author',
			'value' => $store_user_id,
			'compare' => '='
		)
		//'author'         => $current_user_id,
	);
	
	$products_query = new WP_Query($args);

	$products_query->rewind_posts();
	//var_dump($products_query);
	ob_start();
	
	
	
	if ( $products_query->have_posts() ): ?>

		<div class="seller-items site-main woocommerce">

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( $products_query->have_posts() ) : $products_query->the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

		</div>

		<?php //dokan_content_nav( 'nav-below' ); ?>

	<?php else: ?>

		<p class="dokan-info"><?php esc_html_e( 'No products were found on this vendor!', 'dokan' ); ?></p>

	<?php endif; 
	
	$out = ob_get_clean();
	
	echo $out;
	
	die();
}
add_action('wp_ajax_product_list_by_category', 'product_list_by_category');
add_action('wp_ajax_nopriv_product_list_by_category', 'product_list_by_category');
?>
