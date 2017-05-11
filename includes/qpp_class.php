<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Plugin main class that will control the whole skeleton and common functions
 *
 * PHP version 5
 *
 * @category   Main
 * @package    Qibla Payment Plan
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class QPP
{
     
    //Plugin starting point. Will call appropriate actions
    public function __construct() {

        add_action( 'plugins_loaded', array( $this, 'qpp_init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'qpp_enqueue_scripts' ), 10 );
        add_action( 'admin_enqueue_scripts', array( $this, 'qpp_enqueue_scripts' ), 10 );        
    }

    //Plugin initialization
    public function qpp_init() {

        do_action('qpp_before_init');
        
        if(is_admin()){            
            require_once QPP_PLUGIN_PATH.'qpp_admin.php';            
        }
        
        require_once QPP_PLUGIN_PATH.'qpp_front.php';
        
        do_action('qpp_after_init');
    }
    
    //Function will add CSS and JS files
    public function qpp_enqueue_scripts() {
        
        do_action('qpp_before_enqueue_scripts');
        
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'qpp_js', QPP_PLUGIN_URL.'js/qpp.js', array( 'jquery' ), time() );
        wp_enqueue_style( 'qpp_css', QPP_PLUGIN_URL.'css/qpp.css', array(), time() );
        
        do_action('qpp_after_enqueue_scripts');
    }
    
    public function add_filter_once( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	global $_gambitFiltersRan;

	if ( ! isset( $_gambitFiltersRan ) ) {
            $_gambitFiltersRan = array();
	}

	// Since references to $this produces a unique id, just use the class for identification purposes
	$idxFunc = $function_to_add;
	if ( is_array( $function_to_add ) ) {
            $idxFunc[0] = get_class( $function_to_add[0] );
	}
	$idx = _wp_filter_build_unique_id( $tag, $idxFunc, $priority );

	if ( ! in_array( $idx, $_gambitFiltersRan ) ) {
            add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}

	$_gambitFiltersRan[] = $idx;

	return true;
    }
    
    public function add_action_once( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	global $_gambitActionsRan;

	if ( ! isset( $_gambitActionsRan ) ) {
            $_gambitActionsRan = array();
	}

	// Since references to $this produces a unique id, just use the class for identification purposes
	$idxFunc = $function_to_add;
	if ( is_array( $function_to_add ) ) {
            $idxFunc[0] = get_class( $function_to_add[0] );
	}
	$idx = _wp_filter_build_unique_id( $tag, $idxFunc, $priority );

	if ( ! in_array( $idx, $_gambitActionsRan ) ) {
            add_action( $tag, $function_to_add, $priority, $accepted_args );
	}

	$_gambitActionsRan[] = $idx;

	return true;
    }
    
    public function qpp_add_record( $table = '', $data = array() ) {
        
        if( empty($data) || empty($table) ) {
            return false;
        }
        
        global $wpdb;
        $exclude = array( 'btnsave' );
        $attr = "";
        $attr_val = "";
        foreach( $data as $k=>$val ) {
            $val = $this->make_safe($val);
            if(is_array($val)) {
                $val = maybe_serialize($val);
            }
            $should_insert = true;
            foreach( $exclude as $v ) {
                $pos = strpos($k, $v);
                if ($pos !== false) {
                    $should_insert = false;
                    break;
                }
            }
            if( $should_insert ) {
                if( $attr == "" ) {
                    $attr.="`".$k."`";
                    $attr_val.="'".$val."'";
                }else{
                    $attr.=", `".$k."`";
                    $attr_val.=", '".$val."'";
                }                
            }
        }
        $sql = "INSERT INTO `".$wpdb->prefix.$table."` (".$attr.") VALUES (".$attr_val.")";
        $wpdb->query($sql);
        
        return true;
    }
    
    public function qpp_update_record( $table = '', $data = array(), $where = '' ) {
        
        if( empty($where) || empty($data) || empty($table) ) {
            return false;
        }
        
        global $wpdb;
        $exclude = array( 'id','btnsave' );
        $attr = "";
        foreach( $data as $k=>$val ) {
            $val = $this->make_safe($val);
            if(is_array($val)) {
                $val = maybe_serialize($val);
            }
            $should_insert = true;
            foreach( $exclude as $v ) {
                $pos = strpos($k, $v);
                if ($pos !== false) {
                    $should_insert = false;
                    break;
                }
            }
            if( $should_insert ) {
                if( $attr == "" ) {
                    $attr.="`".$k."` = '".$val."'";                    
                }else{
                    $attr.=", `".$k."` = '".$val."'";
                }                
            }
        }
        $sql = "UPDATE `".$wpdb->prefix.$table."` SET ".$attr." WHERE ".$where;
        
        $wpdb->query($sql);
        
        return true;
    }
    
    public function qpp_del_record( $table = '', $where = '' ) {
        
        if( empty($where) || empty($table) ) {
            return false;
        }
        
        global $wpdb;
        $sql = "DELETE FROM `".$wpdb->prefix.$table."` WHERE ".$where;
        $wpdb->query($sql);
        return true;
    }
    
    public function qpp_get_data( $table = '', $where = "1", $get_row = false ) {
        
        if( empty($table) ) {
            return false;
        }
        
        global $wpdb;
        
        $sql = "SELECT * FROM `".$wpdb->prefix.$table."` WHERE ".$where;
        if( $get_row ) {
            $data = $wpdb->get_row($sql);
        }else{
            $data = $wpdb->get_results($sql);
        }
        
        return $data;
    }
    
    public function qpp_number_encrypt($data, $key = 'geyktksYMZNQU8lRTRSAIMFWSF2csvsq2we', $base64_safe=true, $shrink=true) {
        if ($shrink) $data = base_convert($data, 10, 36);
        $data = @mcrypt_encrypt(MCRYPT_ARCFOUR, $key, $data, MCRYPT_MODE_STREAM);
        if ($base64_safe) $data = str_replace('=', '', base64_encode($data));
        return $data;
    }

    public function qpp_number_decrypt($data, $key = 'geyktksYMZNQU8lRTRSAIMFWSF2csvsq2we', $base64_safe=true, $expand=true) {
        if ($base64_safe) $data = base64_decode($data.'==');
        $data = @mcrypt_encrypt(MCRYPT_ARCFOUR, $key, $data, MCRYPT_MODE_STREAM);
        if ($expand) $data = base_convert($data, 36, 10);
        return $data;
    }
    
    public function make_safe( $variable ) {

        $variable = $this->strip_html_tags($variable);
        $bad = array("<", ">");
        $variable = str_replace($bad, "", $variable);
        
        return $variable;
    }

    public function strip_html_tags( $text ) {
        $text = preg_replace(
                array(
                  // Remove invisible content
                        '@<head[^>]*?>.*?</head>@siu',
                        '@<style[^>]*?>.*?</style>@siu',
                        '@<script[^>]*?.*?</script>@siu',
                        '@<object[^>]*?.*?</object>@siu',
                        '@<embed[^>]*?.*?</embed>@siu',
                        '@<applet[^>]*?.*?</applet>@siu',
                        '@<noframes[^>]*?.*?</noframes>@siu',
                        '@<noscript[^>]*?.*?</noscript>@siu',
                        '@<noembed[^>]*?.*?</noembed>@siu'
                ),
                array(
                        '', '', '', '', '', '', '', '', ''), $text );

        return strip_tags( $text);
    }

    // Function to safe redirect the page without warnings
    public function redirect( $url ) {
        echo '<script language="javascript">window.location.href="'.$url.'";</script>';
        exit();
    }
    
    //Function will get called on plugin activation
    static function qpp_install() {

        do_action('qpp_before_install');

        require_once QPP_PLUGIN_PATH.'includes/qpp_install.php';

        do_action('qpp_after_install');
    }

    // Function will get called on plugin de activation
    static function qpp_uninstall() {

        do_action('qpp_before_uninstall');

        require_once QPP_PLUGIN_PATH.'includes/qpp_uninstall.php';

        do_action('qpp_after_uninstall');
    }
}

$qpp = new QPP();