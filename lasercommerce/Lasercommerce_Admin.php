<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Lasercommerce_Tier_Tree.php');

//LaserCommerce Admin Page
class LaserCommerce_Admin extends WC_Settings_Page{
    public function __construct($optionNamePrefix = 'lasercommerce_') {
        $this->id            = 'lasercommerce';
        $this->label         = __('LaserCommerce', 'lasercommerce');
        $this->optionNamePrefix = $optionNamePrefix;
        $this->tierTree      = new Lasercommerce_Tier_Tree( $optionNamePrefix );
        
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page' ), 20 );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_admin_field_price_tiers', array( $this, 'price_tiers_setting' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
        add_action( 'woocommerce_update_option_price_tiers', array( $this, 'price_tiers_save' ) );
    }    
    
    public function get_sections() {
        $sections = array(
            '' => __('Advanced Pricing and Visibility', 'lasercommerce'),
        );
        
        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }
    
    public function get_settings( $current_section = "" ) {
        if( !$current_section ) { //Advanced Pricing and Visibility
            return apply_filters( 
                'woocommerce_lasercommerce_pricing_visibility_settings', 
                array(
                    array( 
                        'title' => __( 'LaserCommerce Advanced Pricing and Visibility Options', 'lasercommerce' ),
                        'id'    => $this->optionNamePrefix . 'options',
                        'type'  => 'title',
                    ),
                    array(
                        'name'  => 'Price Tiers',
                        'type'  => 'price_tiers',
                        'id'    => $this->optionNamePrefix . 'price_tiers',
                        'default' => 'hello2'
                    ),
                    array(
                        'type' => 'sectionend',
                        'id' => $this->optionNamePrefix . 'options'
                    )
                )
            );
        }
        //TODO: sanitize price tiers
        //TODO: enter price tiers in table
    }    
    
    public function output() {
        global $current_section;
        
        if( !$current_section){ //Advanced Pricing and Visibility
            $settings = $this->get_settings();
        
            WC_Admin_Settings::output_fields($settings );
        }
    }
       
    public function price_tiers_setting() {
        global $wp_roles;
        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles(); 
        
        $availableRoles = $wp_roles->get_names();
        $defaultRole = array('customer' => 'Customer');
        $priceTiers = $this->tierTree->getPriceTierNames();
        IF(WP_DEBUG) error_log("priceTiers: ".serialize($priceTiers));

        if(!$priceTiers){
            $unusedRoles = $availableRoles;
        } else {
            $unusedRoles = array_diff($availableRoles, $priceTiers, $defaultRole);
        }
    
        ?>
        <tr valign="top">
            <th colspan scope="row" class="titledesc">
                <?php _e('Price Tiers', 'lasercommerce'); ?>
            </th>
            <td>
                <table class="dl_price_tier widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="cb"></th>
                            <th class="role">
                                <?php _e('User Role', 'lasercommerce'); ?>
                            </th>
                            <th class="name">
                                <?php _e('Tier Name', 'lasercommerce'); ?>
                            </th>
                            <th class="parent"</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th colspan=4>
                                <select id="select_role">
                                    <option value="">Select a user role</option>
                                    <?php
                                        foreach($unusedRoles as $roleName => $displayName) {
                                            echo "<option value='$roleName'>$displayName</option>" ;
                                        }
                                    ?>
                                </select>
                                <a class="add button"> <?php _e('Add service', 'wootrack'); ?></a>
                                <a class="remove button"><?php _e('Remove selected services', 'wootrack'); ?></a>
                            </th>
                        </tr>
                    </tfoot>                                
                    <tbody>
                        <!-- first row -->
                        <tr class="lasercommerce firstrow">
                            <td class="cb"></th>
                            <td class="role">
                                <?php _e('Any', 'lasercommerce'); ?>
                            </td>
                            <td class="name">
                                <?php _e('Regular Price', 'woocommerce'); ?>
                            </td>
                            <td class="parent"></th>
                        </tr>
                        <?php
                            foreach($priceTiers as $role => $name){
                        ?>
                            <tr>
                                <td width="1%" class="cb">
                                    <?php echo "<input type='checkbox' id='cb_$role'>" ; ?>
                                </td>
                                <td class="role">
                                    <?php echo $role; ?>
                                </td>
                                <td class="name">
                                    <?php echo "<input type='text' id='name_$role' value='$name'> Price"; ?>
                                </td>
                                <td class="parent"></th>
                            </tr>
                        <?php
                            }
                        ?>   
                    </tbody>
                    <?php echo "<input type='hidden' name='".$this->optionNamePrefix."price_tiers' value='hello'>"; ?>
                    <script type="text/javascript">
                        <?php //todo: this ?>
                    </script>
                <table>
            <td>
        </tr>
                        
        <?php
    }
    
    public function price_tiers_save(){
        //todo: this
    }
    
    public function donationBoxSection(){
        //todo: this
    }

    public function save() {
        global $current_section;
        
        if( !$current_section ) {
            $settings = $this->get_settings();
            
            WC_Admin_Settings::save_fields( $settings );
        }
    }
}
?>