<?php
/**
 * Example Custom Configuration for RBEC Role-Based Button Visibility
 * 
 * This file shows how to customize role configurations and add new functionality.
 * Copy the relevant sections to your theme's functions.php or create a custom plugin.
 * 
 * IMPORTANT: This is an example file - it will not be loaded automatically.
 * You need to copy the code you want to use to your theme's functions.php file.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EXAMPLE 1: Modify existing role permissions
 * 
 * This example shows how to change the default permissions for existing roles.
 * Add this to your theme's functions.php file.
 */
function custom_modify_role_permissions() {
    // Only run if our plugin is active
    if (!function_exists('rbec_get_role_button_config')) {
        return;
    }
    
    // Allow Editors to use Elementor (override default)
    add_filter('rbec_user_can_see_elementor_button', function($can_see) {
        if (current_user_can('edit_posts')) {
            return true;
        }
        return $can_see;
    });
    
    // Allow Shop Managers to edit posts (override default)
    add_filter('rbec_user_can_see_edit_button', function($can_see) {
        if (current_user_can('manage_woocommerce')) {
            return true;
        }
        return $can_see;
    });
}
add_action('init', 'custom_modify_role_permissions');

/**
 * EXAMPLE 2: Add custom role with specific permissions
 * 
 * This example shows how to add a new custom role with specific button permissions.
 */
function add_custom_role_with_permissions() {
    // Add a new role called 'Content Manager'
    add_role('content_manager', 'Content Manager', array(
        'read' => true,
        'edit_posts' => true,
        'edit_pages' => true,
        'edit_published_posts' => true,
        'edit_published_pages' => true,
        'publish_posts' => true,
        'publish_pages' => true,
        'delete_posts' => true,
        'delete_pages' => true,
        'delete_published_posts' => true,
        'delete_published_pages' => true,
    ));
    
    // Configure button visibility for the new role
    add_filter('rbec_role_button_config', function($config) {
        $config['content_manager'] = array(
            'show_edit' => true,
            'show_elementor' => false,
            'description' => 'Content Manager can edit but not with Elementor'
        );
        return $config;
    });
}
add_action('init', 'add_custom_role_with_permissions');

/**
 * EXAMPLE 3: Add custom button type (e.g., "Edit with Gutenberg")
 * 
 * This example shows how to add a new button type beyond Edit and Elementor.
 */
function add_custom_button_type() {
    // Add custom button configuration to existing roles
    add_filter('rbec_role_button_config', function($config) {
        foreach ($config as $role => $permissions) {
            $config[$role]['show_gutenberg'] = $permissions['show_edit']; // Default to same as edit
        }
        return $config;
    });
    
    // Add helper function for Gutenberg button visibility
    if (!function_exists('rbec_user_can_see_gutenberg_button')) {
        function rbec_user_can_see_gutenberg_button() {
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;
            
            foreach ($user_roles as $role) {
                $config = rbec_get_role_button_config($role);
                if (isset($config['show_gutenberg']) && $config['show_gutenberg']) {
                    return true;
                }
            }
            
            return false;
        }
    }
    
    // Hide Gutenberg buttons for users who can't see them
    add_filter('post_row_actions', function($actions, $post) {
        if (!rbec_user_can_see_gutenberg_button()) {
            unset($actions['edit']);
            // Remove Gutenberg-specific actions if they exist
            unset($actions['gutenberg_edit']);
        }
        return $actions;
    }, 10, 2);
}
add_action('init', 'add_custom_button_type');

/**
 * EXAMPLE 4: Conditional permissions based on post type
 * 
 * This example shows how to give different permissions for different post types.
 */
function conditional_permissions_by_post_type() {
    add_filter('rbec_user_can_see_edit_button', function($can_see) {
        global $post;
        
        // If we're on a specific post type (e.g., products)
        if ($post && $post->post_type === 'product') {
            // Only allow Shop Managers to edit products
            if (current_user_can('manage_woocommerce')) {
                return true;
            } else {
                return false;
            }
        }
        
        // Default behavior for other post types
        return $can_see;
    });
    
    add_filter('rbec_user_can_see_elementor_button', function($can_see) {
        global $post;
        
        // Disable Elementor for product pages
        if ($post && $post->post_type === 'product') {
            return false;
        }
        
        // Default behavior for other post types
        return $can_see;
    });
}
add_action('init', 'conditional_permissions_by_post_type');

/**
 * EXAMPLE 5: Add custom CSS for hidden buttons
 * 
 * This example shows how to add custom styling for hidden buttons.
 */
function add_custom_button_styling() {
    $css = "
    /* Custom styling for hidden buttons */
    .rbec-role-button-hidden {
        opacity: 0.3 !important;
        pointer-events: none !important;
        text-decoration: line-through !important;
    }
    
    /* Show tooltip on hover for hidden buttons */
    .rbec-role-button-hidden:hover::after {
        content: 'Access restricted for your role';
        position: absolute;
        background: #000;
        color: #fff;
        padding: 5px;
        border-radius: 3px;
        font-size: 12px;
        z-index: 9999;
    }
    ";
    
    wp_add_inline_style('wp-admin', $css);
}
add_action('admin_enqueue_scripts', 'add_custom_button_styling');

/**
 * EXAMPLE 6: Debug role assignments
 * 
 * This example shows how to add debug information to help troubleshoot role assignments.
 */
function debug_role_assignments() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    add_action('admin_notices', function() {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        echo '<div class="notice notice-info">';
        echo '<p><strong>RBEC Role-Based Edit Control Debug:</strong></p>';
        echo '<p>Current User: ' . $current_user->display_name . '</p>';
        echo '<p>User Roles: ' . implode(', ', $user_roles) . '</p>';
        echo '<p>Can Edit: ' . (rbec_user_can_see_edit_button() ? 'Yes' : 'No') . '</p>';
        echo '<p>Can Use Elementor: ' . (rbec_user_can_see_elementor_button() ? 'Yes' : 'No') . '</p>';
        echo '</div>';
    });
}
add_action('admin_init', 'debug_role_assignments');

/**
 * EXAMPLE 7: Integration with User Role Editor plugin
 * 
 * This example shows how to integrate with the User Role Editor plugin.
 */
function integrate_with_user_role_editor() {
    // Check if User Role Editor is active
    if (!function_exists('ure_get_editable_roles')) {
        return;
    }
    
    // Add custom capabilities for button visibility
    add_filter('ure_capabilities_groups_tree', function($groups) {
        $groups['rbec_buttons'] = array(
            'caption' => 'UiPress Button Visibility',
            'parent' => 'general',
            'level' => 3
        );
        return $groups;
    });
    
    add_filter('ure_custom_capability_groups', function($groups, $cap_id) {
        if (in_array($cap_id, array('edit_with_elementor', 'view_edit_buttons'))) {
            $groups[] = 'rbec_buttons';
        }
        return $groups;
    }, 10, 2);
}
add_action('init', 'integrate_with_user_role_editor');
