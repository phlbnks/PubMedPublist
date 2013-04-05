<?php
/*
Plugin Name: PubMed Publist
Description: Shortcodes to display a list of publications from PubMed
Version: 0.9.1
Author: Phil Banks
*/


/**
 * Define some useful constants
 **/
define('pm_publist_VERSION', '1.0');
define('pm_publist_DIR', plugin_dir_path(__FILE__));
define('pm_publist_URL', plugin_dir_url(__FILE__));



/**
 * Load files
 *
 **/
function pm_publist_load(){

    if(is_admin()) //load admin files only in admin
        require_once(pm_publist_DIR.'includes/admin.php');
    require_once(pm_publist_DIR.'includes/core.php');
}

pm_publist_load();



/**
 * Activation, Deactivation and Uninstall Functions
 *
 **/
register_activation_hook(__FILE__, 'pm_publist_activation');
register_deactivation_hook(__FILE__, 'pm_publist_deactivation');


function pm_publist_activation() {

	//actions to perform once on plugin activation go here


    //register uninstaller
    register_uninstall_hook(__FILE__, 'pm_publist_uninstall');
}

function pm_publist_deactivation() {

	// actions to perform once on plugin deactivation go here

}

function pm_publist_uninstall(){

    //actions to perform once on plugin uninstall go here

}


?>
