<?php
/**
 * Role Configuration for RBEC Role-Based Button Visibility
 * 
 * This file contains the centralized configuration for which buttons
 * are visible to which user roles.
 * 
 * To add new roles or modify button visibility:
 * 1. Add your role to the $role_button_config array
 * 2. Set the appropriate visibility flags
 * 3. The system will automatically apply these rules
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Role-based button visibility configuration
 * 
 * Structure:
 * 'role_slug' => [
 *     'show_edit' => true/false,        // Show classic WordPress edit button
 *     'show_elementor' => true/false,   // Show "Edit with Elementor" button
 *     'description' => 'Role description for documentation'
 * ]
 */
$rbec_role_button_config = array(
    
    // Administrator - Full access to all buttons
    'administrator' => array(
        'show_edit' => true,
        'show_elementor' => true,
        'description' => 'Administrators can see all edit buttons'
    ),
    
    // Editor - Can edit but not with Elementor
    'editor' => array(
        'show_edit' => true,
        'show_elementor' => false,
        'description' => 'Editors can see classic edit button only'
    ),
    
    // Shop Manager - No edit buttons (view only)
    'shop_manager' => array(
        'show_edit' => false,
        'show_elementor' => false,
        'description' => 'Shop Managers have view-only access'
    ),
    
    // Author - No edit buttons (view only)
    'author' => array(
        'show_edit' => false,
        'show_elementor' => false,
        'description' => 'Authors have view-only access'
    ),
    
    // Contributor - No edit buttons (view only)
    'contributor' => array(
        'show_edit' => false,
        'show_elementor' => false,
        'description' => 'Contributors have view-only access'
    ),
    
    // Subscriber - No edit buttons (view only)
    'subscriber' => array(
        'show_edit' => false,
        'show_elementor' => false,
        'description' => 'Subscribers have view-only access'
    )
    
    // ADD NEW ROLES HERE
    // Example:
    // 'custom_role' => array(
    //     'show_edit' => true,
    //     'show_elementor' => false,
    //     'description' => 'Custom role description'
    // ),
);

/**
 * Get role configuration for a specific role
 * 
 * @param string $role Role slug
 * @return array|false Role configuration or false if role not found
 */
function rbec_get_role_button_config($role) {
    global $rbec_role_button_config;
    
    if (isset($rbec_role_button_config[$role])) {
        return $rbec_role_button_config[$role];
    }
    
    // Default configuration for unknown roles (conservative approach)
    return array(
        'show_edit' => false,
        'show_elementor' => false,
        'description' => 'Unknown role - defaulting to view-only access'
    );
}



/**
 * Get all roles that can see edit button
 * 
 * @return array Array of role slugs
 */
function rbec_get_roles_with_edit_access() {
    global $rbec_role_button_config;
    $roles = array();
    
    foreach ($rbec_role_button_config as $role => $config) {
        if ($config['show_edit']) {
            $roles[] = $role;
        }
    }
    
    return $roles;
}

/**
 * Get all roles that can see Elementor button
 * 
 * @return array Array of role slugs
 */
function rbec_get_roles_with_elementor_access() {
    global $rbec_role_button_config;
    $roles = array();
    
    foreach ($rbec_role_button_config as $role => $config) {
        if ($config['show_elementor']) {
            $roles[] = $role;
        }
    }
    
    return $roles;
}

/**
 * Validate role configuration structure
 * 
 * @param array $config Role configuration array
 * @return array Array of validation errors (empty if valid)
 */
function rbec_validate_role_config($config) {
    $errors = array();
    $required_keys = array('show_edit', 'show_elementor', 'description');
    
    if (!is_array($config)) {
        $errors[] = 'Configuration must be an array';
        return $errors;
    }
    
    foreach ($config as $role => $settings) {
        if (!is_array($settings)) {
            $errors[] = "Role '{$role}' configuration must be an array";
            continue;
        }
        
        foreach ($required_keys as $key) {
            if (!isset($settings[$key])) {
                $errors[] = "Role '{$role}' is missing required key: {$key}";
            } elseif (in_array($key, array('show_edit', 'show_elementor')) && !is_bool($settings[$key])) {
                $errors[] = "Role '{$role}' key '{$key}' must be boolean";
            } elseif ($key === 'description' && !is_string($settings[$key])) {
                $errors[] = "Role '{$role}' key '{$key}' must be string";
            }
        }
    }
    
    return $errors;
}

/**
 * Initialize configuration validation on admin init
 */
function rbec_init_config_validation() {
    global $rbec_role_button_config;
    
    $errors = rbec_validate_role_config($rbec_role_button_config);
    
    if (!empty($errors)) {
        add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>RBEC Role-Based Edit Control:</strong> Configuration validation errors found:</p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        });
    }
}
add_action('admin_init', 'rbec_init_config_validation');

/**
 * Debug function to display current role configuration
 * Only works if WP_DEBUG is enabled
 */
if (!function_exists('rbec_debug_role_config')) {
    function rbec_debug_role_config() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    error_log('RBEC Role-Based Edit Control - Current User Roles: ' . implode(', ', $user_roles));
    
    foreach ($user_roles as $role) {
        $config = rbec_get_role_button_config($role);
        error_log("RBEC Role-Based Edit Control - Role '{$role}': Edit=" . ($config['show_edit'] ? 'Yes' : 'No') . 
                 ", Elementor=" . ($config['show_elementor'] ? 'Yes' : 'No'));
    }
    }
}
