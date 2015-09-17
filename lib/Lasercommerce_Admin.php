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

        add_action('woocommerce_update_option', array(&$this, 'update_option_tier_tree'));

        $tier_tree_option_name = $this->prefix_option($this->get_tier_tree_key());
        add_filter( 'woocommerce_admin_settings_sanitize_option_'.$tier_tree_option_name, array(&$this, 'sanitize_option_tier_tree'), 10, 3 );
        // add_action( 'woocommerce_update_option_tier_tree', array( $this, 'tier_tree_save' ) );
    }   

    public function get_option( $option_name, $default ){
        return $this->plugin->getOption( $option_name, $default );
        // return WC_Admin_Settings::get_option( $option_name, $default );
    }

    public function set_option( $option_name, $option_value ){
        return $this->plugin->updateOption( $option_name, $default );
    }
    
    public function prefix_option( $option_name ){
        return $this->plugin->prefix( $option_name );
    }    

    public function unprefix_option( $option_name ){
        return $this->plugin->unPrefix( $option_name );
    }

    public function get_tier_key_key(){
        return $this->plugin->tier_key_key;
    }

    public function get_tier_tree_key(){
        return $this->plugin->tier_tree_key;
    }

    public function get_default_tier_key(){
        return $this->plugin->default_tier_key;
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
        $_procedure = $this->_class."ADD_SECTIONS: ";
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
        $_procedure = $this->_class."ADD_SETTINGS: ";
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
                'description' => __('The usermeta key that determines a users tier', LASERCOMMERCE_DOMAIN),
                'type'  => 'text',
                'id'    => $this->prefix_option($this->get_tier_key_key())
            );
            $settings[] = array(
                'name'  => 'Default Tier',
                'description' => __('The default tier assigned to a user', LASERCOMMERCE_DOMAIN),
                'type'  => 'text',
                'id'    => $this->prefix_option($this->get_default_tier_key())
            );
            $settings[] = array(
                'name'  => 'Tier Tree',
                'type'  => 'tier_tree',
                'description'   => __('Drag classes to here from "Available User Roles"', LASERCOMMERCE_DOMAIN),
                'id'    => $this->prefix_option($this->get_tier_tree_key()),
                'default' => '[{"major":true,"name":"Retail","id":"RN","children":[{"major":true,"name":"Wholesale","id":"WN","children":[{"major":true,"name":"Distributor","id":"DN"}]}]}]'
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

    public function output_nestable($id, $json){ 
        $nestable_id = $id.'_nestable';
        $trash_id = $id.'_trash';
        $factory_id = $id.'_factory';

    ?>
        <div class="dd dd-shaded-handle" id="<?php echo esc_attr($nestable_id); ?>">
            <ol class="dd-list">
                <div id="dd-empty-placeholder"></div>
            </ol>
            <?php 
                // echo '<ol class="dd-list">';
                // if($tree) {
                //     foreach ($tree as $node) {
                //         $this->output_nestable_li($node);
                //     }
                // } else {
                //     echo '<div id="dd-empty-placeholder"></div>';
                // }
                // echo '</ol>';
            ?>
        </div>
        <div class="nestable_factory" id="<?php echo esc_attr($factory_id); ?>">
            
        </div>
        <p><?php _e('Trash', LASERCOMMERCE_DOMAIN); ?></p>
        <div class="dd dd-shaded-handle" id="<?php echo esc_attr($trash_id); ?>">
            <div class="dd-empty"></div>
        </div>

        <textarea
            class="tier_tree_field" 
            type="text"
            id="<?php echo esc_attr( $id ); ?>" 
            name="<?php echo esc_attr( $id ); ?>" 
            <?php if(LASERCOMMERCE_DEBUG) {
                echo 'style="width:100%; max-width:600px;" ';
                echo 'rows=10 ';
            } else {
                echo 'style="display:hidden"';
            } ?>
        >
            <?php echo wp_kses_data($json) ?>
        </textarea> 
        <script type="text/javascript">
;
(function ($) {
    $(document).ready(function()
    {

        console.log("calling nestable");

        var nestable_json = <?php echo "'".($json)."'";?> ;
        var nestable_wrapper = $('.dd#'+<?php echo "'".esc_attr($nestable_id)."'";?>);
        var field_wrapper = $('.tier_tree_field#'+<?php echo "'".esc_attr( $id )."'"; ?>);
        var trash_wrapper = $('.dd#'+<?php echo "'".esc_attr($trash_id)."'";?>);

        var updateOutput = function(l, e){
            console.log("output closure called");
            var output = l.data('output');
            console.log("output");
            console.log(output);
            if(output === undefined) return;
            if (window.JSON) {
                output.val(window.JSON.stringify(l.nestable('serialize')));
            } else {
                output.val('JSON browser support required');
            }   
        }

        function buildItem(item) {

            var node_name_id = item.id + '_name';
            var node_name_value = (item.name ? item.name : '');
            var node_name_label = <?php echo "'".__('Name', LASERCOMMERCE_DOMAIN)."'"; ?>;
            var node_major_id = item.id + '_major';
            var node_major_value = (item.major ? item.major : '');
            var node_major_label = <?php echo "'".__('Major', LASERCOMMERCE_DOMAIN)."'"; ?>;
            var node_omniscient_id = item.id + '_omniscient';
            var node_omniscient_value = (item.omniscient ? item.omniscient : '');
            var node_omniscient_label = <?php echo "'".__('Omni', LASERCOMMERCE_DOMAIN)."'"; ?>;

            var html = '';
            html += '<li ';
            html += '    class="dd-item" ';
            html += '    data-id="' + item.id + '" ';
            html += '    data-name="' + node_name_value + '"';
            if(node_major_value) html += '    data-major="' + node_major_value + '"';
            if(node_omniscient_value) html += '    data-omniscient="' + node_omniscient_value + '"';
            html += '>';
            html += '    <div class="dd-handle">';
            html += '        <div class="lc_node_section lc_node_id">';
            html += '            <h3>' + item.id + '</h3>';
            html += '        </div>';
            html += '    </div>';
            html += '    <div class="dd-content">';
            html += '        <div class="lc_node_section lc_node_major">';
            html += '            <label for="'+ node_omniscient_id + '">' + node_omniscient_label + '</label>';
            html += '            <input ';
            html += '                id="'+ node_omniscient_id + '" ';
            html += '                class="lc_node lc_node_omniscient" ';
            html += '                data-updates="omniscient" ';
            html += '                type="checkbox" ';
            if(node_omniscient_value) html += 'checked';
            html += '            />';
            html += '        </div>';
            html += '        <div class="lc_node_section lc_node_major">';
            html += '            <label for="'+ node_major_id + '">' + node_major_label + '</label>';
            html += '            <input ';
            html += '                id="'+ node_major_id + '" ';
            html += '                class="lc_node lc_node_major" ';
            html += '                data-updates="major" ';
            html += '                type="checkbox" ';
            if(node_major_value) html += 'checked';
            html += '            />';
            html += '        </div>';
            html += '        <div class="lc_node_section lc_node_name">';
            html += '            <label for="'+ node_name_id + '">' + node_name_label + '</label>';
            html += '            <input ';
            html += '                id="' + node_name_id + '" ';
            html += '                class="lc_node lc_node_name"';
            html += '                type="text"';
            html += '                data-updates="name"';
            html += '                value="' + node_name_value + '"';
            html += '            />';
            html += '        </div>';
            html += '    </div>';

            if (item.children) {

                html += "<ol class='dd-list'>";
                $.each(
                    item.children, 
                    function (index, sub) {
                        html += buildItem(sub);
                    }
                );
                html += "</ol>";
            }
            html += "</li>";

            return html;
        }

        var output = '<ol class="dd-list">';
        $.each(JSON.parse(nestable_json), function (index, item) {
            output += buildItem(item);
        });
        output += '</ol>';
        nestable_wrapper.html(output);

        nestable_wrapper.nestable({
            group: 1,
            callback: updateOutput,
        })

        trash_wrapper.nestable({
            group: 1,
        })

        nestable_wrapper.data('output', field_wrapper);

        nestable_obj = nestable_wrapper.data('nestable');
        nestable_obj.options.callback.call(nestable_obj, nestable_obj.el)

        //code for updating the output when a user changes an input
        nestable_wrapper
        .find('.dd-item input.lc_node')
        .on(
            'change', 
            (function(nestable_wrapper){    
                return function(e){
                    var input = e.length ? e : $(e.target)
                    var val = (input.attr('type') === 'checkbox' ? input.is(':checked') : input.val() );
                    var li = input.parents('.dd-item').first();
                    var updates = input.data('updates');

                    if(updates){
                        if(!val) val = "";
                        li.data(updates, val);
                    }
                    // console.log(li.data('id') + " set " + updates + " to " + li.data(updates));

                    nestable_obj = nestable_wrapper.data('nestable');
                    nestable_obj.options.callback.call(nestable_obj, nestable_obj.el, this)
                };
            })(nestable_wrapper)
        )
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

        $option_value = $this->get_option($this->unprefix_option($data['id']), $data['default']);
        $field_description = WC_Admin_Settings::get_field_description( array(
            'desc' => $data['description'],
            'desc_tip' => '',
            'type'  => $data['type']
        ) );
        $description_html = $field_description['description'];
        $tooltip_html = $field_description['tooltip_html'];

        // global $Lasercommerce_Tier_Tree;

        // $names = $Lasercommerce_Tier_Tree->getNames();
        // $tree = $Lasercommerce_Tier_Tree->getTierTree($option_value);
        // $tree = $Lasercommerce_Tier_Tree->getTierTree();
        // $availableTiers = array_keys($names);
        // $usedTiers = $Lasercommerce_Tier_Tree->getTreeTiers();
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
            // error_log($_procedure."tree: ".             serialize($tree));
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
                $this->output_nestable($data['id'], $option_value);
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
        $_procedure = $this->_class."TT_SAVE: ";
        if(LASERCOMMERCE_DEBUG) error_log($_procedure.'updating price tier! field: '.serialize($field).' POST '.serialize($_POST));
        if( isset( $_POST[ $field['id']]) ){
            if(LASERCOMMERCE_DEBUG) error_log($_procedure.'updating option '.$field['id'].' as '.$_POST[$field['id']]);
            $this->set_option( $field['id'], $_POST[$field['id']]);
        }
    }
    
    /** (Unfinished) Outputs a donate box section in the admin interface
     */
    public function donationBoxSection(){
        //todo: this
    }

    public function sanitize_option_tier_tree($value, $option, $raw_value) {
        $_procedure = $this->_class."SANITIZE_OPTION_TT: ";
        error_log($_procedure."POST: ".serialize($_POST));
        error_log($_procedure."value: ".serialize($value));
        error_log($_procedure."option: ".serialize($option));
        error_log($_procedure."raw_value: ".serialize($raw_value));
        return sanitize_text_field($value);
    }

    public function update_option_tier_tree ($option){
        $_procedure = $this->_class."UPDATE_OPTION_TT: ";
        if ( $option['id'] == $this->prefix_option($this->get_tier_tree_key()) ){
            error_log($_procedure."updating option tier tree");
            error_log($_procedure."option: ".serialize($option));
        }
    }

    /**
     * Used by WC_Settings API to save all of the fields in the current section
      */
    // public function save() {
    //     global $current_section;
        
    //     if( !$current_section ) {
    //         $settings = $this->get_settings();
            
    //         WC_Admin_Settings::save_fields( $settings );
    //     }
    // }
}
?>