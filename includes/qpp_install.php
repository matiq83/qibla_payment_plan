<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class that will hold functionality for plugin activation
 *
 * PHP version 5
 *
 * @category   Install
 * @package    Qibla Payment Plan
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class QPP_Install extends QPP
{
    public function __construct() {
        
        do_action('qpp_before_install', $this );
        
        do_action('qpp_after_install', $this );
    }    
}

$qpp_install = new QPP_Install();