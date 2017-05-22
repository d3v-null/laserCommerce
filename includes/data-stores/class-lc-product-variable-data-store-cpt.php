<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once(WOOCOMMERCE_BASE . "/includes/class-wc-data-store.php");
require_once(WOOCOMMERCE_BASE . "/includes/interfaces/class-wc-object-data-store-interface.php");
require_once(WOOCOMMERCE_BASE . "/includes/interfaces/class-wc-product-variable-data-store-interface.php");
require_once(WOOCOMMERCE_BASE . "/includes/interfaces/class-wc-product-data-store-interface.php");
require_once(WOOCOMMERCE_BASE . "/includes/data-stores/class-wc-data-store-wp.php");
require_once(WOOCOMMERCE_BASE . "/includes/data-stores/class-wc-product-data-store-cpt.php");
require_once(WOOCOMMERCE_BASE . "/includes/data-stores/class-wc-product-variable-data-store-cpt.php");

/**
 * LaserCommerce Variable Product Data Store: Stored in CPT.
 */
class LC_Product_Variable_Data_Store_CPT extends WC_Product_Variable_Data_Store_CPT {
    // Just wanted to see if this would break anythig
}
