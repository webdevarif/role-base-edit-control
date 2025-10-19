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
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple plugin loading
if (defined('RBEC_LOADED')) {
    return;
}
define('RBEC_LOADED', true);

// Define plugin constants (only if not already defined)
if (!defined('RBEC_VERSION')) {
    define('RBEC_VERSION', '1.0.0');
}
if (!defined('RBEC_PLUGIN_DIR')) {
    define('RBEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('RBEC_PLUGIN_URL')) {
    define('RBEC_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Keep old constants for backward compatibility
if (!defined('RBEC_VERSION_LEGACY')) {
    define('RBEC_VERSION_LEGACY', RBEC_VERSION);
}
if (!defined('RBEC_PLUGIN_DIR_LEGACY')) {
    define('RBEC_PLUGIN_DIR_LEGACY', RBEC_PLUGIN_DIR);
}
if (!defined('RBEC_PLUGIN_URL_LEGACY')) {
    define('RBEC_PLUGIN_URL_LEGACY', RBEC_PLUGIN_URL);
}


/**
 * Main plugin class
 */
class RBEC_Main {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Button manager instance
     */
    private $button_manager;

    /**
     * Admin settings instance
     */
    private $admin_settings;

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->init();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load role configuration
        $role_config_file = RBEC_PLUGIN_DIR_LEGACY . 'includes' . DIRECTORY_SEPARATOR . 'role-config.php';
        if (file_exists($role_config_file)) {
            require_once $role_config_file;
        } else {
            error_log('RBEC: role-config.php not found at: ' . $role_config_file);
        }
        
        // Load button manager class
        $button_manager_file = RBEC_PLUGIN_DIR_LEGACY . 'includes' . DIRECTORY_SEPARATOR . 'class-role-button-manager.php';
        if (file_exists($button_manager_file)) {
            require_once $button_manager_file;
        } else {
            error_log('RBEC: class-role-button-manager.php not found at: ' . $button_manager_file);
        }
        
        // Load simple permissions system
        $permissions_file = RBEC_PLUGIN_DIR_LEGACY . 'includes' . DIRECTORY_SEPARATOR . 'class-simple-permissions.php';
        if (file_exists($permissions_file)) {
            require_once $permissions_file;
        } else {
            error_log('RBEC: including permissions inline');
            // Include permissions inline if file doesn't exist
            $this->include_permissions_inline();
        }
        
        // Load admin settings class
        $admin_settings_file = RBEC_PLUGIN_DIR_LEGACY . 'includes' . DIRECTORY_SEPARATOR . 'class-admin-settings.php';
        if (file_exists($admin_settings_file)) {
            require_once $admin_settings_file;
        } else {
            error_log('RBEC: class-admin-settings.php not found at: ' . $admin_settings_file);
        }
        
        // Load WP-CLI commands if WP-CLI is available
        if (defined('WP_CLI') && WP_CLI) {
            $wp_cli_file = RBEC_PLUGIN_DIR_LEGACY . 'includes' . DIRECTORY_SEPARATOR . 'class-wp-cli-commands.php';
            if (file_exists($wp_cli_file)) {
                require_once $wp_cli_file;
            } else {
                error_log('RBEC: class-wp-cli-commands.php not found at: ' . $wp_cli_file);
            }
        }
    }

    /**
     * Include permissions inline as fallback
     */
    private function include_permissions_inline() {
        // Basic permissions fallback - define essential functions inline
        if (!function_exists('rbec_user_can_see_edit_button')) {
            function rbec_user_can_see_edit_button() {
                return true; // Default to true if config not available
            }
        }
        if (!function_exists('rbec_user_can_see_elementor_button')) {
            function rbec_user_can_see_elementor_button() {
                return true; // Default to true if config not available
            }
        }
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        try {
            if (class_exists('RBEC_Button_Manager')) {
                $this->button_manager = new RBEC_Button_Manager();
            } else {
                error_log('RBEC: RBEC_Button_Manager class not found');
            }
            
            if (class_exists('RBEC_Admin_Settings')) {
                $this->admin_settings = new RBEC_Admin_Settings();
            } else {
                error_log('RBEC: RBEC_Admin_Settings class not found');
            }
        } catch (Exception $e) {
            error_log('RBEC: Error initializing components: ' . $e->getMessage());
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize button manager
        if ($this->button_manager) {
            $this->button_manager->init();
        }
        
        // Initialize admin settings
        if ($this->admin_settings) {
            $this->admin_settings->init();
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts() {
        // Only load on admin pages
        if (!is_admin()) {
            return;
        }

        // Get current user's primary role
        $current_user = wp_get_current_user();
        $primary_role = !empty($current_user->roles) ? $current_user->roles[0] : '';

        // Get role configuration
        global $rbec_role_button_config;
        
        // Enqueue admin control script
        wp_enqueue_script(
            'rbec-role-buttons-admin',
            RBEC_PLUGIN_URL_LEGACY . 'assets/js/admin-button-control.js',
            array('jquery'),
            RBEC_VERSION_LEGACY,
            true
        );

        // Localize script with user role and configuration
        wp_localize_script('rbec-role-buttons-admin', 'rbecRoleButtons', array(
            'userRole' => $primary_role,
            'roleConfig' => $rbec_role_button_config,
            'nonce' => wp_create_nonce('rbec_role_buttons_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));

    }

    /**
     * AJAX handler for testing current user
     */
    public function ajax_test_user() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'rbec_role_buttons_admin')) {
            wp_send_json_error('Security check failed');
        }

        // Capability check
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Get current user info
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        $role_details = array();
        foreach ($user_roles as $role) {
            $config = rbec_get_role_button_config($role);
            $role_details[$role] = $config;
        }

        wp_send_json_success(array(
            'display_name' => $current_user->display_name,
            'roles' => $user_roles,
            'can_edit' => rbec_user_can_see_edit_button(),
            'can_elementor' => rbec_user_can_see_elementor_button(),
            'role_details' => $role_details
        ));
    }

    /**
     * Get admin settings instance
     */
    public function get_admin_settings() {
        return $this->admin_settings;
    }

}

/**
 * Initialize the plugin
 */
function rbec_init() {
    return RBEC_Main::get_instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'rbec_init');

/**
 * Plugin activation hook
 */
function rbec_activate() {
    // Set default options if they don't exist
    if (!get_option('rbec_version')) {
        add_option('rbec_version', RBEC_VERSION_LEGACY);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Redirect to settings page after activation
    set_transient('rbec_redirect_to_settings', true, 30);
    
    // Show welcome notice
    set_transient('rbec_show_welcome_notice', true, 60);
}
register_activation_hook(__FILE__, 'rbec_activate');

/**
 * Plugin deactivation hook
 */
function rbec_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'rbec_deactivate');

/**
 * Add AJAX handlers and enqueue scripts using plugin instance
 */
add_action('wp_ajax_rbec_test_user', function() {
    $plugin = RBEC_Main::get_instance();
    $plugin->ajax_test_user();
});

add_action('admin_enqueue_scripts', function($hook) {
    $plugin = RBEC_Main::get_instance();
    $plugin->enqueue_admin_scripts($hook);
});

/**
 * Redirect to settings page after plugin activation
 */
function rbec_redirect_to_settings() {
    if (get_transient('rbec_redirect_to_settings')) {
        delete_transient('rbec_redirect_to_settings');
        
        // Only redirect if not on the plugins page
        if (!isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('options-general.php?page=role-based-edit-control'));
            exit;
        }
    }
}
add_action('admin_init', 'rbec_redirect_to_settings');

/**
 * Add settings link to plugin actions
 */
function rbec_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=role-based-edit-control') . '">' . __('Settings', 'role-based-edit-control') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'rbec_add_settings_link');

/**
 * Show welcome notice after plugin activation
 */
function rbec_show_welcome_notice() {
    if (get_transient('rbec_show_welcome_notice')) {
        delete_transient('rbec_show_welcome_notice');
        
        $settings_url = admin_url('options-general.php?page=role-based-edit-control');
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>' . __('Role-Based Edit Control', 'role-based-edit-control') . '</strong> ' . __('has been activated successfully!', 'role-based-edit-control') . '</p>';
        echo '<p>' . __('Configure your role permissions and user overrides:', 'role-based-edit-control') . ' <a href="' . esc_url($settings_url) . '" class="button button-primary">' . __('Go to Settings', 'role-based-edit-control') . '</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'rbec_show_welcome_notice');

/**
 * Add settings link to admin bar
 */
function rbec_add_admin_bar_link($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $wp_admin_bar->add_node(array(
        'id' => 'rbec-settings',
        'title' => __('Role-Based Edit Control', 'role-based-edit-control'),
        'href' => admin_url('options-general.php?page=role-based-edit-control'),
        'meta' => array(
            'title' => __('Configure role permissions and user overrides', 'role-based-edit-control')
        )
    ));
}
add_action('admin_bar_menu', 'rbec_add_admin_bar_link', 100);

/**
 * Debug function - only works if WP_DEBUG is enabled
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    function rbec_debug_role_config() {
        if (current_user_can('manage_options')) {
            error_log('RBEC Debug: Plugin loaded successfully');
            error_log('RBEC Debug: Current user roles: ' . implode(', ', wp_get_current_user()->roles));
        }
    }
    add_action('admin_init', 'rbec_debug_role_config');
}
