<?php
/**
 * WooCommerce Points and Rewards
 *
 * @package     WC-Points-Rewards/Classes
 * @author      WooThemes
 * @copyright   Copyright (c) 2013, WooThemes
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Product class
 *
 * Handle messages for the single product page, and calculations for how many points are earned for a product purchase,
 * along with the discount available for a specific product
 *
 * @since 1.0
 */
class WC_Points_Rewards_Product {


	/**
	 * Add product-related hooks / filters
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// add single product message immediately after product excerpt
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_product_message' ) );

		// add variation message before the price is displayed
		add_filter( 'woocommerce_variation_price_html',      array( $this, 'render_variation_message' ), 10, 2 );
		add_filter( 'woocommerce_variation_sale_price_html', array( $this, 'render_variation_message' ), 10, 2 );
	}


	/**
	 * Add "Earn X Points when you purchase" message to the single product page for simple products
	 *
	 * @since 1.0
	 */
	public function render_product_message() {
		global $product;

		// only display on single product page
		if ( ! is_product() )
			return;

		$message = get_option( 'wc_points_rewards_single_product_message' );

		$points_earned = self::get_points_earned_for_product_purchase( $product );

		// bail if none available
		if ( ! $message || ! $points_earned )
			return;

		// replace message variables
		$message = $this->replace_message_variables( $message, $product );

		echo apply_filters( 'wc_points_rewards_single_product_message', $message );
	}


	/**
	 * Add "Earn X Points when you purchase" message to the single product page for variable products
	 *
	 * @since 1.0
	 */
	public function render_variation_message( $price_html, $product ) {

		if ( ! is_product() )
			return $price_html;

		$message = get_option( 'wc_points_rewards_single_product_message' );

		$points_earned = self::get_points_earned_for_product_purchase( $product );

		// bail if none available
		if ( ! $message || ! $points_earned )
			return $price_html;

		// replace message variables
		$price_html = $this->replace_message_variables( $message, $product ) . $price_html;

		return $price_html;
	}


	/**
	 * Replace product page message variables :
	 *
	 * {points} - the points earned for purchasing the product
	 * {points_value} - the monetary value of the points earned
	 * {points_label} - the label used for points
	 *
	 * @since 1.0
	 * @param string $message the message set in the admin settings
	 * @param object $product the product
	 * @return string the message with variables replaced
	 */
	private function replace_message_variables( $message, $product ) {

		global $wc_points_rewards;

		$points_earned = self::get_points_earned_for_product_purchase( $product );

		// the min/max points earned for variable products can't be determined reliably, so the 'earn X points...' message
		// is not shown until a variation is selected, unless the prices for the variations are all the same
		// in which case, treat it like a simple product and show the message
		if ( method_exists( $product, 'get_variation_price' ) && $product->min_variation_price != $product->max_variation_price )
			return '';

		// points earned
		$message = str_replace( '{points}', number_format_i18n( $points_earned ), $message );

		// points label
		$message = str_replace( '{points_label}', $wc_points_rewards->get_points_label( $points_earned ), $message );

		if ( method_exists( $product, 'get_variation_price' ) )
			$message = '<span class="wc-points-rewards-product-variation-message">' . $message . '</span><br/>';
		else
			$message = '<span class="wc-points-rewards-product-message">' . $message . '</span>';

		return $message;
	}


	/**
	 * Return the points earned when purchasing a product. If points are set at both the product and category level,
	 * the product points are used. If points are not set at the product or category level, the points are calculated
	 * using the default points per currency and the price of the product
	 *
	 * @since 1.0
	 * @param object $product the product to get the points earned for
	 * @return int the points earned
	 */
	public static function get_points_earned_for_product_purchase( $product ) {

		if ( ! is_object( $product ) )
			$product = get_product( $product );

		// check if earned points are set at product-level
		$points = self::get_product_points( $product );

		if ( is_numeric( $points ) )
			return $points;

		// check if earned points are set at category-level
		$points = self::get_category_points( $product );

		if ( is_numeric( $points ) )
			return $points;

		// otherwise, show the default points set for the price of the product
		return WC_Points_Rewards_Manager::calculate_points( $product->get_price() );
	}


	/**
	 * Return the points earned at the product level if set. If a percentage multiplier is set (e.g. 200%), the points are
	 * calculated based on the price of the product then multiplied by the percentage
	 *
	 * @since 1.0
	 * @param object $product the product to get the points earned for
	 * @return int the points earned
	 */
	private static function get_product_points( $product ) {

		if ( empty( $product->variation_id ) ) {

			// simple or variable product, for variable product return the maximum possible points earned
			if ( method_exists( $product, 'get_variation_price' ) ) {
				$points = ( isset( $product->wc_max_points_earned ) ) ? $product->wc_max_points_earned : '';
			} else {
				$points = ( isset( $product->wc_points_earned ) ) ? $product->wc_points_earned : '';
			}

		} else {

			// variation product
			$points = ( isset( $product->product_custom_fields['_wc_points_earned'][0] ) ) ? $product->product_custom_fields['_wc_points_earned'][0] : '';

			// if points aren't set at variation level, use them if they're set at the product level
			if ( '' === $points )
				$points = ( isset( $product->parent->wc_points_earned ) ) ? $product->parent->wc_points_earned : '';
		}

		// if a percentage modifier is set, adjust the points for the product by the percentage
		if ( false !== strpos( $points, '%' ) )
			$points = self::calculate_points_multiplier( $points, $product );

		return $points;
	}


	/**
	 * Return the points earned at the category level if set. If a percentage multiplier is set (e.g. 200%), the points are
	 * calculated based on the price of the product then multiplied by the percentage
	 *
	 * @since 1.0
	 * @param object $product the product to get the points earned for
	 * @return int the points earned
	 */
	private static function get_category_points( $product ) {

		$category_ids = woocommerce_get_product_terms( $product->id, 'product_cat', 'ids' );

		$category_points = '';

		foreach ( $category_ids as $category_id ) {

			$points = get_woocommerce_term_meta( $category_id, '_wc_points_earned', true );

			// if a percentage modifier is set, adjust the default points earned for the category by the percentage
			if ( false !== strpos( $points, '%' ) )
				$points = self::calculate_points_multiplier( $points, $product );

			if ( ! is_numeric( $points ) )
				continue;

			// in the case of a product being assigned to multiple categories with differing points earned, we want to return the biggest one
			if ( $points >= (int) $category_points )
				$category_points = $points;
		}

		return $category_points;
	}


	/**
	 * Calculate the points earned when a product or category is set to a percentage. This modifies the default points
	 * earned based on the global "Earn Points Conversion Rate" setting and products price by the given $percentage.
	 * e.g. a 200% multiplier will change 5 points to 10.
	 *
	 * @since 1.0
	 * @param string $percentage the percentage to multiply the default points earned by
	 * @param object $product the product to get the points earned for
	 * @return int the points earned after adjusting for the multiplier
	 */
	private static function calculate_points_multiplier( $percentage, $product ) {

		$percentage = str_replace( '%', '', $percentage ) / 100;

		return $percentage * WC_Points_Rewards_Manager::calculate_points( $product->get_price() );
	}


	/**
	 * Return the maximum discount available for redeeming points. If a max discount is set at both the product and
	 * category level, the product max discount is used. A global max discount can be set which is used as a fallback if
	 * no other max discounts are set
	 *
	 * @since 1.0
	 * @param object $product the product to get the maximum discount for
	 * @return float|string the maximum discount or an empty string which means a maximum discount is not set for the given product
	 */
	public static function get_maximum_points_discount_for_product( $product ) {

		if ( ! is_object( $product ) )
			$product = get_product( $product );

		// check if max discount is set at product-level
		$max_discount = self::get_product_max_discount( $product );

		if ( is_numeric( $max_discount ) )
			return $max_discount;

		// check if max discount is are set at category-level
		$max_discount = self::get_category_max_discount( $product );

		if ( is_numeric( $max_discount ) )
			return $max_discount;

		// limit the discount available by the global maximum discount if set
		$max_discount = get_option( 'wc_points_rewards_max_discount' );

		// if the global max discount is a percentage, calculate it by multiplying the percentage by the product price
		if ( false !== strpos( $max_discount, '%' ) )
			$max_discount = self::calculate_discount_modifier( $max_discount, $product );

		if ( is_numeric( $max_discount ) )
			return $max_discount;

		// otherwise, there is no maximum discount set
		return '';
	}


	/**
	 * Return the maximum point discount at the product level if set. If a percentage multiplier is set (e.g. 35%),
	 * the maximum discount is equal to the product's price times the percentage
	 *
	 * @since 1.0
	 * @param object $product the product to get the maximum discount for
	 * @return float|string the maximum discount
	 */
	private static function get_product_max_discount( $product ) {

		if ( empty( $product->variation_id ) ) {

			// simple product
			$max_discount = ( isset( $product->wc_points_max_discount ) ) ? $product->wc_points_max_discount : '';

		} else {
			// variable product
			$max_discount = ( isset( $product->product_custom_fields['_wc_points_max_discount'][0] ) ) ? $product->product_custom_fields['_wc_points_max_discount'][0] : '';
		}

		// if a percentage modifier is set, set the maximum discount using the price of the product
		if ( false !== strpos( $max_discount, '%' ) )
			$max_discount = self::calculate_discount_modifier( $max_discount, $product );

		return $max_discount;
	}


	/**
	 * Return the maximum points discount at the category level if set. If a percentage multiplier is set (e.g. 35%),
	 * the maximum discount is equal to the product's price times the percentage
	 *
	 * @since 1.0
	 * @param object $product the product to get the maximum discount for
	 * @return float|string the maximum discount
	 */
	private static function get_category_max_discount( $product ) {

		$category_ids = woocommerce_get_product_terms( $product->id, 'product_cat', 'ids' );

		$category_max_discount = '';

		foreach ( $category_ids as $category_id ) {

			$max_discount = get_woocommerce_term_meta( $category_id, '_wc_points_max_discount', true );

			// if a percentage modifier is set, set the maximum discount using the price of the product
			if ( false !== strpos( $max_discount, '%' ) )
				$max_discount = self::calculate_discount_modifier( $max_discount, $product );

			// get the minimum discount if the product belongs to multiple categories with differing maximum discounts
			if ( ! is_numeric( $category_max_discount ) || $max_discount < $category_max_discount )
				$category_max_discount = $max_discount;
		}

		return $category_max_discount;
	}


	/**
	 * Calculate the maximum points discount when it's set to a percentage by multiplying the percentage times the product's
	 * price
	 *
	 * @since 1.0
	 * @param string $percentage the percentage to multiply the price by
	 * @param object $product the product to get the maximum discount for
	 * @return float the maximum discount after adjusting for the percentage
	 */
	private static function calculate_discount_modifier( $percentage, $product ) {

		$percentage = str_replace( '%', '', $percentage ) / 100;

		return $percentage * $product->get_price();
	}


} // end \WC_Points_Rewards_Product class
