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
 * Admin class
 *
 * Load / saves admin settings
 *
 * @since 1.0
 */
class WC_Points_Rewards_Admin {

	/** @var string settings page ID */
	private $page_id;

	/** @var array points & rewards manage/actions tabs */
	private $tabs;

	/** @var string settings page tab ID */
	private $settings_tab_id = 'points';

	/* @var \WC_Points_Rewards_Manage_Points_List_Table the manage points list table object */
	private $manage_points_list_table;

	/* @var \WC_Points_Rewards_Points_Log_List_Table The points log list table object */
	private $points_log_list_table;


	/**
	 * Setup admin class
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$this->tabs = array(
			'manage'  => __( 'Manage Points', 'wc_points_rewards' ),
			'log' => __( 'Points Log', 'wc_points_rewards' ),
		);

		/** General admin hooks */

		// Load WC styles / scripts
		add_filter( 'woocommerce_screen_ids', array( $this, 'load_wc_scripts' ) );

		// add 'Points & Rewards' link under WooCommerce menu
		add_action( 'admin_menu', array( $this, 'add_menu_link' ) );

		// manage points / points log list table settings
		add_action( 'in_admin_header',   array( $this, 'load_list_tables' ) );
		add_filter( 'set-screen-option', array( $this, 'set_list_table_options' ), 10, 3 );
		add_filter( 'manage_woocommerce_page_wc_points_rewards_columns', array( $this, 'manage_columns' ) );

		// warn that points won't be able to be redeemed if coupons are disabled
		add_action( 'admin_notices', array( $this, 'verify_coupons_enabled' ) );

		/** WC settings hooks */

		// add 'Points & Rewards' tab to WooCommerce settings
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab'  ), 50 );

		// show settings
		add_action( 'woocommerce_settings_tabs_' . $this->settings_tab_id, array( $this, 'render_settings' ) );

		// save settings
		add_action( 'woocommerce_update_options_' . $this->settings_tab_id, array( $this, 'save_settings' ) );

		// Add a custom field types
		add_action( 'woocommerce_admin_field_conversion_ratio', array( $this, 'render_conversion_ratio_field' ) );
		add_action( 'woocommerce_admin_field_singular_plural',  array( $this, 'render_singular_plural_field' ) );

		// save custom field types
		add_action( 'woocommerce_update_option_conversion_ratio', array( $this, 'save_conversion_ratio_field' ) );
		add_action( 'woocommerce_update_option_singular_plural',  array( $this, 'save_singular_plural_field' ) );

		// Add a apply points woocommerce_admin_fields() field type
		add_action( 'woocommerce_admin_field_apply_points', array( $this, 'render_apply_points_section' ) );

		// handle any settings page actions (apply points to previous orders)
		add_action( 'woocommerce_settings_start', array( $this, 'handle_settings_actions' ) );

		/** Order hooks */

		// Add the points earned/redeemed for a discount to the edit order page
		add_action( 'woocommerce_admin_order_totals_after_shipping', array( $this, 'render_points_earned_redeemed_info' ) );

		/** Coupon hooks */

		// Add coupon points modifier field
		add_action( 'woocommerce_coupon_options', array( $this, 'render_coupon_points_modifier_field' ) );

		// Save coupon points modifier field
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'save_coupon_points_modifier_field' ) );

		// Sync variation prices
		add_action( 'woocommerce_variable_product_sync', array( $this, 'variable_product_sync' ), 10, 2 );
	}


	/**
	 * Verify that coupouns are enabled and render an annoying warning in the
	 * admin if they are not
	 *
	 * @since 1.0
	 */
	public function verify_coupons_enabled() {

		$coupons_enabled = get_option( 'woocommerce_enable_coupons' ) == 'no' ? false : true;

		if ( ! $coupons_enabled ) {
			$message = sprintf(
				__( 'WooCommerce Points and Rewards requires coupons to be %senabled%s in order to function properly and allow customers to redeem points during checkout.', 'wc_points_rewards' ),
				'<a href="' . admin_url('admin.php?page=woocommerce_settings&tab=general') . '">',
				'</a>'
			);

			echo '<div class="error"><p>' . $message . '</p></div>';
		}
	}


	/**
	 * Add settings/export screen ID to the list of pages for WC to load its JS on
	 *
	 * @since 1.0
	 * @param array $screen_ids
	 * @return array
	 */
	public function load_wc_scripts( $screen_ids ) {
		$wc_screen_id = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );

		// sub-menu page
		$screen_ids[] = $wc_screen_id . '_page_wc_points_rewards';

		// add/edit product category page
		$screen_ids[] = 'edit-product_cat';

		return $screen_ids;
	}


	/** 'Points & Rewards' sub-menu methods ******************************************************/


	/**
	 * Add 'Points & Rewards' sub-menu link under 'WooCommerce' top level menu
	 *
	 * @since 1.0
	 */
	public function add_menu_link() {

		$this->page_id = add_submenu_page(
			'woocommerce',
			__( 'Points & Rewards', 'wc_points_rewards' ),
			__( 'Points & Rewards', 'wc_points_rewards' ),
			'manage_woocommerce',
			'wc_points_rewards',
			array( $this, 'show_sub_menu_page' )
		);

		// add the Manage Points/Points log list table Screen Options
		add_action( 'load-' . $this->page_id, array( $this, 'add_list_table_options' ) );
	}


	/**
	 * Save our list table options
	 *
	 * @since 1.0
	 * @param string $status unknown
	 * @param string $option the option name
	 * @param mixed $value the option value
	 * @return mixed
	 */
	public function set_list_table_options( $status, $option, $value ) {
		if ( 'wc_points_rewards_manage_points_customers_per_page' == $option || 'wc_points_rewards_points_log_per_page' == $option )
			return $value;

		return $status;
	}


	/**
	 * Add list table Screen Options
	 *
	 * @since 1.0
	 */
	public function add_list_table_options() {

		if ( isset( $_GET['tab'] ) && 'log' === $_GET['tab'] ) {
			$args = array(
				'label' => __( 'Points Log', 'wc_points_rewards' ),
				'default' => 20,
				'option' => 'wc_points_rewards_points_log_per_page',
			);
		} else {
			$args = array(
				'label' => __( 'Manage Points', 'wc_points_rewards' ),
				'default' => 20,
				'option' => 'wc_points_rewards_manage_points_customers_per_page',
			);
		}

		add_screen_option( 'per_page', $args );
	}


	/**
	 * Loads the list tables so the columns can be hidden/shown from
	 * the page Screen Options dropdown (this must be done prior to Screen Options
	 * being rendered)
	 *
	 * @since 1.0
	 */
	public function load_list_tables() {

		if ( isset( $_GET['page'] ) && 'wc_points_rewards' == $_GET['page'] ) {
			if ( isset( $_GET['tab'] ) && 'log' == $_GET['tab'] )
				$this->get_points_log_list_table();
			else
				$this->get_manage_points_list_table();
		}
	}


	/**
	 * Returns the list table columns so they can be managed from the screen
	 * options pulldown.  Normally this would happen automatically based on the
	 * screen id, but since we have two distinct list tables sharing one screen
	 * we had to generate unique id's in the two list table constructors, which
	 * means that the core manage_{screen_id}_columns filters don't get called,
	 * so we hook on the screen-based filter and then call our two custom-screen
	 * based filters to get the columns based on the current tab.
	 *
	 * Unfortunately the settings still seem to be saved to the common screen id
	 * so hiding a column in one list table hides a column of the same name in
	 * the other
	 *
	 * @since 1.0
	 * @param $columns array array of column definitions
	 * @return array of column definitions
	 */
	public function manage_columns( $columns ) {
		if ( isset( $_GET['page'] ) && 'wc_points_rewards' == $_GET['page'] ) {
			if ( isset( $_GET['tab'] ) && 'log' == $_GET['tab'] )
				$columns = apply_filters( 'manage_woocommerce_page_wc_points_rewards_points_log_columns', $columns );
			else
				$columns = apply_filters( 'manage_woocommerce_page_wc_points_rewards_manage_points_columns', $columns );
		}

		return $columns;
	}


	/**
	 * Show Points & Rewards Manage/Log page content
	 *
	 * @since 1.0
	 */
	public function show_sub_menu_page() {

		$current_tab = ( empty( $_GET['tab'] ) ) ? 'manage' : urldecode( $_GET['tab'] );

		?>
		<div class="wrap woocommerce">
			<div id="icon-woocommerce" class="icon32-woocommerce-users icon32"><br /></div>
			<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">

			<?php

				// display tabs
				foreach ( $this->tabs as $tab_id => $tab_title ) {

					$class = ( $tab_id == $current_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
					$url   = add_query_arg( 'tab', $tab_id, admin_url( 'admin.php?page=wc_points_rewards' ) );

					printf( '<a href="%s" class="%s">%s</a>', $url, $class, $tab_title );
				}

			?> </h2> <?php


			// display tab content, default to 'Manage' tab
			if ( 'log' === $current_tab )
				$this->show_log_tab();
			else
				$this->show_manage_tab();

		?></div> <?php
	}


	/**
	 * Show the Points & Rewards > Manage tab content
	 *
	 * @since 1.0
	 */
	private function show_manage_tab() {

		// setup 'Manage Points' list table and prepare the data
		$manage_table = $this->get_manage_points_list_table();
		$manage_table->prepare_items();

		?><form method="get" id="mainform" action="" enctype="multipart/form-data"><?php

		// title/search result string
		echo '<h2>' . __( 'Manage Customer Points', 'wc_points_rewards' ) . '</h2>';

		// display any action messages
		$manage_table->render_messages();

		echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';

		// display the list table
		$manage_table->display();
		?></form><?php
	}


	/**
	 * Show the Points & Rewards > Log tab content
	 *
	 * @since 1.0
	 */
	private function show_log_tab() {

		// setup 'Points Log' list table and prepare the data
		$log_table = $this->get_points_log_list_table();
		$log_table->prepare_items();

		?><form method="get" id="mainform" action="" enctype="multipart/form-data"><?php

		// title/search result string
		echo '<h2>' . __( 'Points Log', 'wc_points_rewards' ) . '</h2>';

		echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $_REQUEST['tab'] ) . '" />';

		// display the list table
		$log_table->display();
		?></form><?php
	}


	/**
	 * Gets the manage points list table object
	 *
	 * @since 1.0
	 * @return \WC_Points_Rewards_Manage_Points_List_Table the points & rewards manage points list table object
	 */
	private function get_manage_points_list_table() {
		global $wc_points_rewards;

		if ( ! is_object( $this->manage_points_list_table ) ) {

			$class_name = apply_filters( 'wc_points_rewards_manage_points_list_table_class_name', 'WC_Points_Rewards_Manage_Points_List_Table' );

			require( $wc_points_rewards->get_plugin_path() . '/classes/class-wc-points-rewards-manage-points-list-table.php' );

			$this->manage_points_list_table = new $class_name();
		}

		return $this->manage_points_list_table;
	}


	/**
	 * Gets the points log list table object
	 *
	 * @since 1.0
	 * @return \WC_Points_Rewards_Points_Log_List_Table the points & rewards points log list table object
	 */
	private function get_points_log_list_table() {
		global $wc_points_rewards;

		if ( ! is_object( $this->points_log_list_table ) ) {

			$class_name = apply_filters( 'wc_points_rewards_points_log_list_table_class_name', 'WC_Points_Rewards_Points_Log_List_Table' );

			require( $wc_points_rewards->get_plugin_path() . '/classes/class-wc-points-rewards-points-log-list-table.php' );

			$this->points_log_list_table = new $class_name();
		}

		return $this->points_log_list_table;
	}


	/** 'Points & Rewards' WC settings tab methods ******************************************************/


	/**
	 * Add 'Points & Rewards' tab to WooCommerce Settings tabs
	 *
	 * @since 1.0
	 * @param array $settings_tabs tabs array sans 'Points & Rewards' tab
	 * @return array $settings_tabs now with 100% more 'Points & Rewards' tab!
	 */
	public function add_settings_tab( $settings_tabs ) {

		$settings_tabs[ $this->settings_tab_id ] = __( 'Points & Rewards', 'wc_points_rewards' );

		return $settings_tabs;
	}


	/**
	 * Render the 'Points & Rewards' settings page
	 *
	 * @since 1.0
	 */
	public function render_settings() {
		global $woocommerce;

		woocommerce_admin_fields( $this->get_settings() );

		$confirm_message = __( 'Are you sure you want to apply points to all previous orders that have not already had points generated? This cannot be reversed! Note that this can take some time in shops with a large number of orders, if an error occurs, simply Apply Points again to continue the process.', 'wc_points_rewards' );

		$js = "
			// confirm admin wants to apply points to all previous orders
			$( '#wc_points_rewards_apply_points_to_previous_orders' ).click( function( e ) {
				if ( ! confirm( '" . esc_js( $confirm_message ) . "' ) ) {
					e.preventDefault();
				}
			} );
		";

		if ( function_exists( 'wc_enqueue_js' ) ) {
			wc_enqueue_js( $js );
		} else {
			$woocommerce->add_inline_js( $js );
		}
	}


	/**
	 * Save the 'Points & Rewards' settings page
	 *
	 * @since 1.0
	 */
	public function save_settings() {

		woocommerce_update_options( $this->get_settings() );
	}


	/**
	 * Returns settings array for use by render/save/install default settings methods
	 *
	 * @since 1.0
	 * @return array settings
	 */
	public static function get_settings() {

		$settings = array(

			array(
				'title' => __( 'Points Settings', 'wc_points_rewards' ),
				'type'  => 'title',
				'id'    => 'wc_points_rewards_points_settings_start'
			),

			// earn points conversion
			array(
				'title'    => __( 'Earn Points Conversion Rate', 'wc_points_rewards' ),
				'desc_tip' => __( 'Set the number of points awarded based on the product price.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_earn_points_ratio',
				'default'  => '1:1',
				'type'     => 'conversion_ratio'
			),

			// earn points conversion
			array(
				'title'    => __( 'Earn Points Rounding Mode', 'wc_points_rewards' ),
				'desc_tip' => __( 'Set how points should be rounded.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_earn_points_rounding',
				'default'  => 'round',
				'options'  => array(
					'round' => 'Round to nearest integer',
					'floor' => 'Always round down',
					'ceil'  => 'Always round up',
				),
				'type'     => 'select'
			),

			// redeem points conversion
			array(
				'title'    => __( 'Redemption Conversion Rate', 'wc_points_rewards' ),
				'desc_tip' => __( 'Set the value of points redeemed for a discount.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_redeem_points_ratio',
				'default'  => '100:1',
				'type'     => 'conversion_ratio'
			),

			// redeem points conversion
			array(
				'title'    => __( 'Partial Redemption', 'wc_points_rewards' ),
				'desc'     => __( 'Enable partial redemption', 'wc_points_rewards' ),
				'desc_tip' => __( 'Lets users enter how many points they wish to redeem during cart/checkout.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_partial_redemption_enabled',
				'default'  => 'no',
				'type'     => 'checkbox'
			),

			// maximum points discount available
			array(
				'title'    => __( 'Maximum Points Discount', 'wc_points_rewards' ),
				'desc_tip' => __( 'Set the maximum product discount allowed for the cart when redeeming points. Use either a fixed monetary amount or a percentage based on the product price. Leave blank to disable.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_cart_max_discount',
				'default'  => '',
				'type'     => 'text',
			),

			// maximum points discount available
			array(
				'title'    => __( 'Maximum Product Points Discount', 'wc_points_rewards' ),
				'desc_tip' => __( 'Set the maximum product discount allowed when redeeming points per-product. Use either a fixed monetary amount or a percentage based on the product price. Leave blank to disable. This can be overridden at the category and product level.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_max_discount',
				'default'  => '',
				'type'     => 'text',
			),

			// points label
			array(
				'title'    => __( 'Points Label', 'wc_points_rewards' ),
				'desc_tip' => __( 'The label used to refer to points on the frontend, singular and plural.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_points_label',
				'default'  => sprintf( '%s:%s', __( 'Point', 'wc_points_rewards' ), __( 'Points', 'wc_points_rewards' ) ),
				'type'     => 'singular_plural',
			),

			array( 'type' => 'sectionend', 'id' => 'wc_points_rewards_points_settings_end' ),

			array(
				'title' => __( 'Product / Cart / Checkout Messages', 'wc_points_rewards' ),
				'desc'  => sprintf( __( 'Adjust the message by using %1$s{points}%2$s and %1$s{points_label}%2$s to represent the points earned / available for redemption and the label set for points.', 'wc_points_rewards' ), '<code>', '</code>' ),
				'type'  => 'title',
				'id'    => 'wc_points_rewards_messages_start'
			),

			// single product page message
			array(
				'title'    => __( 'Single Product Page Message', 'wc_points_rewards' ),
				'desc_tip' => __( 'Add an optional message to the single product page below the price. Customize the message using {points} and {points_label}. Limited HTML is allowed. Leave blank to disable.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_single_product_message',
				'css'      => 'min-width: 400px;',
				'default'  => sprintf( __( 'Purchase this product now and earn %s!', 'wc_points_rewards' ), '<strong>{points}</strong> {points_label}' ),
				'type'     => 'textarea',
			),

			// earn points cart/checkout page message
			array(
				'title'    => __( 'Earn Points Cart/Checkout Page Message', 'wc_points_rewards' ),
				'desc_tip' => __( 'Displayed on the cart and checkout page when points are earned. Customize the message using {points} and {points_label}. Limited HTML is allowed.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_earn_points_message',
				'css'      => 'min-width: 400px;',
				'default'  => sprintf( __( 'Complete your order and earn %s for a discount on a future purchase', 'wc_points_rewards' ), '<strong>{points}</strong> {points_label}' ),
				'type'     => 'textarea',
			),

			// redeem points cart/checkout page message
			array(
				'title'    => __( 'Redeem Points Cart/Checkout Page Message', 'wc_points_rewards' ),
				'desc_tip' => __( 'Displayed on the cart and checkout page when points are available for redemption. Customize the message using {points}, {points_value}, and {points_label}. Limited HTML is allowed.', 'wc_points_rewards' ),
				'id'       => 'wc_points_rewards_redeem_points_message',
				'css'      => 'min-width: 400px;',
				'default'  => sprintf( __( 'Use %s for a %s discount on this order!', 'wc_points_rewards' ), '<strong>{points}</strong> {points_label}', '<strong>{points_value}</strong>' ),
				'type'     => 'textarea',
			),

			array( 'type' => 'sectionend', 'id' => 'wc_points_rewards_messages_end' ),

			array(
				'title' => __( 'Points Earned for Actions', 'wc_points_rewards' ),
				'desc'  => __( 'Customers can also earn points for actions like creating an account or writing a product review. You can enter the amount of points the customer will earn for each action in this section.', 'wc_points_rewards' ),
				'type'  => 'title',
				'id'    => 'wc_points_rewards_earn_points_for_actions_settings_start'
			),

			array( 'type' => 'sectionend', 'id' => 'wc_points_rewards_earn_points_for_actions_settings_end' ),

			array(
				'type'  => 'title',
				'title' => __( 'Actions', 'wc_points_rewards' ),
				'id'    => 'wc_points_rewards_points_actions_start',
			),

			array(
				'title'       => __( 'Apply Points to Previous Orders', 'wc_points_rewards' ),
				'desc_tip'    => __( 'This will apply points to all previous orders and cannot be reversed.', 'wc_points_rewards' ),
				'button_text' => __( 'Apply Points', 'wc_points_rewards' ),
				'type'        => 'apply_points',
				'id'          => 'wc_points_rewards_apply_points_to_previous_orders'
			),

			array( 'type' => 'sectionend', 'id' => 'wc_points_rewards_points_actions_end' ),

		);

		$integration_settings = apply_filters( 'wc_points_rewards_action_settings', array() );

		if ( $integration_settings ) {

			// set defaults
			foreach ( array_keys( $integration_settings ) as $key ) {
				if ( ! isset( $integration_settings[ $key ]['css'] ) )  $integration_settings[ $key ]['css']  = 'max-width: 50px;';
				if ( ! isset( $integration_settings[ $key ]['type'] ) ) $integration_settings[ $key ]['type'] = 'text';
			}

			// find the start of the Points Earned for Actions settings to splice into
			$index = -1;
			foreach ( $settings as $index => $setting ) {
				if ( isset( $setting['id'] ) && 'wc_points_rewards_earn_points_for_actions_settings_start' == $setting['id'] )
					break;
			}

			array_splice( $settings, $index + 1, 0, $integration_settings );
		}

		return apply_filters( 'wc_points_rewards_settings', $settings );
	}


	/**
	 * Render the Earn Points/Redeem Points conversion ratio section
	 *
	 * @since 1.0
	 * @param array $field associative array of field parameters
	 */
	public function render_conversion_ratio_field( $field ) {
		global $woocommerce;

		if ( isset( $field['title'] ) && isset( $field['id'] ) ) :

			$ratio = get_option( $field['id'], $field['default'] );

			list( $points, $monetary_value ) = explode( ':', $ratio );

			?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for=""><?php echo wp_kses_post( $field['title'] ); ?></label>
						<img class="help_tip" data-tip="<?php echo esc_attr( $field['desc_tip'] ); ?>" src="<?php echo esc_url( $woocommerce->plugin_url() . '/assets/images/help.png' ); ?>" height="16" width="16" />
					</th>
					<td class="forminp forminp-text">
						<fieldset>
							<input name="<?php echo esc_attr( $field['id'] . '_points' ); ?>" id="<?php echo esc_attr( $field['id'] . '_points' ); ?>" type="text" style="max-width: 50px;" value="<?php echo esc_attr( $points ); ?>" />&nbsp;<?php _e( 'Points', 'wc_points_rewards' ); ?>
							<span>&nbsp;&#61;&nbsp;</span>&nbsp;<?php echo get_woocommerce_currency_symbol(); ?>
							<input name="<?php echo esc_attr( $field['id'] . '_monetary_value' ); ?>" id="<?php echo esc_attr( $field['id'] . '_monetary_value' ); ?>" type="text" style="max-width: 50px;" value="<?php echo esc_attr( $monetary_value ); ?>" />
						</fieldset>
					</td>
				</tr>
			<?php

		endif;
	}


	/**
	 * Render a singular-plural text field
	 *
	 * @since 0.1
	 * @param array $field associative array of field parameters
	 */
	public function render_singular_plural_field( $field ) {
		global $woocommerce;

		if ( isset( $field['title'] ) && isset( $field['id'] ) ) :

			$value = get_option( $field['id'], $field['default'] );

			list( $singular, $plural ) = explode( ':', $value );

			?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for=""><?php echo wp_kses_post( $field['title'] ); ?></label>
						<img class="help_tip" data-tip="<?php echo esc_attr( $field['desc_tip'] ); ?>" src="<?php echo esc_url( $woocommerce->plugin_url() . '/assets/images/help.png' ); ?>" height="16" width="16" />
					</th>
					<td class="forminp forminp-text">
						<fieldset>
							<input name="<?php echo esc_attr( $field['id'] . '_singular' ); ?>" id="<?php echo esc_attr( $field['id'] . '_singular' ); ?>" type="text" style="max-width: 50px;" value="<?php echo esc_attr( $singular ); ?>" />
							<input name="<?php echo esc_attr( $field['id'] . '_plural' ); ?>" id="<?php echo esc_attr( $field['id'] . '_plural' ); ?>" type="text" style="max-width: 50px;" value="<?php echo esc_attr( $plural ); ?>" />
						</fieldset>
					</td>
				</tr>
			<?php

		endif;
	}


	/**
	 * Save the Earn Points/Redeem Points Conversion Ratio field
	 *
	 * @since 1.0
	 * @param array $field
	 */
	public function save_conversion_ratio_field( $field ) {

		if ( isset( $_POST[ $field['id'] . '_points' ] ) && ! empty( $_POST[ $field['id'] . '_monetary_value' ] ) )
			update_option( $field['id'], $_POST[ $field['id'] . '_points' ]. ':' . $_POST[ $field['id'] . '_monetary_value' ] );
	}


	/**
	 * Save the singular-plural text fields
	 *
	 * @since 0.1
	 * @param array $field
	 */
	public function save_singular_plural_field( $field ) {

		if ( ! empty( $_POST[ $field['id'] . '_singular' ] ) && ! empty( $_POST[ $field['id'] . '_plural' ] ) )
			update_option( $field['id'], $_POST[ $field['id'] . '_singular' ]. ':' . $_POST[ $field['id'] . '_plural' ] );
	}


	/**
	 * Render the 'Apply Points to all previous orders' section
	 *
	 * @since 1.0
	 * @param array $field associative array of field parameters
	 */
	public function render_apply_points_section( $field ) {
		global $woocommerce;

		if ( isset( $field['title'] ) && isset( $field['button_text'] ) && isset( $field['id'] ) ) :

		?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="apply_points"><?php echo wp_kses_post( $field['title'] ); ?></label>
					<img class="help_tip" data-tip="<?php echo esc_attr( $field['desc_tip'] ); ?>" src="<?php echo esc_url( $woocommerce->plugin_url() . '/assets/images/help.png' ); ?>" height="16" width="16" />
				</th>
				<td class="forminp forminp-text">
					<fieldset>
						<a href="<?php echo add_query_arg( array( 'action' => 'apply_points' ) ); ?>" class="button" id="<?php echo $field['id'];?>"><?php echo esc_html( $field['button_text'] ); ?></a>
					</fieldset>
				</td>
			</tr>
		<?php

		endif;
	}


	/**
	 * Handles any points & rewards setting page actions.  The only available
	 * action is to apply points to previous orders, useful when the plugin
	 * is first installed
	 *
	 * @since 1.0
	 */
	public function handle_settings_actions() {

		global $wc_points_rewards;

		$current_tab     = ( empty( $_GET['tab'] ) )         ? null : sanitize_text_field( urldecode( $_GET['tab'] ) );
		$current_action  = ( empty( $_REQUEST['action'] ) )  ? null : sanitize_text_field( urldecode( $_REQUEST['action'] ) );

		if ( 'points' == $current_tab ) {

			if ( 'apply_points' == $current_action ) {

				// try and avoid timeouts as best we can
				@set_time_limit( 0 );

				// perform the action in manageable chunks
				$success_count  = 0;
				$offset         = 0;
				$posts_per_page = 500;

				do {

					// grab a set of order ids for existing orders with no earned points set
					$order_ids = get_posts( array(
						'post_type'      => 'shop_order',
						'fields'         => 'ids',
						'offset'         => $offset,
						'posts_per_page' => $posts_per_page,
						'meta_query' => array(
							array(
								'key'     => '_wc_points_earned',
								'compare' => 'NOT EXISTS'
							),
						)
					) );

					// some sort of database error
					if ( is_wp_error( $order_ids ) ) {
						$wc_points_rewards->admin_message_handler->add_error( __( 'Database error while applying user points.', 'wc_points_rewards' ) );

						return;
					}

					// otherwise go through the results and set the order numbers
					if ( is_array( $order_ids ) ) {
						foreach( $order_ids as $order_id ) {

							$order = new WC_Order( $order_id );

							// only add points to processing or completed orders
							if ( 'processing' === $order->status || 'completed' === $order->status ) {

								$wc_points_rewards->order->add_points_earned( $order );

								$success_count++;
							}
						}
					}

					// increment offset
					$offset += $posts_per_page;

				} while( count( $order_ids ) == $posts_per_page );  // while full set of results returned  (meaning there may be more results still to retrieve)

				// success message
				$wc_points_rewards->admin_message_handler->add_message( sprintf( _n( '%d order updated.', '%s orders updated.', $success_count, 'wc_points_rewards' ), $success_count ) );

			}

			if ( $wc_points_rewards->admin_message_handler->message_count() > 0 || $wc_points_rewards->admin_message_handler->error_count() > 0 ) {
				// display the result

				if ( $wc_points_rewards->admin_message_handler->error_count() > 0 )
					echo '<div id="message" class="error fade"><p><strong>' . esc_html( $wc_points_rewards->admin_message_handler->get_error( 0 ) ) . '</strong></p></div>';

				if ( $wc_points_rewards->admin_message_handler->message_count() > 0 )
					echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $wc_points_rewards->admin_message_handler->get_message( 0 ) ) . '</strong></p></div>';

			}
		}

	}


	/**
	 * Render the points earned / redeemed on the Edit Order totals section
	 *
	 * @since 1.0
	 * @param int $order_id the WC_Order ID
	 */
	public function render_points_earned_redeemed_info( $order_id ) {

		$points_earned   = get_post_meta( $order_id, '_wc_points_earned', true );
		$points_redeemed = get_post_meta( $order_id, '_wc_points_redeemed', true );

		?>
			<h4><?php _e( 'Points', 'wc_points_rewards' ); ?></h4>
			<ul class="totals">
				<li class="left">
					<label><?php _e( 'Earned:', 'wc_points_rewards' ); ?></label>
					<input type="number" disabled="disabled" id="_wc_points_earned" name="_wc_points_earned" placeholder="<?php _e( 'None', 'wc_points_rewards' ); ?>" value="<?php if ( ! empty( $points_earned ) ) echo esc_attr( $points_earned ); ?>" class="first" />
				</li>
				<li class="right">
					<label><?php _e( 'Redeemed:', 'wc_points_rewards' ); ?></label>
					<input type="number" disabled="disabled" id="_wc_points_redeemed" name="_wc_points_redeemed" placeholder="<?php _e( 'None', 'wc_points_rewards' ); ?>" value="<?php if ( ! empty( $points_redeemed ) ) echo esc_attr( $points_redeemed ); ?>" class="first" />
				</li>
			</ul>
			<div class="clear"></div>
		<?php
	}


	/**
	 * Render the points modifier field on the create/edit coupon page
	 *
	 * TODO: an even better action implementation would be ajax calls with a progress or activity indicator
	 *
	 * @since 1.0
	 */
	public function render_coupon_points_modifier_field() {

		// Unique URL
		woocommerce_wp_text_input(
			array(
				'id'          => '_wc_points_modifier',
				'label'       => __( 'Points Modifier', 'wc_points_rewards' ),
				'description' => __( 'Enter a percentage which modifies how points are earned when this coupon is applied. For example, enter 200% to double the amount of points typically earned when the coupon is applied.', 'wc_points_rewards' ),
				'desc_tip'    => true,
			)
		);
	}


	/**
	 * Save the points modifier field on the create/edit coupon page
	 *
	 * @since 1.0
	 * @param int $post_id the coupon post ID
	 */
	public function save_coupon_points_modifier_field( $post_id ) {

		if ( ! empty( $_POST['_wc_points_modifier'] ) )
			update_post_meta( $post_id, '_wc_points_modifier', stripslashes( $_POST['_wc_points_modifier'] ) );
		else
			delete_post_meta( $post_id, '_wc_points_modifier' );

	}

	/**
	 * Go through variations and store the max points
	 */
	public function variable_product_sync( $variation_id, $children ) {
		$wc_max_points_earned = '';
		foreach ( $children as $child ) {
			$earned = get_post_meta( $child, '_wc_points_earned', true );
			if ( $earned !== '' && $earned > $wc_max_points_earned ) {
				$wc_max_points_earned = $earned;
			}
		}
		update_post_meta( $variation_id, '_wc_max_points_earned', $wc_max_points_earned );
	}


} // end \WC_Points_Rewards_Admin class
