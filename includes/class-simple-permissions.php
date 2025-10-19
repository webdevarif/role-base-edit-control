<?php
/**
 * Simple Permission System for UiPress Role-Based Button Visibility
 * 
 * This class provides a simplified database-driven permission system
 * that allows targeting both individual users and roles.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * UiPress Simple Permissions Class
 */
class RBEC_Permissions {

    /**
     * Option name for role permissions
     */
    const ROLE_PERMISSIONS_OPTION = 'rbec_role_permissions';

    /**
     * Option name for user overrides
     */
    const USER_OVERRIDES_OPTION = 'rbec_user_overrides';

    /**
     * Default role permissions
     */
    private static $default_role_permissions = array(
        'administrator' => array('edit' => true, 'elementor' => true),
        'editor' => array('edit' => true, 'elementor' => false),
        'shop_manager' => array('edit' => false, 'elementor' => false),
        'author' => array('edit' => false, 'elementor' => false),
        'contributor' => array('edit' => false, 'elementor' => false),
        'subscriber' => array('edit' => false, 'elementor' => false)
    );

    /**
     * Initialize the permission system
     */
    public static function init() {
        // Migrate from old system if needed
        self::migrate_from_old_system();
    }

    /**
     * Check if a user can see a specific button
     * 
     * Priority: User Override → Role Permission → Default (false)
     * 
     * @param int $user_id User ID (0 for current user)
     * @param string $button_type Button type ('edit' or 'elementor')
     * @return bool True if user can see the button
     */
    public static function user_can_see_button($user_id = 0, $button_type = 'edit') {
        static $cache = array();
        
        // Use current user if no user ID provided
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        // Return cached result if available
        $cache_key = "{$user_id}_{$button_type}";
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        
        $can_see = false;
        
        // 1. Check user override first
        $user_overrides = get_option(self::USER_OVERRIDES_OPTION, array());
        if (isset($user_overrides[$user_id][$button_type])) {
            $can_see = $user_overrides[$user_id][$button_type];
        } else {
            // 2. Check role permissions
            $user = get_user_by('id', $user_id);
            if ($user && !empty($user->roles)) {
                $role_permissions = get_option(self::ROLE_PERMISSIONS_OPTION, self::$default_role_permissions);
                
                foreach ($user->roles as $role) {
                    if (isset($role_permissions[$role][$button_type])) {
                        $can_see = $role_permissions[$role][$button_type];
                        break; // Use first matching role
                    }
                }
            }
        }
        
        // Cache the result for this request
        $cache[$cache_key] = $can_see;
        
        return $can_see;
    }

    /**
     * Set role permissions
     * 
     * @param string $role Role slug
     * @param array $permissions Permissions array ['edit' => true, 'elementor' => false]
     * @return bool Success
     */
    public static function set_role_permissions($role, $permissions) {
        $role_permissions = get_option(self::ROLE_PERMISSIONS_OPTION, self::$default_role_permissions);
        $role_permissions[$role] = array_merge($role_permissions[$role] ?? array(), $permissions);
        
        return update_option(self::ROLE_PERMISSIONS_OPTION, $role_permissions);
    }

    /**
     * Get role permissions
     * 
     * @param string $role Role slug (optional)
     * @return array Role permissions
     */
    public static function get_role_permissions($role = null) {
        $role_permissions = get_option(self::ROLE_PERMISSIONS_OPTION, self::$default_role_permissions);
        
        if ($role) {
            return $role_permissions[$role] ?? array('edit' => false, 'elementor' => false);
        }
        
        return $role_permissions;
    }

    /**
     * Set user override permissions
     * 
     * @param int $user_id User ID
     * @param array $permissions Permissions array ['edit' => true, 'elementor' => false]
     * @return bool Success
     */
    public static function set_user_override($user_id, $permissions) {
        $user_overrides = get_option(self::USER_OVERRIDES_OPTION, array());
        $user_overrides[$user_id] = array_merge($user_overrides[$user_id] ?? array(), $permissions);
        
        return update_option(self::USER_OVERRIDES_OPTION, $user_overrides);
    }

    /**
     * Get user override permissions
     * 
     * @param int $user_id User ID
     * @return array User override permissions
     */
    public static function get_user_override($user_id) {
        $user_overrides = get_option(self::USER_OVERRIDES_OPTION, array());
        return $user_overrides[$user_id] ?? array();
    }

    /**
     * Remove user override (user will use role permissions)
     * 
     * @param int $user_id User ID
     * @return bool Success
     */
    public static function remove_user_override($user_id) {
        $user_overrides = get_option(self::USER_OVERRIDES_OPTION, array());
        unset($user_overrides[$user_id]);
        
        return update_option(self::USER_OVERRIDES_OPTION, $user_overrides);
    }

    /**
     * Get all user overrides
     * 
     * @return array All user overrides
     */
    public static function get_all_user_overrides() {
        return get_option(self::USER_OVERRIDES_OPTION, array());
    }

    /**
     * Reset all permissions to defaults
     * 
     * @return bool Success
     */
    public static function reset_to_defaults() {
        delete_option(self::ROLE_PERMISSIONS_OPTION);
        delete_option(self::USER_OVERRIDES_OPTION);
        
        return true;
    }

    /**
     * Export permissions to array
     * 
     * @return array Exported permissions
     */
    public static function export_permissions() {
        return array(
            'role_permissions' => self::get_role_permissions(),
            'user_overrides' => self::get_all_user_overrides(),
            'exported_at' => current_time('mysql'),
            'version' => '1.0'
        );
    }

    /**
     * Import permissions from array
     * 
     * @param array $permissions Imported permissions
     * @return bool Success
     */
    public static function import_permissions($permissions) {
        if (!is_array($permissions)) {
            return false;
        }
        
        $success = true;
        
        if (isset($permissions['role_permissions'])) {
            $success &= update_option(self::ROLE_PERMISSIONS_OPTION, $permissions['role_permissions']);
        }
        
        if (isset($permissions['user_overrides'])) {
            $success &= update_option(self::USER_OVERRIDES_OPTION, $permissions['user_overrides']);
        }
        
        return $success;
    }

    /**
     * Get effective permissions for a user (including role and overrides)
     * 
     * @param int $user_id User ID
     * @return array Effective permissions
     */
    public static function get_user_effective_permissions($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return array('edit' => false, 'elementor' => false);
        }
        
        // Check for user override first
        $user_override = self::get_user_override($user_id);
        if (!empty($user_override)) {
            return array_merge(
                array('edit' => false, 'elementor' => false),
                $user_override
            );
        }
        
        // Check role permissions
        $role_permissions = self::get_role_permissions();
        $effective_permissions = array('edit' => false, 'elementor' => false);
        
        foreach ($user->roles as $role) {
            if (isset($role_permissions[$role])) {
                $effective_permissions = array_merge($effective_permissions, $role_permissions[$role]);
                break; // Use first matching role
            }
        }
        
        return $effective_permissions;
    }

    /**
     * Search users for admin interface
     * 
     * @param string $search Search term
     * @param int $limit Limit results
     * @return array User search results
     */
    public static function search_users($search, $limit = 10) {
        $args = array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_nicename', 'user_email', 'display_name'),
            'number' => $limit,
            'fields' => array('ID', 'display_name', 'user_email', 'user_login')
        );
        
        $users = get_users($args);
        $results = array();
        
        foreach ($users as $user) {
            $user_obj = get_user_by('id', $user->ID);
            $results[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'login' => $user->user_login,
                'roles' => $user_obj->roles,
                'effective_permissions' => self::get_user_effective_permissions($user->ID)
            );
        }
        
        return $results;
    }

    /**
     * Migrate from old system to new database-driven system
     */
    private static function migrate_from_old_system() {
        // Check if migration is needed
        if (get_option('rbec_permissions_migrated')) {
            return;
        }
        
        // Migrate role permissions from old global config
        global $rbec_role_button_config;
        
        if (isset($rbec_role_button_config) && is_array($rbec_role_button_config)) {
            $migrated_permissions = array();
            
            foreach ($rbec_role_button_config as $role => $config) {
                $migrated_permissions[$role] = array(
                    'edit' => $config['show_edit'] ?? false,
                    'elementor' => $config['show_elementor'] ?? false
                );
            }
            
            update_option(self::ROLE_PERMISSIONS_OPTION, $migrated_permissions);
        }
        
        // Mark migration as complete
        update_option('rbec_permissions_migrated', true);
    }

    /**
     * Get all available WordPress roles
     * 
     * @return array Available roles
     */
    public static function get_available_roles() {
        $wp_roles = wp_roles();
        $roles = array();
        
        foreach ($wp_roles->get_names() as $role => $name) {
            $roles[$role] = array(
                'name' => $name,
                'permissions' => self::get_role_permissions($role)
            );
        }
        
        return $roles;
    }
}

// Initialize the permission system
add_action('init', array('RBEC_Permissions', 'init'));

// Backward compatibility functions
function uipress_user_can_see_edit_button() {
    return RBEC_Permissions::user_can_see_button(get_current_user_id(), 'edit');
}

function uipress_user_can_see_elementor_button() {
    return RBEC_Permissions::user_can_see_button(get_current_user_id(), 'elementor');
}
