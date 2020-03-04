<?php
/**
 * Plugin Name: WooMultistore Custom Feature Plugin
 * Description: WooMultistore Custom Feature Plugin
 * Author: WooCommerce Multistore
 * Author URI: https://woomultistore.com/
 * Version: 1.0.0
 * WC tested up to: 3.8.0
**/

class WOOMULTI_CUSTOM_PLUGIN {

	private $updater = null;

	public function __construct() {
		add_action('init', array($this, 'init') );
	}

	public function init() {
		$this->updater = new WOO_MSTORE_admin_product(false);
		add_action('WOO_MSTORE_admin_product/slave_product_updated', array( $this, 'sync_price_for_variable_products'), 10, 1);
	}

	public function sync_price_for_variable_products($data) {
		$current_blog_id = get_current_blog_id();
		restore_current_blog();

		$master_blog_id = get_current_blog_id();
		$quantity = array();
		$quantity[ $data['master_product']->get_id() ] = array(
			'manage_stock' => $data['master_product']->get_manage_stock(),
			'quantity' 	   => $data['master_product']->get_stock_quantity(),
			'price'		   => $data['master_product']->get_regular_price(),
			'sale_price'   => $data['master_product']->get_sale_price(),
		);

		$_children = $data['master_product']->get_children();

		if ( !empty($_children) ) {
			foreach ( $_children as $child ) {
				$var = wc_get_product( $child );
				if ( $var ) {
					$quantity[ $var->get_id() ] = array(
						'manage_stock' => $var->get_manage_stock(),
						'quantity' 	   => $var->get_stock_quantity(),
						'price'		   => $var->get_regular_price(),
						'sale_price'   => $var->get_sale_price(),
					);
				}
			}
		}

		// go back to the child site.
		switch_to_blog( $current_blog_id );

		if ( !empty($quantity) ) {
			foreach ($quantity as $key => $value) {
				$child_product_id = $this->updater->get_slave_product_id($master_blog_id, $key);

				if ( !empty($child_product_id) ) {
					$child_product = wc_get_product( $child_product_id );
					$child_product->set_manage_stock( $value['manage_stock'] );
					$child_product->set_regular_price( $value['price'] );
					$child_product->set_sale_price( $value['sale_price'] );
					$child_product->set_stock_quantity( $value['quantity'] );
					$child_product->save();
				}
			}
		}
	}
}

new WOOMULTI_CUSTOM_PLUGIN();
