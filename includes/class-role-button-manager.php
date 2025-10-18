<?php
/**
 * Role Button Manager Class
 * 
 * This class handles the core logic for hiding/showing admin buttons
 * based on user roles. It integrates with WordPress hooks and Elementor.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * UiPress Role Button Manager Class
 */
class RBEC_Button_Manager {

    /**
     * Whether the manager has been initialized
     */
    private $initialized = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Constructor is intentionally empty
        // Initialization is done via init() method
    }

    /**
     * Initialize the button manager
     */
    public function init() {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $this->register_hooks();
        
        // Debug logging if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_init', 'uipress_debug_role_config');
        }
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Post and page list table actions
        add_filter('post_row_actions', array($this, 'filter_post_row_actions'), 10, 2);
        add_filter('page_row_actions', array($this, 'filter_page_row_actions'), 10, 2);
        
        // Admin bar menu
        add_action('admin_bar_menu', array($this, 'filter_admin_bar_menu'), 999);
        
        // Edit post links
        add_filter('get_edit_post_link', array($this, 'filter_edit_post_link'), 10, 3);
        
        // Bulk actions filters
        add_filter('bulk_actions-edit-post', array($this, 'filter_bulk_actions'));
        add_filter('bulk_actions-edit-page', array($this, 'filter_bulk_actions'));
        
        // Elementor specific hooks
        $this->register_elementor_hooks();
        
        // Admin footer for additional button hiding
        add_action('admin_footer', array($this, 'add_admin_footer_scripts'));
    }

    /**
     * Register Elementor-specific hooks
     */
    private function register_elementor_hooks() {
        // Check if Elementor is active
        if (!$this->is_elementor_active()) {
            return;
        }

        // Remove Elementor edit links from post row actions
        add_filter('post_row_actions', array($this, 'remove_elementor_row_actions'), 10, 2);
        
        // Hide Elementor admin menu items for non-admins
        add_action('admin_menu', array($this, 'filter_elementor_admin_menu'), 999);
        
        // Block Elementor editor access
        add_action('elementor/editor/footer', array($this, 'check_elementor_editor_access'));
    }

    /**
     * Filter post row actions to hide edit buttons
     * 
     * @param array $actions Current row actions
     * @param WP_Post $post Post object
     * @return array Filtered actions
     */
    public function filter_post_row_actions($actions, $post) {
        try {
            // Validate inputs
            if (!is_array($actions) || !is_object($post)) {
                return $actions;
            }

            // Remove edit action if user can't see it
            if (!uipress_user_can_see_edit_button()) {
                unset($actions['edit']);
            }

            // Remove Elementor edit action if user can't see it
            if (!uipress_user_can_see_elementor_button()) {
                $this->remove_elementor_actions($actions);
            }

            return $actions;
            
        } catch (Exception $e) {
            // Log error and fail gracefully
            error_log('UiPress Role Buttons Error in filter_post_row_actions: ' . $e->getMessage());
            return $actions;
        }
    }

    /**
     * Filter page row actions to hide edit buttons
     * 
     * @param array $actions Current row actions
     * @param WP_Post $post Post object
     * @return array Filtered actions
     */
    public function filter_page_row_actions($actions, $post) {
        try {
            // Same logic as post row actions with error handling
            return $this->filter_post_row_actions($actions, $post);
        } catch (Exception $e) {
            error_log('UiPress Role Buttons Error in filter_page_row_actions: ' . $e->getMessage());
            return $actions;
        }
    }

    /**
     * Remove Elementor actions from row actions
     * 
     * @param array $actions Row actions array
     */
    private function remove_elementor_actions(&$actions) {
        // Remove various Elementor-related actions
        $elementor_actions = array(
            'elementor',
            'edit_with_elementor',
            'elementor-preview'
        );

        foreach ($elementor_actions as $action) {
            unset($actions[$action]);
        }
    }

    /**
     * Filter bulk actions to hide edit actions
     * 
     * @param array $actions Bulk actions array
     * @return array Filtered bulk actions
     */
    public function filter_bulk_actions($actions) {
        try {
            // Validate input
            if (!is_array($actions)) {
                return $actions;
            }

            // Remove edit actions if user can't see them
            if (!uipress_user_can_see_edit_button()) {
                unset($actions['edit']);
                unset($actions['trash']);
            }

            return $actions;
            
        } catch (Exception $e) {
            error_log('UiPress Role Buttons Error in filter_bulk_actions: ' . $e->getMessage());
            return $actions;
        }
    }

    /**
     * Filter admin bar menu to hide edit links
     * 
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public function filter_admin_bar_menu($wp_admin_bar) {
        // Remove edit post link from admin bar
        if (!uipress_user_can_see_edit_button()) {
            $wp_admin_bar->remove_node('edit');
        }

        // Remove Elementor edit link from admin bar
        if (!uipress_user_can_see_elementor_button()) {
            $wp_admin_bar->remove_node('elementor_edit_page');
        }
    }

    /**
     * Filter edit post link
     * 
     * @param string $link Edit post link
     * @param int $post_id Post ID
     * @param string $context Link context
     * @return string|false Edit link or false if user can't edit
     */
    public function filter_edit_post_link($link, $post_id, $context) {
        // Return false to prevent edit links from being generated
        if (!uipress_user_can_see_edit_button()) {
            return false;
        }

        return $link;
    }

    /**
     * Remove Elementor row actions
     * 
     * @param array $actions Current row actions
     * @param WP_Post $post Post object
     * @return array Filtered actions
     */
    public function remove_elementor_row_actions($actions, $post) {
        if (!uipress_user_can_see_elementor_button()) {
            $this->remove_elementor_actions($actions);
        }

        return $actions;
    }

    /**
     * Filter Elementor admin menu items
     */
    public function filter_elementor_admin_menu() {
        // Hide Elementor admin menu items for non-admins
        if (!uipress_user_can_see_elementor_button()) {
            remove_menu_page('elementor');
            remove_submenu_page('elementor', 'elementor-tools');
            remove_submenu_page('elementor', 'elementor-system-info');
        }
    }

    /**
     * Check Elementor editor access
     */
    public function check_elementor_editor_access() {
        if (!uipress_user_can_see_elementor_button()) {
            wp_die(
                __('Sorry, you are not allowed to edit with Elementor.', 'uipress-role-buttons'),
                __('Access Denied', 'uipress-role-buttons'),
                array('response' => 403)
            );
        }
    }

    /**
     * Add admin footer scripts for additional button hiding
     */
    public function add_admin_footer_scripts() {
        // Only add scripts if user has restricted access
        if (uipress_user_can_see_edit_button() && uipress_user_can_see_elementor_button()) {
            return;
        }

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Additional button hiding for elements not caught by PHP hooks
            
            <?php if (!uipress_user_can_see_edit_button()): ?>
            // Hide classic edit buttons
            $('.edit-link, .edit a, .post-edit-link, .page-edit-link').hide();
            <?php endif; ?>
            
            <?php if (!uipress_user_can_see_elementor_button()): ?>
            // Hide Elementor buttons
            $('.elementor-edit-link, .elementor-edit-page, .elementor-preview-link').hide();
            $('a[href*="elementor"]').hide();
            <?php endif; ?>
            
            // Hide buttons in UiPress admin panels
            <?php if (!uipress_user_can_see_edit_button()): ?>
            $('.uip-admin-page a[href*="post.php"], .uip-admin-page a[href*="post-new.php"]').hide();
            <?php endif; ?>
            
            <?php if (!uipress_user_can_see_elementor_button()): ?>
            $('.uip-admin-page a[href*="elementor"]').hide();
            <?php endif; ?>
        });
        </script>
        <?php
    }

    /**
     * Check if Elementor is active
     * 
     * @return bool True if Elementor is active
     */
    private function is_elementor_active() {
        return did_action('elementor/loaded');
    }

    /**
     * Get current user's primary role
     * 
     * @return string User's primary role
     */
    private function get_current_user_role() {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        return !empty($user_roles) ? $user_roles[0] : '';
    }

    /**
     * Debug method to log button visibility decisions
     * 
     * @param string $context Context where the check is happening
     */
    public function debug_button_visibility($context = '') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $user_role = $this->get_current_user_role();
        $can_edit = uipress_user_can_see_edit_button();
        $can_elementor = uipress_user_can_see_elementor_button();
        
        error_log(sprintf(
            'UiPress Role Buttons - %s: Role=%s, Edit=%s, Elementor=%s',
            $context,
            $user_role,
            $can_edit ? 'Yes' : 'No',
            $can_elementor ? 'Yes' : 'No'
        ));
    }
}
