<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class that will hold functionality for plugin deactivation
 *
 * PHP version 5
 *
 * @category   Uninstall
 * @package    Qibla Payment Plan
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class QPP_Uninstall extends QPP
{
    public function __construct() {
        
        do_action('qpp_before_uninstall', $this );
        
        do_action('qpp_after_uninstall', $this );
    }    
}

$qpp_uninstall = new QPP_Uninstall();