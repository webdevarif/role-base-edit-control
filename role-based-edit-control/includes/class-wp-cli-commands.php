<?php
/**
 * WP-CLI Commands for UiPress Role-Based Button Visibility Plugin
 * 
 * This class provides command-line management capabilities for the plugin,
 * allowing administrators to manage role permissions from the command line.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only load if WP-CLI is available
if (!class_exists('WP_CLI')) {
    return;
}

/**
 * UiPress Role Buttons WP-CLI Commands Class
 */
class RBEC_WP_CLI_Commands {

    /**
     * List all available roles and their button permissions
     * 
     * ## EXAMPLES
     * 
     *     wp uipress-role-buttons list-roles
     *     wp uipress-role-buttons list-roles --format=table
     *     wp uipress-role-buttons list-roles --format=json
     * 
     * @when after_wp_load
     */
    public function list_roles($args, $assoc_args) {
        global $uipress_role_button_config;
        
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        
        if ($format === 'json') {
            WP_CLI::line(json_encode($uipress_role_button_config, JSON_PRETTY_PRINT));
            return;
        }
        
        // Prepare table data
        $table_data = array();
        foreach ($uipress_role_button_config as $role => $config) {
            $table_data[] = array(
                'Role' => $role,
                'Edit Button' => $config['show_edit'] ? 'Yes' : 'No',
                'Elementor Button' => $config['show_elementor'] ? 'Yes' : 'No',
                'Description' => $config['description']
            );
        }
        
        WP_CLI\Utils\format_items($format, $table_data, array('Role', 'Edit Button', 'Elementor Button', 'Description'));
    }

    /**
     * Update role permissions for a specific role
     * 
     * ## OPTIONS
     * 
     * <role>
     * : The role slug to update
     * 
     * --edit=<value>
     * : Set edit button permission (true/false)
     * 
     * --elementor=<value>
     * : Set Elementor button permission (true/false)
     * 
     * ## EXAMPLES
     * 
     *     wp uipress-role-buttons update-role editor --edit=true --elementor=false
     *     wp uipress-role-buttons update-role shop_manager --edit=false --elementor=false
     * 
     * @when after_wp_load
     */
    public function update_role($args, $assoc_args) {
        global $uipress_role_button_config;
        
        if (empty($args[0])) {
            WP_CLI::error('Role name is required');
        }
        
        $role = $args[0];
        
        if (!isset($uipress_role_button_config[$role])) {
            WP_CLI::error("Role '{$role}' not found in configuration");
        }
        
        $updated = false;
        
        if (isset($assoc_args['edit'])) {
            $edit_value = filter_var($assoc_args['edit'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($edit_value === null) {
                WP_CLI::error('Invalid edit value. Use true or false.');
            }
            $uipress_role_button_config[$role]['show_edit'] = $edit_value;
            $updated = true;
        }
        
        if (isset($assoc_args['elementor'])) {
            $elementor_value = filter_var($assoc_args['elementor'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($elementor_value === null) {
                WP_CLI::error('Invalid elementor value. Use true or false.');
            }
            $uipress_role_button_config[$role]['show_elementor'] = $elementor_value;
            $updated = true;
        }
        
        if (!$updated) {
            WP_CLI::error('No valid parameters provided. Use --edit and/or --elementor');
        }
        
        // Save the updated configuration
        $this->save_configuration($uipress_role_button_config);
        
        WP_CLI::success("Role '{$role}' updated successfully");
        
        // Show updated role info
        $config = $uipress_role_button_config[$role];
        WP_CLI::line("Edit Button: " . ($config['show_edit'] ? 'Yes' : 'No'));
        WP_CLI::line("Elementor Button: " . ($config['show_elementor'] ? 'Yes' : 'No'));
    }

    /**
     * Test current user's permissions
     * 
     * ## EXAMPLES
     * 
     *     wp uipress-role-buttons test-user
     *     wp uipress-role-buttons test-user --user-id=123
     * 
     * @when after_wp_load
     */
    public function test_user($args, $assoc_args) {
        $user_id = isset($assoc_args['user-id']) ? (int) $assoc_args['user-id'] : get_current_user_id();
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            WP_CLI::error("User with ID {$user_id} not found");
        }
        
        // Switch to the user context
        wp_set_current_user($user_id);
        
        $can_edit = uipress_user_can_see_edit_button();
        $can_elementor = uipress_user_can_see_elementor_button();
        
        WP_CLI::line("User: {$user->display_name} (ID: {$user_id})");
        WP_CLI::line("Roles: " . implode(', ', $user->roles));
        WP_CLI::line("Can see Edit buttons: " . ($can_edit ? 'Yes' : 'No'));
        WP_CLI::line("Can see Elementor buttons: " . ($can_elementor ? 'Yes' : 'No'));
        
        // Show role details
        WP_CLI::line("\nRole Details:");
        foreach ($user->roles as $role) {
            $config = uipress_get_role_button_config($role);
            WP_CLI::line("  {$role}: Edit=" . ($config['show_edit'] ? 'Yes' : 'No') . 
                        ", Elementor=" . ($config['show_elementor'] ? 'Yes' : 'No'));
        }
    }

    /**
     * Export current configuration to a file
     * 
     * ## OPTIONS
     * 
     * <file>
     * : Path to the export file
     * 
     * --format=<format>
     * : Export format (json, php)
     * 
     * ## EXAMPLES
     * 
     *     wp uipress-role-buttons export config.json
     *     wp uipress-role-buttons export config.php --format=php
     * 
     * @when after_wp_load
     */
    public function export($args, $assoc_args) {
        global $uipress_role_button_config;
        
        if (empty($args[0])) {
            WP_CLI::error('Export file path is required');
        }
        
        $file_path = $args[0];
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'json';
        
        if ($format === 'php') {
            $content = "<?php\n";
            $content .= "/**\n";
            $content .= " * Exported UiPress Role Buttons Configuration\n";
            $content .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
            $content .= " */\n\n";
            $content .= "\$uipress_role_button_config = " . var_export($uipress_role_button_config, true) . ";\n";
        } else {
            $content = json_encode($uipress_role_button_config, JSON_PRETTY_PRINT);
        }
        
        $result = file_put_contents($file_path, $content);
        
        if ($result === false) {
            WP_CLI::error("Failed to write to file: {$file_path}");
        }
        
        WP_CLI::success("Configuration exported to: {$file_path}");
    }

    /**
     * Import configuration from a file
     * 
     * ## OPTIONS
     * 
     * <file>
     * : Path to the import file
     * 
     * --format=<format>
     * : Import format (json, php, auto)
     * 
     * --dry-run
     * : Preview the import without applying changes
     * 
     * ## EXAMPLES
     * 
     *     wp uipress-role-buttons import config.json
     *     wp uipress-role-buttons import config.php --format=php
     *     wp uipress-role-buttons import config.json --dry-run
     * 
     * @when after_wp_load
     */
    public function import($args, $assoc_args) {
        global $uipress_role_button_config;
        
        if (empty($args[0])) {
            WP_CLI::error('Import file path is required');
        }
        
        $file_path = $args[0];
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'auto';
        $dry_run = isset($assoc_args['dry-run']);
        
        if (!file_exists($file_path)) {
            WP_CLI::error("File not found: {$file_path}");
        }
        
        $content = file_get_contents($file_path);
        if ($content === false) {
            WP_CLI::error("Failed to read file: {$file_path}");
        }
        
        // Auto-detect format
        if ($format === 'auto') {
            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            $format = $extension === 'php' ? 'php' : 'json';
        }
        
        // Parse the configuration
        if ($format === 'php') {
            // For PHP files, we'll parse the content safely
            // Extract array content from PHP file
            if (preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*array\s*\((.*)\)\s*;/s', $content, $matches)) {
                // This is a basic parser - for complex configs, consider using a proper parser
                WP_CLI::error('PHP configuration import is not supported for security reasons. Please use JSON format instead.');
                return;
            }
            $temp_config = null;
        } else {
            $temp_config = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                WP_CLI::error('Invalid JSON format: ' . json_last_error_msg());
            }
        }
        
        if (!is_array($temp_config)) {
            WP_CLI::error('Invalid configuration format');
        }
        
        // Validate the configuration
        $errors = uipress_validate_role_config($temp_config);
        if (!empty($errors)) {
            WP_CLI::error('Configuration validation failed: ' . implode(', ', $errors));
        }
        
        if ($dry_run) {
            WP_CLI::line('DRY RUN - Configuration preview:');
            WP_CLI\Utils\format_items('table', $this->config_to_table_data($temp_config), 
                                    array('Role', 'Edit Button', 'Elementor Button', 'Description'));
            WP_CLI::line('Use without --dry-run to apply these changes.');
            return;
        }
        
        // Apply the configuration
        $this->save_configuration($temp_config);
        
        WP_CLI::success('Configuration imported successfully');
        
        // Show summary
        WP_CLI\Utils\format_items('table', $this->config_to_table_data($temp_config), 
                                array('Role', 'Edit Button', 'Elementor Button', 'Description'));
    }

    /**
     * Reset configuration to defaults
     * 
     * ## OPTIONS
     * 
     * --confirm
     * : Confirm the reset operation
     * 
     * ## EXAMPLES
     * 
     *     wp uipress-role-buttons reset --confirm
     * 
     * @when after_wp_load
     */
    public function reset($args, $assoc_args) {
        if (!isset($assoc_args['confirm'])) {
            WP_CLI::error('Reset requires --confirm flag');
        }
        
        // Get default configuration
        $default_config = $this->get_default_configuration();
        
        // Save the default configuration
        $this->save_configuration($default_config);
        
        WP_CLI::success('Configuration reset to defaults');
        
        // Show reset configuration
        WP_CLI\Utils\format_items('table', $this->config_to_table_data($default_config), 
                                array('Role', 'Edit Button', 'Elementor Button', 'Description'));
    }

    /**
     * Bulk update multiple roles
     * 
     * ## OPTIONS
     * 
     * --file=<file>
     * : JSON file with role updates
     * 
     * --roles=<roles>
     * : Comma-separated list of roles to update
     * 
     * --edit=<value>
     * : Set edit permission for all specified roles
     * 
     * --elementor=<value>
     * : Set Elementor permission for all specified roles
     * 
     * ## EXAMPLES
     * 
     *     wp uipress-role-buttons bulk-update --roles="editor,author" --edit=false
     *     wp uipress-role-buttons bulk-update --file=bulk-updates.json
     * 
     * @when after_wp_load
     */
    public function bulk_update($args, $assoc_args) {
        global $uipress_role_button_config;
        
        if (isset($assoc_args['file'])) {
            $this->bulk_update_from_file($assoc_args['file']);
            return;
        }
        
        if (!isset($assoc_args['roles'])) {
            WP_CLI::error('Either --file or --roles is required');
        }
        
        $roles = explode(',', $assoc_args['roles']);
        $roles = array_map('trim', $roles);
        
        $edit_value = null;
        $elementor_value = null;
        
        if (isset($assoc_args['edit'])) {
            $edit_value = filter_var($assoc_args['edit'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($edit_value === null) {
                WP_CLI::error('Invalid edit value. Use true or false.');
            }
        }
        
        if (isset($assoc_args['elementor'])) {
            $elementor_value = filter_var($assoc_args['elementor'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($elementor_value === null) {
                WP_CLI::error('Invalid elementor value. Use true or false.');
            }
        }
        
        if ($edit_value === null && $elementor_value === null) {
            WP_CLI::error('At least one of --edit or --elementor is required');
        }
        
        $updated_roles = array();
        
        foreach ($roles as $role) {
            if (!isset($uipress_role_button_config[$role])) {
                WP_CLI::warning("Role '{$role}' not found, skipping");
                continue;
            }
            
            if ($edit_value !== null) {
                $uipress_role_button_config[$role]['show_edit'] = $edit_value;
            }
            
            if ($elementor_value !== null) {
                $uipress_role_button_config[$role]['show_elementor'] = $elementor_value;
            }
            
            $updated_roles[] = $role;
        }
        
        if (empty($updated_roles)) {
            WP_CLI::error('No valid roles to update');
        }
        
        // Save the updated configuration
        $this->save_configuration($uipress_role_button_config);
        
        WP_CLI::success('Updated roles: ' . implode(', ', $updated_roles));
    }

    /**
     * Bulk update from JSON file
     */
    private function bulk_update_from_file($file_path) {
        global $uipress_role_button_config;
        
        if (!file_exists($file_path)) {
            WP_CLI::error("File not found: {$file_path}");
        }
        
        $content = file_get_contents($file_path);
        $updates = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            WP_CLI::error('Invalid JSON format: ' . json_last_error_msg());
        }
        
        if (!is_array($updates)) {
            WP_CLI::error('Invalid update format');
        }
        
        $updated_roles = array();
        
        foreach ($updates as $role => $permissions) {
            if (!isset($uipress_role_button_config[$role])) {
                WP_CLI::warning("Role '{$role}' not found, skipping");
                continue;
            }
            
            if (isset($permissions['show_edit'])) {
                $uipress_role_button_config[$role]['show_edit'] = (bool) $permissions['show_edit'];
            }
            
            if (isset($permissions['show_elementor'])) {
                $uipress_role_button_config[$role]['show_elementor'] = (bool) $permissions['show_elementor'];
            }
            
            $updated_roles[] = $role;
        }
        
        if (empty($updated_roles)) {
            WP_CLI::error('No valid roles to update');
        }
        
        // Save the updated configuration
        $this->save_configuration($uipress_role_button_config);
        
        WP_CLI::success('Updated roles: ' . implode(', ', $updated_roles));
    }

    /**
     * Save configuration (placeholder - would need proper implementation)
     */
    private function save_configuration($config) {
        // In a real implementation, you would save this to the database
        // For now, we'll just update the global variable
        global $uipress_role_button_config;
        $uipress_role_button_config = $config;
        
        // You could also save to an option or custom table
        // update_option('uipress_role_button_config', $config);
        
        WP_CLI::log('Configuration saved (note: changes are temporary in this implementation)');
    }

    /**
     * Get default configuration
     */
    private function get_default_configuration() {
        return array(
            'administrator' => array(
                'show_edit' => true,
                'show_elementor' => true,
                'description' => 'Administrators can see all edit buttons'
            ),
            'editor' => array(
                'show_edit' => true,
                'show_elementor' => false,
                'description' => 'Editors can see classic edit button only'
            ),
            'shop_manager' => array(
                'show_edit' => false,
                'show_elementor' => false,
                'description' => 'Shop Managers have view-only access'
            ),
            'author' => array(
                'show_edit' => false,
                'show_elementor' => false,
                'description' => 'Authors have view-only access'
            ),
            'contributor' => array(
                'show_edit' => false,
                'show_elementor' => false,
                'description' => 'Contributors have view-only access'
            ),
            'subscriber' => array(
                'show_edit' => false,
                'show_elementor' => false,
                'description' => 'Subscribers have view-only access'
            )
        );
    }

    /**
     * Convert configuration to table data
     */
    private function config_to_table_data($config) {
        $table_data = array();
        foreach ($config as $role => $settings) {
            $table_data[] = array(
                'Role' => $role,
                'Edit Button' => $settings['show_edit'] ? 'Yes' : 'No',
                'Elementor Button' => $settings['show_elementor'] ? 'Yes' : 'No',
                'Description' => $settings['description']
            );
        }
        return $table_data;
    }
}

// Register WP-CLI commands
WP_CLI::add_command('rbec', 'RBEC_WP_CLI_Commands');
