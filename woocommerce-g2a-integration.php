<?php 
/*
  Plugin Name: Woocommerce G2A Integration
  Plugin URI: https://alloxes.com/
  description: G2A Importer For WooCommerce	
  Version: 0.2
  Author: Alloxes Developers
  Author URI: http://alloxes.com/
  */
 
 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
ini_set('memory_limit', '256M');
// Log folder path
define( 'WC_G2A_LOG_FILE_PATH', plugin_dir_path( __FILE__ ) . 'logs/' );

// G2A class 
require plugin_dir_path( __FILE__ ).'includes/g2a-class.php';
 
// Plugin pages
require plugin_dir_path( __FILE__ ).'admin-pages.php';

 add_action( 'admin_enqueue_scripts', "enqueue_scripts");
 function enqueue_scripts() {
	wp_enqueue_style( 'g2a-style', plugin_dir_url( __FILE__ ). 'g2a-style.css', array(), 0.1 );	
	
	wp_enqueue_script('g2a_js',plugins_url('js/g2a-integration-admin.js',__FILE__),array("jquery"),'1.2.0', true);	

	wp_localize_script( "g2a_js", 'g2a_js', array('ajaxurl'=>admin_url('admin-ajax.php')));

}
function g2a_enqueue_scripts(){		
	wp_enqueue_style( 'g2a-style', plugin_dir_url( __FILE__ ). 'g2a-style.css', array(), 0.1 );
		
	wp_enqueue_script('g2a_js',plugins_url('js/g2a-integration.js',__FILE__),array("jquery"),'1.2.0', true);
	
	wp_localize_script( "g2a_js", 'g2a_js', array('ajaxurl'=>admin_url('admin-ajax.php')));	
}
add_action( 'wp_enqueue_scripts', "g2a_enqueue_scripts");
  

// Double Check Price and Stock before Order
add_action( 'woocommerce_before_calculate_totals', 'custom_cart_items_prices', 10, 1 );
function custom_cart_items_prices( $cart ) {
	$obj_g2a = new G2A();
	if(!defined( 'DOING_AJAX' )){
		 return;
	}
	
	foreach ( $cart->get_cart() as $cart_item_key=>$cart_item ) {
        
        $product_id = $cart_item['data']->get_id();

       $g2a_id = get_post_meta($product_id,'g2a_id', true);
       //$g2a_ignore_licence = get_post_meta($product_id,'g2a_ignore_licence', true);
	   
		if(!empty($g2a_id)){
			
			$g2a_products = $obj_g2a->g2a_get_products(array("id"=>$g2a_id));

			if(!empty($g2a_products)){
				
				/* $product = wc_get_product( $product_id );$product_price = (float) $product->get_price('edit'); */
				
				$product_price = (float) $cart_item['data']->get_price('edit');
				$quantity_per_item =  $cart_item['quantity'];

				$g2a_qty =	$g2a_products->docs[0]->qty;
				$g2a_retail_min_price =	$g2a_products->docs[0]->retail_min_price;
				$g2a_minPrice =	$g2a_products->docs[0]->minPrice;
				$is_remove_item = false;
				//Check Price  
				$g2a_nkg ="";
				if(!$obj_g2a->g2a_price_check($product_price,$g2a_retail_min_price,$g2a_minPrice,$product_id)){
					$is_remove_item = true;
					$g2a_nkg .=" true price";
					
					wc_add_notice(__('Product price is updated by seller and so refresh page to get updated price here.'), 'error' );
				}
				// Check Stock before Order
				if($quantity_per_item > $g2a_qty){
					$is_remove_item = true;
					$g2a_nkg .="$quantity_per_item > $g2a_qty true Stock";
					WC()->cart->remove_cart_item($cart_item_key);
					wc_add_notice( __('Product is out of stock'), 'error' );
				}
				
				update_post_meta($product_id, '_stock',$g2a_qty);
				
				
				if($is_remove_item){
					/*WC()->cart->remove_cart_item($cart_item_key);
					//WC()->session->set('wc_notices', "removed");
					//
					wc_add_notice( __('Product price is updated by seller and so new price is updated here.'.$product_price." = ".$g2a_retail_min_price." quantity ".$quantity_per_item." = ".$g2a_qty." => ".$g2a_nkg), 'error' );*/
				}
				
				
				$msg  = "Product : $product_price  \n";
				$msg .= "G2a info \n";
				$msg .= "qty : $g2a_qty \n";
				$msg .= "retail_min_price : $g2a_retail_min_price \n";
				$msg .= "g2a_minPrice : $g2a_minPrice \n";
				$obj_g2a->g2a_error_logs($msg);
				
				//$obj_g2a->g2a_error_logs(print_r($g2a_products,true));
			}
			
		}
		
       
    }
	
}
/* ajax g2a sync price */

add_action( 'wp_ajax_g2a_sync_price', 'wc_g2a_sync_pric');
add_action( 'wp_ajax_nopriv_g2a_sync_pric', 'wc_g2a_sync_pric');

function wc_g2a_sync_pric(){
	$post_id = $_REQUEST["post_id"];
	$obj_g2a = new G2A();
	$g2a_price = $obj_g2a->g2a_sync_pric($post_id);
	if(!empty($g2a_price)){
		
		echo json_encode(array("status"=>1,"message"=>"$post_id","g2a_price"=>$g2a_price["g2a_retail_min_price"],"g2a_minPrice"=>$g2a_price["g2a_minPrice"]));
		
	}else{
		echo json_encode(array("status"=>1,"message"=>"$post_id"));
		
	}
	die();
}

/* ajax g2a sync details */

add_action( 'wp_ajax_g2a_sync_details', 'wc_g2a_sync_details');
add_action( 'wp_ajax_nopriv_g2a_sync_details', 'wc_g2a_sync_details');

function wc_g2a_sync_details(){
	$post_id = $_REQUEST["post_id"];
	$obj_g2a = new G2A();
	$obj_g2a->g2a_sync_details($post_id);
	
	echo json_encode(array("status"=>1,"message"=>"$post_id"));
	die();
}

/* AJAX G2a Get Order Details */
add_action( 'wp_ajax_g2a_get_order_details', 'g2a_get_order_details');
add_action( 'wp_ajax_nopriv_g2a_get_order_details', 'g2a_get_order_details');

function g2a_get_order_details(){
	$order_id = $_REQUEST["order_id"];
	$item_id = $_REQUEST["item_id"];
	$qty = $_REQUEST["qty"];
	$obj_g2a = new G2A();
	/* $order = wc_get_order($order_id); */
	$g2a_order_id = wc_get_order_item_meta($item_id,'_g2a_order_id_'.$qty,true);
	$g2a_key = wc_get_order_item_meta($item_id,'_licence_key_x_'.$qty,true);
	//update_post_meta($order_id,'g2a_price', $g2a_price );
	//update_post_meta($order_id,'g2a_currency',$g2a_currency);
	if(!empty($g2a_order_id)){
		
		$order_details = $obj_g2a->g2a_get_order_details($g2a_order_id);
		if(!empty($order_details)){
			$msg = "<p> Order ID : $g2a_order_id </p>";
			$msg .= "<p> Order Key : $g2a_key </p>";
			$msg .= "<p> Status : $order_details->status </p>";
			$msg .= "<p> Price : $order_details->price </p>";
			$msg .= "<p> Currency : $order_details->currency </p>";			
			echo json_encode(array("status"=>1,"message"=>$msg));
		}
		
	}else{
		echo json_encode(array("status"=>0,"message"=>"Add order in G2A. After you can show details"));		
	}	
	die();
}
/* g2a_assign_order_key */

add_action( 'wp_ajax_g2a_assign_order_key', 'g2a_assign_order_key');
add_action( 'wp_ajax_nopriv_g2a_assign_order_key', 'g2a_assign_order_key');
function g2a_assign_order_key(){
	$error_msg = "something went wrong. please try again later";
	$item_id = $_REQUEST["item_id"];
	$qty = $_REQUEST["qty"];
	$order_id = $_REQUEST["order_id"];
	
	$g2a_order_key = trim($_REQUEST["manually_key"]);
	$obj_g2a = new G2A();
	/* $order = wc_get_order( $order_id ); */
	$g2a_order_id = wc_get_order_item_meta($item_id,'_g2a_order_id_'.$qty,true);
	$g2a_key = wc_get_order_item_meta($item_id,'_licence_key_x_'.$qty,true);
	
	if(!empty($g2a_order_key)){
		// Get Order Key
		$g2a_key = $g2a_order_key;
		wc_update_order_item_meta($item_id,'_licence_key_x_'.$qty,strtoupper($g2a_key));
		wc_update_order_item_meta($item_id,'_g2a_manually_key_'.$qty,"yes");
		/* $order->update_status('completed'); */
		
	}else{
		$error_msg ="Please enter order key";
	}
	
	if(!empty($g2a_key)){
		$msg  = "<p> Order ID : $g2a_order_id </p>";
		$msg .= "<p> Order Key : $g2a_key </p>";
			
		echo json_encode(array("status"=>1,"message"=>$msg));
	}else{
		echo json_encode(array("status"=>0,"message"=>"$error_msg"));
	}	
	die();
	
}

/* AJAX Get Order Key */ 
add_action( 'wp_ajax_g2a_get_order_key', 'g2a_get_order_key');
add_action( 'wp_ajax_nopriv_g2a_get_order_key', 'g2a_get_order_key');
function g2a_get_order_key(){
	$error_msg = "something went wrong. please try again later";
	$item_id = $_REQUEST["item_id"];
	$qty = $_REQUEST["qty"];
	$order_id = $_REQUEST["order_id"];
	$obj_g2a = new G2A();
	/* $order = wc_get_order( $order_id ); */
	$g2a_order_id = wc_get_order_item_meta($item_id,'_g2a_order_id_'.$qty,true);
	$g2a_key = wc_get_order_item_meta($item_id,'_licence_key_x_'.$qty,true);
		$error_code = "";	
	if(!empty($g2a_order_id) && empty($g2a_key)){
		// Get Order Key
		$g2a_order_key =  $obj_g2a->g2a_get_order_key($g2a_order_id);
		if(!empty($g2a_order_key) && $g2a_order_key->status != "ERROR"){
			$g2a_key = $g2a_order_key->key;
			wc_update_order_item_meta($item_id,'_licence_key_x_'.$qty,strtoupper($g2a_key));
			/* $order->update_status('completed'); */
		}else{
			$error_msg = $g2a_order_key->message;			$error_code = $g2a_order_key->code;	
		}
		
	}
	
	/* var_dump($g2a_order_key);
	var_dump($g2a_order_id); */
	
	if(!empty($g2a_key)){
		$msg  = "<p> Order ID : $g2a_order_id </p>";
		$msg .= "<p> Order Key : $g2a_key </p>";
			
		echo json_encode(array("status"=>1,"message"=>$msg));
	}else{
		echo json_encode(array("status"=>0,"message"=>"$error_msg","code"=>$error_code));
	}	
	die();
	
}



add_action( 'wp_ajax_g2a_get_order_key_user', 'g2a_get_order_key_user');
add_action( 'wp_ajax_nopriv_g2a_get_order_key_user', 'g2a_get_order_key_user');

function g2a_get_order_key_user(){
	$error_msg = "something went wrong. please try again later";
	$item_id = $_REQUEST["item_id"];
	$qty = $_REQUEST["qty"];
	$order_id = $_REQUEST["order_id"];
	$obj_g2a = new G2A();
	/* $order = wc_get_order( $order_id ); */
	$g2a_order_id = wc_get_order_item_meta($item_id,'_g2a_order_id_'.$qty,true);
	$g2a_key = wc_get_order_item_meta($item_id,'_licence_key_x_'.$qty,true);
		$error_code = "";	
	if(!empty($g2a_order_id) && empty($g2a_key)){
		// Get Order Key
		$g2a_order_key =  $obj_g2a->g2a_get_order_key($g2a_order_id);
		if(!empty($g2a_order_key) && $g2a_order_key->status != "ERROR"){
			$g2a_key = strtoupper($g2a_order_key->key);
			wc_update_order_item_meta($item_id,'_licence_key_x_'.$qty,$g2a_key);
			/* $order->update_status('completed'); */
		}else{
			$error_msg = $g2a_order_key->message;
			$error_code = $g2a_order_key->code;	
		}
		
	}
		
	
	if(!empty($g2a_key)){
		
		$msg = "License key x $qty : $g2a_key ";
			
		echo json_encode(array("status"=>1,"message"=>$msg));
	}else{
		echo json_encode(array("status"=>0,"message"=>"$error_msg","code"=>$error_code));
	}	
	die();	
}

/* AJAX Pay for order */ 
add_action( 'wp_ajax_g2a_pay_order', 'g2a_pay_order');
add_action( 'wp_ajax_nopriv_g2a_pay_order', 'g2a_pay_order');

function g2a_pay_order(){
	$error_msg = "something went wrong. please try again later";
	$item_id = $_REQUEST["item_id"];
	$qty = $_REQUEST["qty"];
	$order_id = $_REQUEST["order_id"];
	$obj_g2a = new G2A();
	/* $order = wc_get_order( $order_id ); */
	$g2a_order_id = wc_get_order_item_meta($item_id,'_g2a_order_id_'.$qty,true);
	$g2a_key = wc_get_order_item_meta($item_id,'_licence_key_x_'.$qty,true);
	
	if(!empty($g2a_order_id) && empty($g2a_key)){
		//Pay for order
		$res_pay = $obj_g2a->pay_for_order($g2a_order_id);
		if(!empty($res_pay)){
			$g2a_transaction_id = $res_pay->transaction_id;
			$g2a_status = $res_pay->status;
			wc_update_order_item_meta($item_id,'_g2a_transaction_id_'.$qty,$g2a_transaction_id);
			
			// Get Order Key
			$g2a_order_key =  $obj_g2a->g2a_get_order_key($g2a_order_id);
			if(!empty($g2a_order_key) && $g2a_order_key->status != "ERROR"){
				$g2a_key = $g2a_order_key->key;
				wc_update_order_item_meta($item_id,'_licence_key_x_'.$qty,strtoupper($g2a_key));
				/* $order->update_status('completed'); */
			}
		}
	}
	if(!empty($g2a_order_id)){
		$msg  = "<p> Order ID : $g2a_order_id </p>";
		$msg .= "<p> Order Key : $g2a_key </p>";
			
		echo json_encode(array("status"=>1,"message"=>$msg));
	}else{
		echo json_encode(array("status"=>0,"message"=>"$error_msg"));
	}	
	die();	
}

/* Ajax for Add order in G2A  */
add_action( 'wp_ajax_g2a_add_order', 'g2a_add_order');
add_action( 'wp_ajax_nopriv_g2a_add_order', 'g2a_add_order');

function g2a_add_order(){
	$error_msg = "something went wrong. please try again later";
	$item_id = $_REQUEST["item_id"];
	$qty = $_REQUEST["qty"];
	$order_id = $_REQUEST["order_id"];
	$obj_g2a = new G2A();
	/* $order = wc_get_order( $order_id ); */
	$g2a_order_id = wc_get_order_item_meta($item_id,'_g2a_order_id_'.$qty,true);
	$g2a_key = wc_get_order_item_meta($item_id,'_licence_key_x_'.$qty,true);
	
	$product_id =  wc_get_order_item_meta($item_id, '_product_id', true);
	$line_total =  wc_get_order_item_meta($item_id, '_line_total', true);	
	
	$g2a_id = get_post_meta($product_id,"g2a_id",true);
	
	if(!empty($g2a_id)){
		// Add order in G2A
		$parameter =array("product_id"=>$g2a_id);
		$res_add_order = $obj_g2a->g2a_add_order($parameter);
		if(!empty($res_add_order)){
			// 
			$g2a_order_id = $res_add_order->order_id;
			$g2a_price = $res_add_order->price;
			$g2a_currency = $res_add_order->currency;
			wc_update_order_item_meta($item_id,'_g2a_order_id_'.$qty,$g2a_order_id);
			//$order->update_status('completed');
		}
		
	}else{
		$error_msg ="This is not G2A product";
	}	
	
	if(!empty($g2a_order_id)){
		$msg  = "<p> Order ID : $g2a_order_id </p>";
		$msg .= "<p> Order Key : $g2a_key </p>";
			
		echo json_encode(array("status"=>1,"message"=>$msg));
	}else{
		echo json_encode(array("status"=>0,"message"=>"$error_msg"));
	}	
	die();
}

/* Ajax for re generate order in G2A  */
add_action( 'wp_ajax_g2a_re_generet_order', 'g2a_re_generet_order');
add_action( 'wp_ajax_nopriv_g2a_re_generet_order', 'g2a_re_generet_order');
function g2a_re_generet_order(){
	
	$error_msg = "something went wrong. please try again later";
	$item_id = $_REQUEST["item_id"];
	$qty = $_REQUEST["qty"];
	$order_id = $_REQUEST["order_id"];
	$obj_g2a = new G2A();
	/* $order = wc_get_order( $order_id ); */
	wc_update_order_item_meta($item_id,'_g2a_order_id_'.$qty,"");
	wc_update_order_item_meta($item_id,'_licence_key_x_'.$qty,"");
	wc_update_order_item_meta($item_id,'_g2a_transaction_id_'.$qty,"");
	
	$product_id =  wc_get_order_item_meta($item_id, '_product_id', true);
	$line_total =  wc_get_order_item_meta($item_id, '_line_total', true);	
	
	$g2a_id = get_post_meta($product_id,"g2a_id",true);
	$is_re_generet =  true;
	if(!empty($g2a_id)){
		// Add order in G2A
		$parameter =array("product_id"=>$g2a_id);
		$res_add_order = $obj_g2a->g2a_add_order($parameter);
		if(!empty($res_add_order)){
			// 
			$g2a_order_id = $res_add_order->order_id;
			$g2a_price = $res_add_order->price;
			$g2a_currency = $res_add_order->currency;
			wc_update_order_item_meta($item_id,'_g2a_order_id_'.$qty,$g2a_order_id);
			/** Pay for order **/
			$res_pay = $obj_g2a->pay_for_order($g2a_order_id);
			if(!empty($res_pay)){
				$g2a_transaction_id = $res_pay->transaction_id;
				$g2a_status = $res_pay->status;
				wc_update_order_item_meta($item_id,'_g2a_transaction_id_'.$qty,$g2a_transaction_id);
				
				// Get Order Key
				$g2a_order_key =  $obj_g2a->g2a_get_order_key($g2a_order_id);
				if(!empty($g2a_order_key) && $g2a_order_key->status != "ERROR"){
					$g2a_key = $g2a_order_key->key;
					wc_update_order_item_meta($item_id,'_licence_key_x_'.$qty,strtoupper($g2a_key));
					
				}else{
					$error_msg = $g2a_order_key->message;
					$error_code = $g2a_order_key->code;	
					$is_re_generet = false;
				}
				
			}else{
				$error_msg ="your order payment is not payable at this time";			
				$is_re_generet = false;
			}
		
			
		}else{
			
			$error_msg ="your order is not added in G2A";
			
			$is_re_generet = false;
		}
		
	}else{
		$error_msg ="This is not G2A product";
		$is_re_generet = false;
	}
	
	
	if($is_re_generet){
		$msg  = "<p> Order ID : $g2a_order_id </p>";
		$msg .= "<p> Order Key : $g2a_key </p>";
			
		echo json_encode(array("status"=>1,"message"=>$msg));
	}else{
		echo json_encode(array("status"=>0,"message"=>"$error_msg"));
	}
	die();
}

/* AJAX Resend Licence Key */ 
add_action( 'wp_ajax_g2a_resend_order_key', 'g2a_resend_order_key');
add_action( 'wp_ajax_nopriv_g2a_resend_order_key', 'g2a_resend_order_key');
function g2a_resend_order_key(){
	$error_msg = "Something went wrong. Please try again later!";
	$msg = "Key re-sent via Email successfully.";
	$order_id = $_REQUEST["post_id"];
	$obj_g2a = new G2A();
	$order = wc_get_order( $order_id );
	$g2a_order_id = get_post_meta($order_id,'g2a_order_id',true);
	$g2a_key = get_post_meta($order_id,'g2a_key',true);
	global $woocommerce;
	$sitename = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    $to = $order->billing_email;
    $meta = get_post_custom($order_id, true);
    $fname = $meta['_billing_first_name'][0];
	$lname = $meta['_billing_last_name'][0];
	$ma_id = woocommerce_get_page_id('myaccount');
    $ma_url = get_permalink($ma_id);
    
	$formated_license = "";
	foreach ($order->get_items() as $item_id => $item_data) 
	{
		$product_id =  $order->get_item_meta($item_id, '_product_id', true);
		
		$item_quantity = $item_data->get_quantity();
		$g2a_id = get_post_meta($product_id,"g2a_id",true);
		if(!empty($g2a_id)){
				
			$formated_license .='<p> Product :'.$item_data->get_name().' </p>';
				
			for ($x = 1; $x <= $item_quantity; $x++) 
			{
			
				$g2a_key =	wc_get_order_item_meta($item_id,'_licence_key_x_'.$x,true);
				$formated_license .= '<p> Product Key x '.$x.' : '. '<b>'. $g2a_key .'</b></p>';			
			}
		}	
		
	}
	/* var_dump($formated_license); */
	
	if(!empty($formated_license ) && $to && '' != trim($to)){
        //$formated_license = 'Product Key: '. '<b>'. $g2a_key .'</b>';
        $heading = 'Product Keys for Order #'. $order_id;
        $subject = $sitename. ' | Product Keys for Order #'. $order_id;
        $message = '<p>Dear '. $fname .' '. $lname .'</p><p>Thank you for your order, those are your product keys for the order #'. $order_id .'.</p>';
        $headers = apply_filters('woocommerce_email_headers', '', 'rewards_message');
        $message .= '<br>' . $formated_license;
        $message .= '<p>You can see all your past orders and product keys <a title="My Account" href="'. $ma_url .'">here</a>.</p>';
        $mailer = $woocommerce->mailer();
        $message = $mailer->wrap_message($heading, $message);
		$mailer->send($to, $subject, $message, $headers, array());
		echo json_encode(array("status"=>1,"message"=>$msg));
	} else {
		echo json_encode(array("status"=>0,"message"=>"$error_msg"));
	}	
	die();
	
}

//add_action( 'woocommerce_payment_complete', 'so_payment_complete',10,1 );
//add_action( 'woocommerce_payment_complete_order_status', 'so_payment_complete',10,2 );
add_action( 'woocommerce_order_status_processing', 'so_payment_complete');
function so_payment_complete($order_id ){
	$obj_g2a = new G2A();
	$obj_g2a->g2a_error_logs("woocommerce_order_status_processing  -- ".$order_id);
    $order = wc_get_order( $order_id );
	//$obj_g2a->g2a_error_logs(print_r($order,true));
    $user = $order->get_user();
	$error_msg ="\n Order : $order_id";
	foreach ($order->get_items() as $item_id => $item_data) 
    {
		//$obj_g2a->g2a_error_logs(print_r($item_data,true));
		$product_id =  $order->get_item_meta($item_id, '_product_id', true);
		$line_total =  $order->get_item_meta($item_id, '_line_total', true);
		 $item_quantity = $item_data->get_quantity();
		$g2a_id = get_post_meta($product_id,"g2a_id",true);
		$g2a_ignore_licence = get_post_meta($product_id,'g2a_ignore_licence', true);
		
		if($g2a_ignore_licence != "yes"){
			
			for ($x = 1; $x <= $item_quantity; $x++) {
				
			$g2a_order_id = $order->get_item_meta($item_id,'_g2a_order_id_'.$x,true);
			
			if(!empty($g2a_id) && empty($g2a_order_id)){
				// Add order in G2A
				$parameter =array("product_id"=>$g2a_id);
				$res_add_order = $obj_g2a->g2a_add_order($parameter);
				if(!empty($res_add_order)){
					// 
					$g2a_order_id = $res_add_order->order_id;
					$g2a_price = $res_add_order->price;
					$g2a_currency = $res_add_order->currency;
					
					wc_update_order_item_meta($item_id,'_g2a_order_id_'.$x,$g2a_order_id);
					
					//$g2a_key = get_post_meta($order_id,'g2a_key',true);
					
					if(!empty($g2a_order_id)){
						//Pay for order
						$res_pay = $obj_g2a->pay_for_order($g2a_order_id);
						if(!empty($res_pay)){
							$g2a_transaction_id = $res_pay->transaction_id;
							$g2a_status = $res_pay->status;
							
							wc_update_order_item_meta($item_id,'_g2a_transaction_id_'.$x,$g2a_transaction_id);
							
							//Get Order Key
							
							$g2a_order_key =  $obj_g2a->g2a_get_order_key($g2a_order_id);
							if(!empty($g2a_order_key)){
								$g2a_key = $g2a_order_key->key;
								wc_update_order_item_meta($item_id,'_licence_key_x_'.$x,strtoupper($g2a_key));
							}
						}
					}	
					
				}			
			}
		
			}
			
		}
		
	} 
	  
	
    if( $user ){
		//$obj_g2a->g2a_error_logs(print_r($user,true));
        // do something with the user
    }
}

/** add order in G2A on-hold **/
add_action( 'woocommerce_order_status_on-hold', 'on_hold_add_G2A_order');
function on_hold_add_G2A_order($order_id)
{
	$obj_g2a = new G2A();
	$obj_g2a->g2a_error_logs("woocommerce_order_status_on-hold  -- ".$order_id);
	
    $order = wc_get_order($order_id);
	
    $user = $order->get_user();
	$error_msg ="\n Order : $order_id";
	foreach ($order->get_items() as $item_id => $item_data) 
    {
		
		$product_id =  $order->get_item_meta($item_id, '_product_id', true);
		$line_total =  $order->get_item_meta($item_id, '_line_total', true);
		 $item_quantity = $item_data->get_quantity();
		$g2a_id = get_post_meta($product_id,"g2a_id",true);
		
		for ($x = 1; $x <= $item_quantity; $x++) {
			
		$g2a_order_id = $order->get_item_meta($item_id,'_g2a_order_id_'.$x,true);
		
		if(!empty($g2a_id) && empty($g2a_order_id)){
			// Add order in G2A
			$parameter =array("product_id"=>$g2a_id);
			$res_add_order = $obj_g2a->g2a_add_order($parameter);
			if(!empty($res_add_order)){
				
				$g2a_order_id = $res_add_order->order_id;
				$g2a_price = $res_add_order->price;
				$g2a_currency = $res_add_order->currency;				
				wc_update_order_item_meta($item_id,'_g2a_order_id_'.$x,$g2a_order_id);	
			}			
		}
	
		}
	} 
		
}

/* Send G2A oreder key */ 
/* add_action( 'woocommerce_order_item_meta_start', 'ts_order_item_meta_start', 10, 3 ); */

/*
add_action( 'woocommerce_order_details_after_order_table', 'ts_order_item_meta_start', 10, 1); 
add_action( 'woocommerce_email_after_order_table', 'ts_order_item_meta_start', 10, 1);
function ts_order_item_meta_start($order ){
	
	$order_id = $order->id;
	$g2a_key = get_post_meta($order_id,'g2a_key',true);
	if(!empty($g2a_key)){
		$product_name = "";
		foreach ($order->get_items() as $item_id => $item_data) 
		{
			 $product = $item_data->get_product();
			 $product_name = $product->get_name();
		}
		echo '<h3>License key</h3><table  >
			<tr>
				<th>Product</th><th> License key </th>
			</tr>
			<tr>
			<td>'.$product_name.'</td>
			<td>  '.$g2a_key.' </td> </tr><table>';			
	}
	return ;
}*/

/********
* Only one product allowed in cart if product is G2A
*********/
/*
 * function check_if_cart_has_product( $valid, $product_id, $quantity ) {  
	$is_g2a =0 ;
	$count_g2a =0 ;
	
	$g2a_id_a = get_post_meta($product_id, 'g2a_id',true);
	
	if(!empty($g2a_id_a)){
			$is_g2a++;
	}
    if(!empty(WC()->cart->get_cart()) && $valid){
        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
			$count_g2a++;			
            $_product = $values['data'];			
			$g2a_id = get_post_meta($_product->id, 'g2a_id',true);
			if(!empty($g2a_id)){
				$is_g2a++;				
			}            
        }
    }
	
	if(WC()->cart->get_cart_contents_count() >= 1 && $is_g2a){
		wc_add_notice( __('You can purchase one product at a time'), 'error' );
		return false;
	}
	
    return $valid;

}
add_filter( 'woocommerce_add_to_cart_validation', 'check_if_cart_has_product', 10, 3);
 */ 
/* */ 
  

/*redirect the G2A product in checkout */
function g2a_custom_add_to_cart_redirect( $url ) {
	 
	global $woocommerce;
	 $lw_redirect_checkout = $woocommerce->cart->get_checkout_url();
	 return $lw_redirect_checkout;  
	
}
//add_filter( 'add_to_cart_redirect', 'g2a_custom_add_to_cart_redirect',10,1);

function skip_cart_page_redirection_to_checkout() {

    // If is cart page, redirect checkout.
    if( is_cart() ){
		$is_g2a = 0;
		foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
				
            $_product = $values['data'];			
			$g2a_id = get_post_meta($_product->id, 'g2a_id',true);
			if(!empty($g2a_id)){
				$is_g2a++;				
			}            
        }
		if($is_g2a > 0){
			wp_redirect( WC()->cart->get_checkout_url() );
		}
		
	}
        
}
//add_action('template_redirect', 'skip_cart_page_redirection_to_checkout');


add_action( 'woocommerce_admin_order_item_values', 'g2a_admin_order_item_values',10,3 );
function g2a_admin_order_item_values( $product, $item, $item_id ) {
	$order_id = get_the_ID();
	$product_id = $item["product_id"];  
	$g2a_id = get_post_meta($product_id, 'g2a_id',true);
	$item_quantity = $item->get_quantity();  
  ?>
  <td class="line_customtitle">
    <?php
	if(!empty($g2a_id)){
		for ($x = 1; $x <= $item_quantity; $x++) {
			
			 /* wc_update_order_item_meta($item_id,'_licence_key_x_'.$x,"");  */
			
			$g2a_order_id = wc_get_order_item_meta($item_id,'_g2a_order_id_'.$x,true);
			$g2a_key = wc_get_order_item_meta($item_id,'_licence_key_x_'.$x,true);
			$g2a_transaction_id = wc_get_order_item_meta($item_id,'_g2a_transaction_id_'.$x,true);
			 
			 if(empty($g2a_order_id) && empty($g2a_key)){
				echo '<p>
				<button id="" class="g2a_add_order button button-primary button-large" type="button" data-item_id="'.$item_id.'" data-qty="'.$x.'" data-order_id="'.$order_id.'">Add an order x '.$x.' </button>
				<span class="spinner spinner_add_o"></span>
			   </p>';
			   }
			   
			   
	   /*  
	   if(!empty($g2a_order_id)){
			echo '<p>
			<button id="" class="g2a_get_order_details button button-primary button-large" type="button" data-item_id="'.$item_id.'" data-qty="'.$x.'" data-order_id="'.$order_id.'" >Get Order status</button>
			<span class="spinner spinner_get_o"></span>
		   </p>';
	   }  */
	   
	   
	   if(!empty($g2a_order_id) && empty($g2a_key) && empty($g2a_transaction_id))
	   {
			echo '<p>
			<button id="" class="g2a_pay_order button button-primary button-large" type="button"  data-item_id="'.$item_id.'" data-qty="'.$x.'" data-order_id="'.$order_id.'" >Pay for an order x '.$x.' </button>
			<span class="spinner spinner_pay_o"></span>
		   </p>';
	   }
   
	   if(!empty($g2a_order_id) && !empty($g2a_transaction_id) && empty($g2a_key)){
		echo '<p>
		<button id="" class="g2a_get_order_key button button-primary button-large" type="button" data-item_id="'.$item_id.'" data-qty="'.$x.'" data-order_id="'.$order_id.'"  >Get License Key x '.$x.' </button>
		<span class="spinner spinner_get_key"></span>
		
		</p>';
	   
	   }  
   
		if(empty($g2a_key)){
			echo '<p>			
				<label for="manually_key"> Manually assign License key x '.$x.' </label>
				</p>';
			
			echo '<p>
				<input type="text" id="manually_key_'.$item_id.'_'.$x.'" class="input-text" placeholder="License Key" ></p>';
				
			echo '<p>
					<button id="" class="g2a_assign_order_key button button-primary button-large" type="button" data-item_id="'.$item_id.'" data-qty="'.$x.'" data-order_id="'.$order_id.'" > Add Manually Key x '.$x.' </button>				
					<span class="spinner spinner_assign_key"></span>			
					</p>';
					
					
					
			 if(!empty($g2a_order_id) && !empty($g2a_transaction_id)){
				echo '<p>
				<button id="" class="g2a_re_generet button button-primary button-large" type="button" data-item_id="'.$item_id.'" data-qty="'.$x.'" data-order_id="'.$order_id.'">Re-generet Order x '.$x.' </button>
				<span class="spinner spinner_add_o"></span>
			   </p>';
			   }
		   }
   
	   }		
	}
	 ?>
  </td>
  <?php
}
 
add_action( 'wp_ajax_g2a_import_products_byids', 'wc_g2a_import_products_byids');
add_action( 'wp_ajax_nopriv_g2a_import_products_byids', 'wc_g2a_import_products_byids');

function wc_g2a_import_products_byids(){
	$status_code = 0;
	$message = "";
	$g2aid = $_POST["g2aid"]; 
	$param = array("id"=>$g2aid);
	$obj_g2a = new G2A(); 
	$g2a_products = $obj_g2a->g2a_get_products($param);	
	//var_dump($g2a_products);
	if(!empty($g2a_products)){
		
		if(isset($g2a_products->docs) && !empty($g2a_products->docs) ){
			foreach($g2a_products->docs as $key=>$pro_data){
				 
				$res_add = $obj_g2a->add_wc_products($pro_data);			
				if($res_add){
					$status_code = 1;
					$message = "( $g2aid ) is successfully added";
				}				
			}	
				
		}else{
			$message = "( $g2aid ) Product not Found";
		}
	}else{
		$message = isset($obj_g2a->g2a_error->message)? "( $g2aid ) ".$obj_g2a->g2a_error->message : "( $g2aid ) Product not Found";
	}
	
	echo json_encode(array("status"=>$status_code,"message"=>$message));
	
	die();
	
}
/* Cron for get product id */ 
add_action('get_product_byid_cron', 'get_product_byid_cron_fun',10,1);
function get_product_byid_cron_fun($g2a_id){
	$obj_g2a = new G2A();
	 
	$obj_g2a->g2a_error_logs('Cron G2A id :'.$g2a_id);	
	$param = array("id"=>$g2a_id);
	
	$g2a_products = $obj_g2a->g2a_get_products($param);
	
	if(!empty($g2a_products)){
			
		if(isset($g2a_products->docs)){
			$obj_g2a->g2a_error_logs("Start add product : ".count($g2a_products->docs));
						
			foreach($g2a_products->docs as $key=>$pro_data){
								
				$obj_g2a->add_wc_products($pro_data);			
								
			}
			
			$obj_g2a->g2a_error_logs("End add product : ".$g2a_id);			
		}		
	}
	$import_ids = get_option('wc_g2a_import_ids');
	$import_ids_arr = !empty($import_ids)? explode(",",$import_ids) : array();
	if (($key = array_search($g2a_id, $import_ids_arr)) !== false) {
		unset($import_ids_arr[$key]);
	}
	$wc_g2a_import_ids = implode(",",$wc_g2a_import_ids);
	update_option('wc_g2a_import_ids',$wc_g2a_import_ids);
	
}
