<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LaserCommerce_Admin extends WC_Settings_Page{
    private $_class = "LC_ADMIN_";

    /**
     * Constructs the settings page class, hooking into woocommerce settings api
     * 
     * @param $plugin The main instance of the lasercommerce plugin 
     */
    public function __construct( $plugin ) {
        $this->id            = 'lasercommerce';
        $this->label         = __('LaserCommerce', 'lasercommerce');
        $this->plugin        = $plugin;
        
        parent::__construct();
        // add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page' ), 20 );
        // add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output_sections' ) );
        // add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        // add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

        // add_action( 'admin_enqueue_scripts', array(&$this, 'nestable_init'));
        // add_action( 'woocommerce_settings_' . $this->id, array( $this, 'nestable_init' ) ); 
        $this->nestable_init();
        add_action( 'woocommerce_get_sections_' . $this->id, array( &$this, 'add_sections'));
        add_action( 'woocommerce_get_settings_' . $this->id, array( &$this, 'add_settings'), 10, 2);

        add_action( 'woocommerce_admin_field_tier_tree', array( $this, 'admin_field_tier_tree' ) );
        add_action( 'woocommerce_update_option_tier_tree', array( $this, 'tier_tree_save' ) );
    }   

    public function get_option( $option_name, $default ){
        return $this->plugin->getOption( $option_name, $default );
    }

    public function set_option( $option_name, $option_value ){
        return $this->plugin->updateOption( $option_name, $default );
    }

    /**
     * Initializes the nestable jquery functions responsible for the drag and drop 
     * functionality in the tier tree interface
     */
    public function nestable_init(){

        wp_register_script( 'jquery-nestable-js', plugins_url('js/jquery.nestable.js', dirname(__FILE__)), array('jquery'));
        wp_register_style( 'nestable-css', plugins_url('css/nestable.css', dirname(__FILE__)));
        wp_register_style( 'nestable-shaded-handle-css', plugins_url('css/nestable-shaded-handle.css', dirname(__FILE__)));

        wp_enqueue_script( 'jquery-nestable-js' );
        wp_enqueue_style( 'nestable-css' );
        wp_enqueue_style( 'nestable-shaded-handle-css' );

        global $wp_styles, $is_IE;
        wp_enqueue_style( 'prefix-font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', array(), '4.4.0' );
        if ( $is_IE ) {
            wp_enqueue_style( 'prefix-font-awesome-ie', '//netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome-ie7.min.css', array('prefix-font-awesome'), '4.4.0' );
            // Add IE conditional tags for IE 7 and older
            $wp_styles->add_data( 'prefix-font-awesome-ie', 'conditional', 'lte IE 7' );
        }
    } 
    
    /**
     * Overrides the get_sections() method of the WC_Settings Api
     * used by the api to generate the sections of the pages
     */
    public function add_sections( $sections ) {
        $_procedure = $this->_class."GET_SECTIONS: ";
        if(LASERCOMMERCE_DEBUG) error_log($_procedure);

        $sections[''] = __('Advanced Pricing and Visibility', LASERCOMMERCE_DOMAIN);
        
        return $sections;
    }
    
    /**
     * Filter that adds the settings for this page
     *
     * @param array $settings 
     * @param string $current_section 
     */
    public function add_settings( $settings, $current_section=null ) {
        $_procedure = $this->_class."GET_SETTINGS: ";
        if(LASERCOMMERCE_DEBUG) {
            error_log($_procedure."settings:".serialize($settings));
            error_log($_procedure."current_section:".serialize($current_section));
        }

        if( !$current_section ) { //Advanced Pricing and Visibility
            $settings[] = array( 
                'title' => __( 'LaserCommerce Advanced Pricing and Visibility Options', LASERCOMMERCE_DOMAIN ),
                'id'    => 'options',
                'type'  => 'title',
            );
            $settings[] = array(
                'name'  => 'Tier Key',
                'description' => __('They usermeta key that determines a users tier', LASERCOMMERCE_DOMAIN),
                'type'  => 'text',
                'id'    => 'tier_key'
            );
            $settings[] = array(
                'name'  => 'Tier Tree',
                'type'  => 'tier_tree',
                'description'   => __('Drag classes to here from "Available User Roles"', LASERCOMMERCE_DOMAIN),
                'id'    => 'tier_tree',
                'default' => '[{"id":"special_customer","children":[{"id":"wholesale_buyer","children":[{"id":"distributor","children":[{"id":"international_distributor"}]},{"id":"mobile_operator"},{"id":"gym_owner"},{"id":"salon"},{"id":"home_studio"}]}]}]'
            );
            $settings[] = array(
                'type' => 'sectionend',
                'id' => 'options'
            );
        } 

        return $settings;
        //TODO: sanitize price tiers
        //TODO: enter price tiers in table
    }    
    
    // /**
    //  * Used bt the WC_Settings_Api to output the fields in the settings array
    //  */
    // public function output() {
    //     global $current_section;
        
    //     if( !$current_section){ //Advanced Pricing and Visibility
    //         $settings = $this->get_settings();
        
    //         WC_Admin_Settings::output_fields($settings );
    //     }
    // }

    /**
     * Used by tier_tree_setting to recursively output the html for nestable fields 
     * this is the drag and drop field used in the configuration of the price tier
     * 
     * @param array $node The node of the tree to be displayed
     * @param array $names The array containing the mapping of roles to tier names
     */
    public function output_nestable_li($node) { 
        if(isset($node['id'])) {
            error_log("NODE: ".serialize($node));
            $node_id = $node['id'];
            $node_name_id = $node_id."_name";
            $node_major_id = $node_id."_major";
            // $node_name = isset($names[$node_id])?$names[$node_id]:$node_id; 
            $node_name = isset($node['name'])?$node['name']:$node_id; 
            $node_major = isset($node['major'])?$node['major']:false; 
            ?>
                <li 
                    class="dd-item" 
                    data-id="<?php echo $node_id; ?>" 
                    data-name="<?php echo $node_name?>" 
                    <?php if($node_major) echo "data-major" ?>
                >
                    <div class="dd-handle">
                        <!-- <i class="fa fa-grip"></i>
                        -->
                        <div class="lc_node_section lc_node_id">
                            <h3><?php echo $node_id; ?></h3>
                        </div>
                    </div>
                    <div class="dd-content">
                        <div class="lc_node_section lc_node_major">
                            <label for="<?php echo $node_major_id; ?>">Major</label>
                            <input 
                                id="<?php echo $node_major_id;?>" 
                                name="<?php echo $node_major_id;?>" 
                                class="lc_node lc_node_major"
                                data-updates="major"
                                type="checkbox" 
                                <?php if($node_major) echo "checked"; ?>
                            />
                        </div>
                        <div class="lc_node_section lc_node_name">
                            <label for="<?php echo $node_name_id;?>">Name</label>
                            <input 
                                id="<?php echo $node_name_id;?>" 
                                name="<?php echo $node_name_id;?>" 
                                class="lc_node lc_node_name"
                                type="text"
                                data-updates="name"
                                value="<?php echo $node_name; ?>"
                            />
                        </div>
                    </div>
                    <?php if(isset($node['children'])) { 
                    ?>
                        <ol class="dd-list">
                            <?php foreach( $node['children'] as $child ) {
                                $this->output_nestable_li($child); 
                            } ?>
                        </ol>
                    <?php } ?>
                </li>
            <?php 
        }
    }

    public function output_nestable($id, $tree){ 
        $nestable_id = $id.'_nestable';

    ?>
        <div class="dd dd-shaded-handle" id="<?php echo esc_attr($nestable_id); ?>">
            <?php 
                echo '<ol class="dd-list">';
                if($tree) {
                    foreach ($tree as $node) {
                        $this->output_nestable_li($node);
                    }
                } else {
                    echo '<div id="dd-empty-placeholder"></div>';
                }
                echo '</ol>';
            ?>
        </div>
        <textarea disabled rows=10 class="tier_tree_field" id="<?php echo esc_attr( $id ); ?>" style="width:100%; max-width:600px;" >
            <?php //echo esc_attr($option_value) ?>
        </textarea> 
        <script type="text/javascript">
;
(function ($) {
    $(document).ready(function()
    {

        console.log("calling nestable");
        var updateOutput = function (e){
            var list    = e.length ? e : $(e.target),
                output  = list.data('output');
                console.log(output);
            if (window.JSON) {
                output.val(window.JSON.stringify(list.nestable('serialize')));
            } else {
                output.val('JSON browser support required');
            }
        };
        var nestable_wrapper = $('.dd#'+<?php echo "'".esc_attr($nestable_id)."'";?>);
        var field_wrapper = $('.tier_tree_field#'+<?php echo "'".esc_attr( $id )."'"; ?>);
        // console.log(nestable_wrapper);
        // console.log(field_wrapper);
        nestable_wrapper.data( 
            'output',
            field_wrapper
        );
        nestable_wrapper.nestable({
            // group: 1,
            // includeContent: true
        })
        nestable_wrapper.on('change', updateOutput);

        updateOutput(nestable_wrapper); 

        nestable_wrapper
        .find('.dd-item input.lc_node')
        .on('change', function(e){
            // console.log("e: " + e);
            var input = e.length ? e : $(e.target)
            var val = input.val();
            // console.log("val: " + val);
            var li = input.parents('.dd-item').first();
            // console.log("li: " + li);
            // console.log("li.data('name'): " + li.data('name'));
            li.data('name', val);
            updateOutput(li)
            console.log(li.data('id') + " set to " + li.data('name'));
        });
    });        

})(jQuery); 
        </script>        
    <?php }
       
    /**
     * Used by the WC_Settings to output the price tiers setting html
     *
     */
    public function admin_field_tier_tree( $data ) {
        $_procedure = $this->_class."GEN_TT_HTML: ";

        $option_value = $this->get_option($data['id'], $data['default']);
        $field_description = WC_Admin_Settings::get_field_description( array(
            'desc' => $data['description'],
            'desc_tip' => '',
            'type'  => $data['type']
        ) );
        $description_html = $field_description['description'];
        $tooltip_html = $field_description['tooltip_html'];

        global $Lasercommerce_Tier_Tree;

        // $names = $Lasercommerce_Tier_Tree->getNames();
        // $tree = $Lasercommerce_Tier_Tree->getTierTree($option_value);
        $tree = $Lasercommerce_Tier_Tree->getTierTree();
        // $availableTiers = array_keys($names);
        // $usedTiers = $Lasercommerce_Tier_Tree->getTiers();
        // if(!$usedTiers){
        //     $unusedTiers = $availableTiers;
        // } else {
        //     $unusedTiers = array_diff($availableTiers, $usedTiers);
        // }

        if(LASERCOMMERCE_DEBUG) {
            error_log($_procedure."id:".                serialize($data['id']));
            error_log($_procedure."default:".           serialize($data['default']));
            error_log($_procedure."description_html:".  serialize($description_html));
            error_log($_procedure."tooltip_html:".      serialize($tooltip_html));
            error_log($_procedure."option_value:".      serialize($option_value));
            error_log($_procedure."tree: ".             serialize($tree));
            // error_log($_procedure."names: ".            serialize($names));
            // error_log($_procedure."availableTiers: ".   serialize($availableTiers));
            // error_log($_procedure."usedTiers: ".        serialize($usedTiers));
            // error_log($_procedure."unusedTiers: ".      serialize($unusedTiers));
        }

        $nestable_id = 'nestable-used';

        ob_start();
?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $data['id'] ) ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
        <?php echo $tooltip_html ?>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
            <?php 
                echo $description_html;
                $this->output_nestable('lc_tier_tree', $tree);
                // $unused_tree = array();
                // foreach( $unusedTiers as $tier ) {
                //     $unused_tree[] = array("id" => $tier);
                // }
                // $this->output_nestable($unused_tree, $names, 'nestable-unused');
            ?>
 
        </fieldset>
    </td>
</tr>
<?php
        $html = apply_filters( 'lasercommerce_tier_tree_html', ob_get_clean() );
        echo $html;
    }
    /**
     * used by WC_Settings API to save the price tiers field from $POST data
     * to the options in the database
     *
     * @param array $field The array specifying the field being saved
     */
    public function tier_tree_save( $field ){
        // if(WP_DEBUG) error_log('updating price tier! field: '.serialize($field).' POST '.serialize($_POST));
        if( isset( $_POST[ $field['id']]) ){
            // if(WP_DEBUG) error_log('updating option '.$field['id'].' as '.$_POST[$field['id']]);
            update_option( $field['id'], $_POST[$field['id']]);
        }
    }
    
    /** (Unfinished) Outputs a donate box section in the admin interface
     */
    public function donationBoxSection(){
        //todo: this
    }

    /**
     * Used by WC_Settings API to save all of the fields in the current section
      */
    public function save() {
        global $current_section;
        
        if( !$current_section ) {
            $settings = $this->get_settings();
            
            WC_Admin_Settings::save_fields( $settings );
        }
    }
}
?>