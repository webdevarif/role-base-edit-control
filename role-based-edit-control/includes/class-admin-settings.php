<?php
/**
 * Simple Admin Settings for UiPress Role-Based Button Visibility
 * 
 * This class provides a clean, tabbed interface for managing
 * both role permissions and individual user overrides.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Role-Based Edit Control Admin Settings Class
 */
class RBEC_Admin_Settings {

    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'role-based-edit-control';

    /**
     * Initialize admin settings
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_rbec_search_users', array($this, 'ajax_search_users'));
        add_action('wp_ajax_rbec_save_role_permissions', array($this, 'ajax_save_role_permissions'));
        add_action('wp_ajax_rbec_save_user_override', array($this, 'ajax_save_user_override'));
        add_action('wp_ajax_rbec_remove_user_override', array($this, 'ajax_remove_user_override'));
        add_action('wp_ajax_rbec_export_permissions', array($this, 'ajax_export_permissions'));
        add_action('wp_ajax_rbec_import_permissions', array($this, 'ajax_import_permissions'));
        add_action('wp_ajax_rbec_reset_permissions', array($this, 'ajax_reset_permissions'));
        add_action('wp_ajax_rbec_test_current_user', array($this, 'ajax_test_current_user'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            __('Role-Based Edit Control', 'role-based-edit-control'),
            __('Role-Based Edit Control', 'role-based-edit-control'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="uipress-simple-admin">
                <!-- Tabs -->
                <nav class="nav-tab-wrapper">
                    <a href="#tab-role-permissions" class="nav-tab nav-tab-active" data-tab="role-permissions">
                        <?php _e('Role Permissions', 'role-based-edit-control'); ?>
                    </a>
                    <a href="#tab-user-overrides" class="nav-tab" data-tab="user-overrides">
                        <?php _e('User Overrides', 'role-based-edit-control'); ?>
                    </a>
                    <a href="#tab-quick-actions" class="nav-tab" data-tab="quick-actions">
                        <?php _e('Quick Actions', 'role-based-edit-control'); ?>
                    </a>
                </nav>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Role Permissions Tab -->
                    <div id="tab-role-permissions" class="tab-pane active">
                        <?php $this->render_role_permissions_tab(); ?>
                    </div>

                    <!-- User Overrides Tab -->
                    <div id="tab-user-overrides" class="tab-pane">
                        <?php $this->render_user_overrides_tab(); ?>
                    </div>

                    <!-- Quick Actions Tab -->
                    <div id="tab-quick-actions" class="tab-pane">
                        <?php $this->render_quick_actions_tab(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render role permissions tab
     */
    private function render_role_permissions_tab() {
        $available_roles = RBEC_Permissions::get_available_roles();
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Role-Based Permissions (Default Settings)', 'role-based-edit-control'); ?></h2>
            </div>
            <div class="inside">
                <p><?php _e('Set default permissions for each user role. These apply to all users unless they have individual overrides.', 'role-based-edit-control'); ?></p>
                
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Role', 'role-based-edit-control'); ?></th>
                            <th><?php _e('Edit Button', 'role-based-edit-control'); ?></th>
                            <th><?php _e('Elementor Button', 'role-based-edit-control'); ?></th>
                            <th><?php _e('Actions', 'role-based-edit-control'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_roles as $role_slug => $role_data): ?>
                        <tr data-role="<?php echo esc_attr($role_slug); ?>">
                            <td><strong><?php echo esc_html($role_data['name']); ?></strong></td>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           class="role-permission" 
                                           data-role="<?php echo esc_attr($role_slug); ?>" 
                                           data-permission="edit" 
                                           <?php checked($role_data['permissions']['edit']); ?>>
                                    <?php echo $role_data['permissions']['edit'] ? '✅ Yes' : '❌ No'; ?>
                                </label>
                            </td>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           class="role-permission" 
                                           data-role="<?php echo esc_attr($role_slug); ?>" 
                                           data-permission="elementor" 
                                           <?php checked($role_data['permissions']['elementor']); ?>>
                                    <?php echo $role_data['permissions']['elementor'] ? '✅ Yes' : '❌ No'; ?>
                                </label>
                            </td>
                            <td>
                                <button type="button" class="button save-role-permissions" data-role="<?php echo esc_attr($role_slug); ?>">
                                    <?php _e('Save', 'role-based-edit-control'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="button" class="button button-primary save-all-role-permissions">
                        <?php _e('Save All Role Permissions', 'role-based-edit-control'); ?>
                    </button>
                </p>
            </div>
        </div>

        <!-- Permission Matrix -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Permission Matrix (Quick View)', 'role-based-edit-control'); ?></h2>
            </div>
            <div class="inside">
                <p><?php _e('Quick overview of all role permissions:', 'role-based-edit-control'); ?></p>
                <div class="permission-matrix">
                    <table class="widefat fixed">
                        <thead>
                            <tr>
                                <th><?php _e('Role', 'role-based-edit-control'); ?></th>
                                <th><?php _e('Edit Button', 'role-based-edit-control'); ?></th>
                                <th><?php _e('Elementor Button', 'role-based-edit-control'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_roles as $role_slug => $role_data): ?>
                            <tr>
                                <td><strong><?php echo esc_html($role_data['name']); ?></strong></td>
                                <td><?php echo $role_data['permissions']['edit'] ? '✅ Yes' : '❌ No'; ?></td>
                                <td><?php echo $role_data['permissions']['elementor'] ? '✅ Yes' : '❌ No'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render user overrides tab
     */
    private function render_user_overrides_tab() {
        $user_overrides = RBEC_Permissions::get_all_user_overrides();
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('User-Specific Overrides', 'role-based-edit-control'); ?></h2>
            </div>
            <div class="inside">
                <p><?php _e('Override role permissions for specific users. These settings take priority over role permissions.', 'role-based-edit-control'); ?></p>
                
                <!-- Add User Override Section -->
                <div class="add-user-override">
                    <h3><?php _e('Add User Override', 'role-based-edit-control'); ?></h3>
                    <div class="user-search-container">
                        <input type="text" id="user-search" placeholder="<?php _e('Search users by name or email...', 'role-based-edit-control'); ?>" class="regular-text">
                        <div id="user-search-results" class="user-search-results"></div>
                    </div>
                </div>

                <!-- Existing User Overrides -->
                <?php if (!empty($user_overrides)): ?>
                <h3><?php _e('Current User Overrides', 'role-based-edit-control'); ?></h3>
                
                <!-- Bulk Actions -->
                <div class="bulk-actions">
                    <select id="bulk-action">
                        <option value=""><?php _e('Bulk Actions', 'role-based-edit-control'); ?></option>
                        <option value="enable-edit"><?php _e('Enable Edit for Selected', 'role-based-edit-control'); ?></option>
                        <option value="disable-edit"><?php _e('Disable Edit for Selected', 'role-based-edit-control'); ?></option>
                        <option value="enable-elementor"><?php _e('Enable Elementor for Selected', 'role-based-edit-control'); ?></option>
                        <option value="disable-elementor"><?php _e('Disable Elementor for Selected', 'role-based-edit-control'); ?></option>
                        <option value="remove-overrides"><?php _e('Remove Overrides for Selected', 'role-based-edit-control'); ?></option>
                    </select>
                    <button type="button" id="apply-bulk-action" class="button">
                        <?php _e('Apply', 'role-based-edit-control'); ?>
                    </button>
                    <button type="button" id="select-all-users" class="button">
                        <?php _e('Select All', 'role-based-edit-control'); ?>
                    </button>
                </div>
                
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-checkbox"></th>
                            <th><?php _e('User', 'role-based-edit-control'); ?></th>
                            <th><?php _e('Role', 'role-based-edit-control'); ?></th>
                            <th><?php _e('Edit Button', 'role-based-edit-control'); ?></th>
                            <th><?php _e('Elementor Button', 'role-based-edit-control'); ?></th>
                            <th><?php _e('Actions', 'role-based-edit-control'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_overrides as $user_id => $permissions): ?>
                        <?php 
                        $user = get_user_by('id', $user_id);
                        if (!$user) continue;
                        $user_roles = $user->roles;
                        ?>
                        <tr data-user-id="<?php echo esc_attr($user_id); ?>">
                            <td>
                                <input type="checkbox" class="user-select" data-user-id="<?php echo esc_attr($user_id); ?>">
                            </td>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                <small><?php echo esc_html($user->user_email); ?></small>
                            </td>
                            <td><?php echo esc_html(implode(', ', array_map('translate_user_role', $user_roles))); ?></td>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           class="user-override-permission" 
                                           data-user-id="<?php echo esc_attr($user_id); ?>" 
                                           data-permission="edit" 
                                           <?php checked($permissions['edit'] ?? false); ?>>
                                    <?php echo ($permissions['edit'] ?? false) ? '✅ Yes' : '❌ No'; ?>
                                </label>
                            </td>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           class="user-override-permission" 
                                           data-user-id="<?php echo esc_attr($user_id); ?>" 
                                           data-permission="elementor" 
                                           <?php checked($permissions['elementor'] ?? false); ?>>
                                    <?php echo ($permissions['elementor'] ?? false) ? '✅ Yes' : '❌ No'; ?>
                                </label>
                            </td>
                            <td>
                                <button type="button" class="button save-user-override" data-user-id="<?php echo esc_attr($user_id); ?>">
                                    <?php _e('Save', 'role-based-edit-control'); ?>
                                </button>
                                <button type="button" class="button button-link-delete remove-user-override" data-user-id="<?php echo esc_attr($user_id); ?>">
                                    <?php _e('Remove Override', 'role-based-edit-control'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php _e('No user overrides set. All users will use their role permissions.', 'role-based-edit-control'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render quick actions tab
     */
    private function render_quick_actions_tab() {
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Export & Import', 'role-based-edit-control'); ?></h2>
            </div>
            <div class="inside">
                <h3><?php _e('Export Permissions', 'role-based-edit-control'); ?></h3>
                <p><?php _e('Download your current permission settings as a JSON file for backup or migration.', 'role-based-edit-control'); ?></p>
                <button type="button" class="button" id="export-permissions">
                    <?php _e('Export Permissions', 'role-based-edit-control'); ?>
                </button>
                
                <h3><?php _e('Import Permissions', 'role-based-edit-control'); ?></h3>
                <p><?php _e('Upload a JSON file to restore permission settings.', 'role-based-edit-control'); ?></p>
                <input type="file" id="import-file" accept=".json" style="margin-bottom: 10px;">
                <br>
                <button type="button" class="button" id="import-permissions">
                    <?php _e('Import Permissions', 'role-based-edit-control'); ?>
                </button>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Reset & Maintenance', 'role-based-edit-control'); ?></h2>
            </div>
            <div class="inside">
                <h3><?php _e('Reset to Defaults', 'role-based-edit-control'); ?></h3>
                <p><?php _e('Reset all permissions to default values. This will remove all custom settings.', 'role-based-edit-control'); ?></p>
                <button type="button" class="button button-secondary" id="reset-permissions">
                    <?php _e('Reset to Defaults', 'role-based-edit-control'); ?>
                </button>
                
                <h3><?php _e('Test Current User', 'role-based-edit-control'); ?></h3>
                <p><?php _e('Test what permissions the current user has.', 'role-based-edit-control'); ?></p>
                <button type="button" class="button" id="test-current-user">
                    <?php _e('Test Current User', 'role-based-edit-control'); ?>
                </button>
                <div id="test-results" style="margin-top: 10px;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_' . self::PAGE_SLUG) {
            return;
        }

        wp_enqueue_script(
            'rbec-admin',
            plugin_dir_url(__FILE__) . '../assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('rbec-admin', 'rbecAdmin', array(
            'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
            'nonce' => wp_create_nonce('rbec_admin'),
            'strings' => array(
                'saving' => esc_html__('Saving...', 'role-based-edit-control'),
                'saved' => esc_html__('Saved!', 'role-based-edit-control'),
                'error' => esc_html__('Error occurred', 'role-based-edit-control'),
                'confirmReset' => esc_html__('Are you sure you want to reset all permissions to defaults?', 'role-based-edit-control'),
                'confirmRemove' => esc_html__('Are you sure you want to remove this user override?', 'role-based-edit-control')
            )
        ));

        // Add inline CSS
        wp_add_inline_style('wp-admin', '
            .uipress-simple-admin .tab-content { margin-top: 20px; }
            .uipress-simple-admin .tab-pane { display: none; }
            .uipress-simple-admin .tab-pane.active { display: block; }
            .user-search-container { position: relative; }
            .user-search-results { 
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 9999;
                max-height: 300px;
                overflow-y: auto;
                background: white;
                border: 1px solid #ddd;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                display: none; 
                width: 100%; 
            }
            .user-search-result { 
                padding: 10px; 
                cursor: pointer; 
                border-bottom: 1px solid #eee; 
            }
            .user-search-result:hover { background: #f0f0f0; }
            .user-search-result.selected { background: #0073aa; color: white; }
            .permission-matrix { 
                margin: 20px 0; 
                background: #f9f9f9; 
                padding: 15px; 
                border-radius: 4px; 
            }
            .permission-matrix table { 
                margin: 0; 
                background: white; 
            }
            .bulk-actions { 
                margin: 10px 0; 
                padding: 10px; 
                background: #f1f1f1; 
                border-radius: 4px; 
            }
            .bulk-actions select, .bulk-actions button { 
                margin-right: 10px; 
            }
            .keyboard-shortcuts { 
                position: fixed; 
                bottom: 20px; 
                right: 20px; 
                background: #0073aa; 
                color: white; 
                padding: 10px; 
                border-radius: 4px; 
                font-size: 12px; 
                display: none; 
            }
        ');
    }

    /**
     * AJAX: Search users
     */
    public function ajax_search_users() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $search = sanitize_text_field($_POST['search']);
        $results = RBEC_Permissions::search_users($search, 10);
        
        wp_send_json_success($results);
    }

    /**
     * AJAX: Save role permissions
     */
    public function ajax_save_role_permissions() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $role = sanitize_text_field($_POST['role']);
        $permissions = array(
            'edit' => isset($_POST['edit']) && $_POST['edit'] === 'true',
            'elementor' => isset($_POST['elementor']) && $_POST['elementor'] === 'true'
        );
        
        $success = RBEC_Permissions::set_role_permissions($role, $permissions);
        
        if ($success) {
            wp_send_json_success(array('message' => 'Role permissions saved'));
        } else {
            wp_send_json_error('Failed to save role permissions');
        }
    }

    /**
     * AJAX: Save user override
     */
    public function ajax_save_user_override() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $permissions = array(
            'edit' => isset($_POST['edit']) && $_POST['edit'] === 'true',
            'elementor' => isset($_POST['elementor']) && $_POST['elementor'] === 'true'
        );
        
        $success = RBEC_Permissions::set_user_override($user_id, $permissions);
        
        if ($success) {
            wp_send_json_success(array('message' => 'User override saved'));
        } else {
            wp_send_json_error('Failed to save user override');
        }
    }

    /**
     * AJAX: Remove user override
     */
    public function ajax_remove_user_override() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $success = RBEC_Permissions::remove_user_override($user_id);
        
        if ($success) {
            wp_send_json_success(array('message' => 'User override removed'));
        } else {
            wp_send_json_error('Failed to remove user override');
        }
    }

    /**
     * AJAX: Export permissions
     */
    public function ajax_export_permissions() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $permissions = RBEC_Permissions::export_permissions();
        $json = json_encode($permissions, JSON_PRETTY_PRINT);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="uipress-permissions-' . date('Y-m-d') . '.json"');
        echo $json;
        exit;
    }

    /**
     * AJAX: Import permissions
     */
    public function ajax_import_permissions() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload error');
        }
        
        $content = file_get_contents($file['tmp_name']);
        $permissions = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON file');
        }
        
        $success = RBEC_Permissions::import_permissions($permissions);
        
        if ($success) {
            wp_send_json_success(array('message' => 'Permissions imported successfully'));
        } else {
            wp_send_json_error('Failed to import permissions');
        }
    }

    /**
     * AJAX: Reset permissions
     */
    public function ajax_reset_permissions() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $success = RBEC_Permissions::reset_to_defaults();
        
        if ($success) {
            wp_send_json_success(array('message' => 'Permissions reset to defaults'));
        } else {
            wp_send_json_error('Failed to reset permissions');
        }
    }

    /**
     * AJAX: Test current user
     */
    public function ajax_test_current_user() {
        check_ajax_referer('rbec_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $current_user = wp_get_current_user();
        $effective_perms = RBEC_Permissions::get_user_effective_permissions($current_user->ID);
        
        wp_send_json_success(array(
            'display_name' => $current_user->display_name,
            'roles' => $current_user->roles,
            'permissions' => $effective_perms
        ));
    }
}
