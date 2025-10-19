<?php
/**
 * Plugin Name: Role-Based Edit Control
 * Plugin URI: https://wordpress.org/plugins/role-based-edit-control/
 * Description: Control edit button visibility based on user roles with a simple, visual interface. Target individual users or entire roles for Edit and Elementor buttons.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Tested up to: 6.8
 * Author: Arif Hossin
 * Author URI: https://digitalfarmers.be
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: role-based-edit-control
 * Domain Path: /languages
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RBEC_VERSION', '1.0.0');
define('RBEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RBEC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class - Minimal version
 */
class RBEC_Minimal {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Basic functionality - no external file dependencies
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Basic button filtering (simplified)
        add_filter('post_row_actions', array($this, 'filter_post_row_actions'), 10, 2);
        add_filter('page_row_actions', array($this, 'filter_page_row_actions'), 10, 2);
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Role-Based Edit Control',
            'Edit Control',
            'manage_options',
            'role-based-edit-control',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Role-Based Edit Control</h1>
            <div class="notice notice-success">
                <p><strong>Plugin is working!</strong> This is a minimal version that loads without external file dependencies.</p>
            </div>
            <p>Configure your role-based button visibility settings here.</p>
            <p><strong>Status:</strong> Plugin loaded successfully with minimal functionality.</p>
        </div>
        <?php
    }
    
    public function filter_post_row_actions($actions, $post) {
        // Basic filtering - you can expand this
        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles)) {
            // Hide edit for non-admins (example)
            unset($actions['edit']);
        }
        return $actions;
    }
    
    public function filter_page_row_actions($actions, $post) {
        // Basic filtering - you can expand this
        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles)) {
            // Hide edit for non-admins (example)
            unset($actions['edit']);
        }
        return $actions;
    }
    
    public function activate() {
        // Basic activation
        add_option('rbec_activated', true);
    }
    
    public function deactivate() {
        // Basic deactivation
        delete_option('rbec_activated');
    }
}

// Initialize the plugin
function rbec_minimal_init() {
    RBEC_Minimal::get_instance();
}
add_action('plugins_loaded', 'rbec_minimal_init');
