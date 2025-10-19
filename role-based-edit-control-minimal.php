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
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['rbec_nonce'], 'rbec_settings')) {
            $this->save_settings();
        }
        
        $settings = get_option('rbec_settings', array());
        ?>
        <div class="wrap">
            <h1>Role-Based Edit Control</h1>
            <div class="notice notice-success">
                <p><strong>Plugin is working!</strong> Configure your role-based button visibility settings below.</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('rbec_settings', 'rbec_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Role-Based Control</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked(isset($settings['enabled']) ? $settings['enabled'] : 0, 1); ?>>
                                Enable role-based button visibility control
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Administrator</th>
                        <td>
                            <label>
                                <input type="checkbox" name="roles[administrator][edit]" value="1" <?php checked(isset($settings['roles']['administrator']['edit']) ? $settings['roles']['administrator']['edit'] : 1, 1); ?>>
                                Show Edit Button
                            </label><br>
                            <label>
                                <input type="checkbox" name="roles[administrator][elementor]" value="1" <?php checked(isset($settings['roles']['administrator']['elementor']) ? $settings['roles']['administrator']['elementor'] : 1, 1); ?>>
                                Show Elementor Button
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Editor</th>
                        <td>
                            <label>
                                <input type="checkbox" name="roles[editor][edit]" value="1" <?php checked(isset($settings['roles']['editor']['edit']) ? $settings['roles']['editor']['edit'] : 1, 1); ?>>
                                Show Edit Button
                            </label><br>
                            <label>
                                <input type="checkbox" name="roles[editor][elementor]" value="1" <?php checked(isset($settings['roles']['editor']['elementor']) ? $settings['roles']['editor']['elementor'] : 0, 1); ?>>
                                Show Elementor Button
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Author</th>
                        <td>
                            <label>
                                <input type="checkbox" name="roles[author][edit]" value="1" <?php checked(isset($settings['roles']['author']['edit']) ? $settings['roles']['author']['edit'] : 0, 1); ?>>
                                Show Edit Button
                            </label><br>
                            <label>
                                <input type="checkbox" name="roles[author][elementor]" value="1" <?php checked(isset($settings['roles']['author']['elementor']) ? $settings['roles']['author']['elementor'] : 0, 1); ?>>
                                Show Elementor Button
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Shop Manager</th>
                        <td>
                            <label>
                                <input type="checkbox" name="roles[shop_manager][edit]" value="1" <?php checked(isset($settings['roles']['shop_manager']['edit']) ? $settings['roles']['shop_manager']['edit'] : 0, 1); ?>>
                                Show Edit Button
                            </label><br>
                            <label>
                                <input type="checkbox" name="roles[shop_manager][elementor]" value="1" <?php checked(isset($settings['roles']['shop_manager']['elementor']) ? $settings['roles']['shop_manager']['elementor'] : 0, 1); ?>>
                                Show Elementor Button
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <div class="card" style="margin-top: 20px;">
                <h3>Current Status</h3>
                <p><strong>Plugin Status:</strong> Active and working</p>
                <p><strong>Control Enabled:</strong> <?php echo isset($settings['enabled']) && $settings['enabled'] ? 'Yes' : 'No'; ?></p>
                <p><strong>Your Role:</strong> <?php echo implode(', ', wp_get_current_user()->roles); ?></p>
            </div>
        </div>
        
        <style>
        .form-table th {
            width: 200px;
        }
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        </style>
        <?php
    }
    
    private function save_settings() {
        $settings = array();
        $settings['enabled'] = isset($_POST['enabled']) ? 1 : 0;
        $settings['roles'] = isset($_POST['roles']) ? $_POST['roles'] : array();
        
        update_option('rbec_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        });
    }
    
    public function filter_post_row_actions($actions, $post) {
        if (!$this->should_filter_actions()) {
            return $actions;
        }
        
        $user = wp_get_current_user();
        $settings = get_option('rbec_settings', array());
        
        foreach ($user->roles as $role) {
            if (isset($settings['roles'][$role])) {
                // Hide edit button if not allowed for this role
                if (!isset($settings['roles'][$role]['edit']) || !$settings['roles'][$role]['edit']) {
                    unset($actions['edit']);
                }
                
                // Hide Elementor button if not allowed for this role
                if (!isset($settings['roles'][$role]['elementor']) || !$settings['roles'][$role]['elementor']) {
                    unset($actions['elementor']);
                }
                break;
            }
        }
        
        return $actions;
    }
    
    public function filter_page_row_actions($actions, $post) {
        if (!$this->should_filter_actions()) {
            return $actions;
        }
        
        $user = wp_get_current_user();
        $settings = get_option('rbec_settings', array());
        
        foreach ($user->roles as $role) {
            if (isset($settings['roles'][$role])) {
                // Hide edit button if not allowed for this role
                if (!isset($settings['roles'][$role]['edit']) || !$settings['roles'][$role]['edit']) {
                    unset($actions['edit']);
                }
                
                // Hide Elementor button if not allowed for this role
                if (!isset($settings['roles'][$role]['elementor']) || !$settings['roles'][$role]['elementor']) {
                    unset($actions['elementor']);
                }
                break;
            }
        }
        
        return $actions;
    }
    
    private function should_filter_actions() {
        $settings = get_option('rbec_settings', array());
        return isset($settings['enabled']) && $settings['enabled'];
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
