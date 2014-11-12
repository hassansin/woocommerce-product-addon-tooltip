<?php

/*
  Plugin Name: Woocommerce Product Addon tooltip plugin
  Plugin URI: mailto:rezatxe@gmail.com
  Description: Woocommerce Product Addon tooltip plugin
  Author: hassansin
  Author URI: mailto:rezatxe@gmail.com
  Version: 1.0.0
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

//if (!isset($_SESSION))
//   session_start();

WoocommerceAddonTooltip::instance();

class WoocommerceAddonTooltip {

    protected $slug = 'woocommerce_product_addon_tooltip';
    protected $name = 'Woocommerce Product Addon tooltip';
    protected $shortcode = array( '');
    protected $parentPage = 'options-general.php'; //leave it empty to creat new menu page
    protected $cronInterval = ''; //specify interval in seconds
    protected $defaultOptions = array(
        'version' => '1.0.0'        
    );
    protected $optionName;
    protected $options = array();
    protected $basename;
    protected $plugindir;
    protected $pluginurl;
    private static $_inst;

    public function __construct() {

        $this->plugindir = realpath( dirname( __FILE__ ) );
        $this->pluginurl = plugin_dir_url( __FILE__ );
        $this->basename = plugin_basename( __FILE__ );
        $this->optionName = $this->slug . '-options'; //plugin options        
        $this->add_actions();
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
        $this->options = get_option( $this->optionName );
    }

    private function add_actions() {
        add_action( 'admin_menu', array( &$this, "add_menu" ) );
        add_action( 'woocommerce_product_write_panels', array( $this, 'panel' ),20);
        add_action( 'woocommerce_process_product_meta', array( $this, 'process_meta_box'), 2, 2 );
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display' ), 10 );
        //add_action( 'admin_head', array( &$this, 'admin_enqueue_scripts' ) );
        //add_action( 'wp_enqueue_scripts', array( &$this, 'public_enqueue_scripts' ) );
        //add_action( 'init', array( &$this, 'ajax_init' ) );
        add_action( 'init', array( &$this, 'route' ) );
        //add_action( 'widgets_init', array( &$this, 'registerWidget' ) );

        /*if ( $this->cronInterval ) {
            add_filter( 'cron_schedules', array( $this, 'create_custom_schedule_event' ) );
            add_action( $this->slug . '_cron', get_class() . '::cron_handler' );
        }*/

        if ( isset( $this->shortcode ) ) {
            foreach ( $this->shortcode as $code )
                add_shortcode( $code, array( $this, 'shortcode_hook' ) ); //register all shortcodes
        }
    }
    public function display( $post_id = false, $prefix = false ) {
        global $product;

        if ( ! $post_id ) {
            global $post;
            $post_id = $post->ID;
        }                
        $product_addons_tooltips = get_post_meta( $post_id, '_product_addons_tooltip', TRUE );

        if ( is_array( $product_addons_tooltips ) && sizeof( $product_addons_tooltips ) > 0 ) {            

            ?>
      <dl id="addon-tabs" class="tabs vertical" data-tab>        
      </dl>
      <div id="addon-tabs-content" class="tabs-content vertical">                
      </div>
<div class="clearfix"></div>  
<style type="text/css">

    .woocommerce form #addon-tabs-content .form-row label{display: inline}
    #addon-tabs{display: none;}
    #addon-tabs-content > .content{display: block;}
    @media only screen and (min-width: 40rem){
        #addon-tabs-content > .content{display: none;}
        #addon-tabs-content > .content.active{display: block;}
        #addon-tabs{display: block;}
        #addon-tabs dd > a{padding:15px 10px;}
        #addon-tabs.vertical{width: 40%}
        #addon-tabs-content.vertical{width: 60%;}    
    }    
    #tooltip
    {
        text-align: center;
        color: #fff;
        background: #111;
        position: absolute;
        z-index: 100;
        padding: 15px;
    }
 
    #tooltip:after /* triangle decoration */
    {
        width: 0;
        height: 0;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-top: 10px solid #111;
        content: '';
        position: absolute;
        left: 50%;
        bottom: -10px;
        margin-left: -10px;
    }
 
    #tooltip.top:after
    {
        border-top-color: transparent;
        border-bottom: 10px solid #111;
        top: -20px;
        bottom: auto;
    }

    #tooltip.left:after
    {
        left: 10px;
        margin: 0;
    }

    #tooltip.right:after
    {
        right: 10px;
        left: auto;
        margin: 0;
    }
</style>
<script type="text/javascript">
    //hide all checkbox addons
    //jQuery('.addon-checkbox').parents('.product-addon');

    var product_addons_tooltips  = JSON.parse(<?php echo json_encode(json_encode($product_addons_tooltips,JSON_HEX_APOS))?> );    
    jQuery(function(){
        jQuery('.product-addon').each(function(i,e){            

            //tooltip
            var tooltips = '';
            jQuery.each(product_addons_tooltips,function(ii,ele){
                if(ele.position == i){
                    tooltips = ele.options;
                    return false;
                }                
                return true;
            });
            if(tooltips){
                jQuery(e).find('.addon').each(function(ii,ele){
                    if(tooltips[ii].tooltip)
                        jQuery(ele).parents('label').attr('rel','tooltip').attr('title',tooltips[ii].tooltip);
                });
            } 

            //tab
            if(jQuery(e).find('input.addon[type="checkbox"]').length){
                var title = jQuery(e).find('h3').html();
                var content = jQuery(e).detach().html();
                var active = i==0?'active':'';
                jQuery('<div class="content '+active+'" id="addon'+i+'"></div>').html(content).appendTo('#addon-tabs-content')
                jQuery('<dd class="'+active+'"><a href="#addon'+i+'">'+title+'</a></dd>').appendTo('#addon-tabs');
            }
        });
        //http://osvaldas.info/elegant-css-and-jquery-tooltip-responsive-mobile-friendly
        var targets = jQuery( '[rel~=tooltip]' ),
            target  = false,
            tooltip = false,
            title   = false;
     
        targets.bind( 'mouseenter', function()
        {
            target  = jQuery( this );
            tip     = target.attr( 'data-title' ) || target.attr( 'title' );
            tooltip = jQuery( '<div id="tooltip"></div>' );
     
            if( !tip || tip == '' )
                return false;
     
            target.removeAttr( 'title' );
            if(!target.attr('data-title'))            
                target.attr('data-title',tip);

            tooltip.css( 'opacity', 0 )
                   .html( tip )
                   .appendTo( 'body' );
     
            var init_tooltip = function()
            {
                if( jQuery( window ).width() < tooltip.outerWidth() * 1.5 )
                    tooltip.css( 'max-width', jQuery( window ).width() / 2 );
                else
                    tooltip.css( 'max-width', 340 );
     
                var pos_left = target.offset().left + ( target.outerWidth() / 2 ) - ( tooltip.outerWidth() / 2 ),
                    pos_top  = target.offset().top - tooltip.outerHeight() - 20;
     
                if( pos_left < 0 )
                {
                    pos_left = target.offset().left + target.outerWidth() / 2 - 20;
                    tooltip.addClass( 'left' );
                }
                else
                    tooltip.removeClass( 'left' );
     
                if( pos_left + tooltip.outerWidth() > jQuery( window ).width() )
                {
                    pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
                    tooltip.addClass( 'right' );
                }
                else
                    tooltip.removeClass( 'right' );
     
                if( pos_top < 0 )
                {
                    var pos_top  = target.offset().top + target.outerHeight();
                    tooltip.addClass( 'top' );
                }
                else
                    tooltip.removeClass( 'top' );
     
                tooltip.css( { left: pos_left, top: pos_top } )
                       .animate( { top: '+=10', opacity: 1 }, 50 );
            };
     
            init_tooltip();
            jQuery( window ).resize( init_tooltip );
     
            var remove_tooltip = function()
            {
                tooltip.animate( { top: '-=10', opacity: 0 }, 50, function()
                {
                    jQuery( this ).remove();
                });
                     
            };
     
            target.bind( 'mouseleave', remove_tooltip );
            tooltip.bind( 'click', remove_tooltip );
        });
    });
</script>            

            <?php
        }
    }
    public function process_meta_box( $post_id, $post ) {
        // Save addons as serialised array
        $product_addons                 = $this->get_posted_product_addons();
        update_post_meta( $post_id, '_product_addons_tooltip', $product_addons );
    }
    private function get_posted_product_addons() {
        $product_addons = array();

        if ( isset( $_POST[ 'product_addon_name' ] ) ) {
             $addon_name            = $_POST['product_addon_name'];          
             $addon_position        = $_POST['product_addon_position'];                      

             $addon_option_label    = $_POST['product_addon_option_label'];
             $product_addon_tooltip = $_POST['product_addon_tooltip'];

             for ( $i = 0; $i < sizeof( $addon_name ); $i++ ) {

                if ( ! isset( $addon_name[ $i ] ) || ( '' == $addon_name[ $i ] ) ) continue;

                $addon_options  = array();
                $option_label   = $addon_option_label[ $i ];
                $option_tooltip = $product_addon_tooltip[ $i ];

                for ( $ii = 0; $ii < sizeof( $option_label ); $ii++ ) {
                    $label  = sanitize_text_field( stripslashes( $option_label[ $ii ] ) );
                    $tooltip= str_replace('"', "'",stripslashes( $option_tooltip[ $ii ] ) ) ;

                    $addon_options[] = array(
                        'tooltip' => $tooltip
                    );
                }

                if ( sizeof( $addon_options ) == 0 )
                    continue; // Needs options

                $data                = array();
                $data['name']        = sanitize_text_field( stripslashes( $addon_name[ $i ] ) );                
                $data['position']    = absint( $addon_position[ $i ] );
                $data['options']     = $addon_options;              

                // Add to array
                $product_addons[] = apply_filters( 'woocommerce_product_addons_save_data', $data, $i );
            }
        }

        function addons_cmp( $a, $b ) {
            if ( $a['position'] == $b['position'] ) {
                return 0;
            }
            return ( $a['position'] < $b['position'] ) ? -1 : 1;
        }

        uasort( $product_addons, 'addons_cmp');

        return $product_addons;
    }

    public function panel(){
    global $post;
    $product_addons_tooltips = array_filter( (array) get_post_meta( $post->ID, '_product_addons_tooltip', true ) );

          ?>          
<script type="text/javascript">        
    jQuery(function(){      
        var product_addons_tooltips  = JSON.parse(<?php echo json_encode(json_encode($product_addons_tooltips,JSON_HEX_APOS))?> );
        jQuery('.woocommerce_product_addon').each(function(i,e){
            var tooltips = '';
            jQuery.each(product_addons_tooltips,function(ii,ele){
                if(ele.position == i){
                    tooltips = ele.options;
                    return false;
                }                
                return true;
            });

            jQuery(e).find('td.data table thead tr th:nth-child(5)').before('<th class="tooltip_column">Tooltip</th>');
            jQuery(e).find('td.data table tbody tr').each(function(ii,ele){
                var value = tooltips[ii] ? tooltips[ii].tooltip : '';
                jQuery(ele).find('td:nth-child(5)').before('<td class="tooltip_column"><input type="text" name="product_addon_tooltip['+i+'][]" value="'+value+'" placeholder="Tooltip" /></td>');
            })            
        });

        jQuery('#product_addons_data')      
        .on( 'click', 'button.add_addon_option', function() {           
            var loop = jQuery(this).closest('.woocommerce_product_addon').index('.woocommerce_product_addon');
            jQuery(this).closest('table').find('tbody tr:last td:nth-child(5)').before('<td class="tooltip_column"><input type="text" name="product_addon_tooltip['+loop+'][]" value="" placeholder="Tooltip" /></td>');
        })
        .on( 'click', '.add_new_addon', function() {
            var tr = jQuery('.woocommerce_product_addon:last td.data table thead tr');
            if(tr.children().length==5){
                tr.find('th:nth-child(5)').before('<th class="tooltip_column">Tooltip</th>');
            }
        });
    });
</script>



<?php
    }
    public function plugin_action_links( $links, $file ) {

        if ( basename( $file ) == basename( __FILE__ ) ) {
            //$this->log->logInfo('PluginActionLinks',$this->pluginActionLinks);
            foreach ( $this->pluginActionLinks as $link )
                $links[] = '<a href="'.$this->get_url( $link['page'], $link['action'] ).'">'.$link['title'].'</a>';
        }
        return $links;

    }
    public function registerWidget() {

        $dir = @opendir( $this->plugindir . '/widgets/' );
        if ( $dir ) {
            $widgets = array();
            while ( ( $entry = @readdir( $dir ) ) !== false ) {
                if ( strrchr( $entry, '.' ) === '.php' ) {
                    require_once $this->plugindir . '/widgets/' . $entry;
                    $class = substr( $entry, 0, -4 ); // remove .php and get class name
                    register_widget( $class );
                }
            }
        }


    }

    public function add_menu() {
        $parent_slug = $this->parentPage;
        //add separate menu option for all files in controllers folder
        $dir = @opendir( $this->plugindir . '/controllers/' );

        if ( $dir ) {
            $menus = array();
            $this->pluginActionLinks=array();
            while ( ( $entry = @readdir( $dir ) ) !== false ) {
                if ( strrchr( $entry, '.' ) === '.php' ) {
                    require_once $this->plugindir . '/controllers/' . $entry;
                    $class = substr( $entry, 0, -4 ); // remove .php and get class name
                    $instance = new $class();
                    $return = $instance->menuoptions; //
                    //add class name as slug i.e admin.php?page={$class} - for routing purpose
                    $return['slug'] = $class;
                    $return['capability'] = isset( $return['capability'] )?$return['capability']:'manage_options';
                    //check if plugin action link is set to true
                    if ( isset( $return['pluginActionLink'] ) && is_array( $return['pluginActionLink'] ) )
                        $this->pluginActionLinks[]= array_merge( $return['pluginActionLink'], array( 'page'=>$class ) );
                    $menus[] = $return;
                }
            }
            if(empty($menus))
                return;

            usort( $menus, array( $this, 'sort_menus' ) ); //sort menu options by 'order' key

            add_action( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );

            if ( !$parent_slug ) {
                $parent = array_shift( $menus ); //remove first element. It will be used as parent page
                add_menu_page( $this->name, $parent['menu_title'], 'manage_options', $parent['slug'] );
                add_submenu_page( $parent['slug'], $parent['page_title'], $parent['menu_title'], $parent['capability'], $parent['slug'], array( $this, 'menuCallback' ) );
                $parent_slug = $parent['slug'];
            }
            //add submenu page for the rest
            foreach ( $menus as $menu )
                add_submenu_page( $parent_slug, $menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['slug'], array( $this, 'menuCallback' ) );
            @closedir( $dir );
        }
    }

    public function admin_enqueue_scripts() {
        global $hook_suffix; // check this variable first and then use accordingly

    }

    //handles all ajax calls
    public function ajax_handler() {
        //is there any security breach??        
        $controller = $_GET['controller'];
        $method = $_GET['method'];
        if ( file_exists( $this->plugindir . '/controllers/' . $controller . '.php' ) ) {
            require_once $this->plugindir . '/controllers/' . $controller . '.php';
            $class = ucfirst( $controller );
            $instance = new $class();
            $instance->$method();
        }
        exit;
    }

    public function create_custom_schedule_event( $schedules ) {
        $schedules[$this->slug . '_scheduler'] = array(
            'interval' => $this->cronInterval,
            'display' => __( $this->name . ' Scheduler' )
        );
        return $schedules;
    }


    public function public_enqueue_scripts() {
        wp_enqueue_script( 'jquery' );
        //wp_register_script('jquery-ui', 'http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'jquery');
        //wp_enqueue_script('jquery-ui');
        // wp_enqueue_script('my-script', plugins_url('my-script.js', __FILE__), array('jquery'), '1.0', true);
        //wp_enqueue_style($this->slug . '-style', $this->pluginurl . 'css/' . 'style.css');
    }

    static function instance() {

        if ( !isset( self::$_inst ) ) {
            $className = __CLASS__;
            self::$_inst = new $className;
        }
        return self::$_inst;
    }

    public function activate_plugin() {

        if ( isset( $this->cronInterval ) )
            wp_schedule_event( time(), $this->slug . '_scheduler', $this->slug . '_cron' );

        if ( ( $old_option = get_option( $this->optionName ) ) ) { //if option already exists
            //merge new options and keep the values of old options.
            $merged_option = array_merge( $this->defaultOptions, $old_option );
            update_option( $this->optionName, $merged_option );
        }
        else {
            add_option( $this->optionName, $this->defaultOptions );
        }


        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;
        /*
          $table = $wpdb->prefix . 'calculator_builder';
          $sql = "CREATE TABLE $table (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          name VARCHAR(50) DEFAULT '' NOT NULL,
          html TEXT NOT NULL,
          created TIMESTAMP  DEFAULT CURRENT_TIMESTAMP  NOT NULL,
          UNIQUE KEY id (id)
          );";
          dbDelta($sql);
         *
         */
    }

    public function deactivate_plugin() {
        //delete_option($this->optionName);
    }

    
    public function cron_handler() {

    }

    public function shortcode_hook( $attr, $content=null, $tag ) {

        //load scripts that is only used by this shortcode
        //wp_enqueue_script('my-script', plugins_url('my-script.js', __FILE__), array('jquery'), '1.0', true);
        
        /*
    $cache_file = $this->plugindir.'/cache/mfc_live_feed.csv';
        $cachefile_created = (file_exists($cache_file)) ? @filemtime($cache_file) : 0;          

        if ( (time() - $this->get('cache_duration')) < $cachefile_created ) {
            $csv = file_get_contents($cache_file);
        }else{
            $csv = file_get_contents($this->get('feed_url'));
            $csv = mb_convert_encoding($csv,'UTF-8', 'ISO-8859-1');
            $fp = fopen($cache_file, 'w');  
            fwrite($fp, $csv);  
            fclose($fp);                        
        }
    */
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $this->get('feed_url'));          
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
        $csv= curl_exec($ch); 
        $csv = mb_convert_encoding($csv,'UTF-8', 'ISO-8859-1');
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        curl_close($ch); 

        $lines = array_filter(split("\r\n", $csv));
        $data = array();
        $manufacturers = array();
        foreach ($lines as $line) {
            $cols = $this->str_getcsv ( $line, "|" , "");
            $data[] = $cols;
            if(!in_array(trim($cols[16]), $manufacturers))        
                $manufacturers[] = trim($cols[16]);
        }
        $manufacturers = array_filter($manufacturers);
        sort($manufacturers);

        function cmp($a, $b){return strcmp($a[1],$b[1]);}
        usort($data, "cmp");
        $title = "Contract Hire & Leasing Special Offers";        
        
        if(isset($_GET['manufacturer'])){
            $manufacturer = $_GET['manufacturer'];
            $title = "Special Offers - ". ucwords($manufacturer);
            $items = array();            
            foreach ($data as $value) {
                if(trim(strtolower($value[16])) == $manufacturer){
                    $items[] = $value;
                }
            }
        }
        elseif(isset($_GET['price'])){
            $price_range = split('_', $_GET['price']);
            $low = $price_range[0];
            $high = $price_range[1];

            $title = "Deals by Price ";
            if($low=="0")
                $title .= "Upto £".$high ;
            elseif($high)
                $title .= "£".$low.' To £'.$high;
            elseif(!$high)
                $title .= "Over ".$low."£";

            $title .=" + VAT";

            $items = array();            
            foreach ($data as $value) {                
                $price = $value[6];

                if($high && $price<$high && $price > $low ){
                    $items[] = $value;
                }
                elseif(!$high && $price>$low){
                    $items[] = $value;
                }
            }   
        }
        else{
            $items = $data;
        }

        $price_ranges = array(
            '0_150' => array(0,150),
            '150_250' => array(150,250),
            '250_350' => array(250,350),
            '350_450' => array(350,450),
            '450_550' => array(450,550),
            '550'     => array(550)
            );        
        $current_uri = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);

        ob_start();        
        include $this->plugindir.'/views/template_page.php';
        $html = ob_get_contents();
        ob_clean();        

        //$html = "<pre>".print_r($data,true)."</pre>";
        return $content.  $html;
    }


    //embed ajax object in admin and public pages
    public function ajax_init() {

        $jsObject = array( 'ajaxurl' => admin_url( 'admin-ajax.php' ).'?action='.$this->slug);           
        wp_localize_script( 'jquery', $this->slug, $jsObject );
        // this hook is fired if the current viewer is not logged in
        add_action( 'wp_ajax_nopriv_'.$this->slug, array( &$this, 'ajax_handler' ) );
        add_action( 'wp_ajax_'.$this->slug, array( &$this, 'ajax_handler' ) );
    }

    //process post request or any other processing required to do during initialization
    public function route() {
        if ( !isset( $_POST[$this->slug . '_controller'] ) || !isset( $_POST[$this->slug . '_method'] ) )
            return;
        $controller = $_POST[$this->slug . '_controller']; //controller name
        $action = $_POST[$this->slug . '_method'];

        if ( file_exists( $this->plugindir . '/controllers/' . $controller . '.php' ) ) {
            require_once $this->plugindir . '/controllers/' . $controller . '.php';
            $class = ucfirst( $controller );
            $controller = new $class();
            if ( method_exists( $controller, $action ) === false ) {
                die( 'Action doesn\'t exists' );
            }
            $controller->$action();
        }
        else {
            die( 'Controller doesn\'t exists' );
        }
    }

    //sort menu pages
    public function sort_menus( $a, $b ) {

        return $a['order'] - $b['order'];
    }

    //generates admin menu pages
    public function menuCallback( $page='', $action='' ) {
        if ( !$page )
            $page = $_GET['page']; //controller name
        if ( !$action )
            $action = isset( $_GET['action'] ) ? $_GET['action'] : 'index'; //method name

        if ( file_exists( $this->plugindir . '/controllers/' . $page . '.php' ) ) {
            require_once $this->plugindir . '/controllers/' . $page . '.php';
            $class = ucfirst( $page );
            $controller = new $class();
            $controller->$action();
        }
        else {
            echo _e( 'controller not found' );
        }
    }

    //template parser  from view folder
    protected function render( $template, $data=array(), $echo=true ) {

        $file = $this->plugindir . '/views/' . $template . '.php';
        if ( !file_exists( $file ) ) {
            if ( $echo ) {
                echo 'View file doesn\'t exists';
                return;
            }
            else
                return 'View file doesn\'t exists';
        }
        extract( $data );
        if ( $echo ) {
            include $file;
        }
        else {
            ob_start();
            include $file;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }

    //returns url for admin page
    protected function get_url( $page='', $action='' ) {
        if ( !empty( $this->parentPage ) )
            $url = admin_url() . $this->parentPage;
        else
            $url = admin_url() . 'admin.php';

        if ( $page )
            $url .= '?page=' . $page;
        if ( $action )
            $url .='&action=' . $action;
        return $url;
    }

    //save a single option in options array save('version','1.0.1');
    protected function save( $optionName, $optionval ) {
        if ( key_exists( $optionName, $this->options ) ) {
            $this->options[$optionName] = $optionval;
            update_option( $this->optionName, $this->options );
            return true;
        }
        return false;
    }

    //get a sigle option value eg get('version');
    protected function get( $optionName ) {
        if ( isset( $this->options[$optionName] ) ) {
            return $this->options[$optionName];
        }
        return false;
    }
    protected function str_getcsv($input, $delimiter = ',', $enclosure = '"') {

        return explode($delimiter,$input);

    }

    //enable and display errors


}

?>