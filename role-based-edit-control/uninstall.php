<?php
/**
 * Uninstall script for RBEC Role-Based Button Visibility Plugin
 * 
 * This file is executed when the plugin is deleted through the WordPress admin.
 * It cleans up all plugin data from the database.
 * 
 * @package RBEC_Role_Buttons
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean up plugin data
 */
function rbec_uninstall() {
    // Remove plugin options
    delete_option('rbec_version');
    
    // Remove any transients that might have been created
    delete_transient('rbec_cache');
    delete_transient('rbec_user_roles');
    
    // Clean up any custom user meta if we added any
    global $wpdb;
    
    // Remove any custom user meta that starts with our prefix
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            'rbec_%'
        )
    );
    
    // Remove any custom options that start with our prefix
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            'rbec_%'
        )
    );
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log uninstall for debugging purposes
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('RBEC Role-Based Edit Control Plugin: Uninstalled and cleaned up successfully');
    }
}

// Run the uninstall function
rbec_uninstall();
