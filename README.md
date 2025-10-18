# Role-Based Edit Control

A WordPress plugin that allows you to control the visibility of edit buttons based on user roles with a simple, visual interface. Target individual users or entire roles for Edit and Elementor buttons.

[![WordPress.org](https://img.shields.io/badge/WordPress.org-Plugin-blue.svg)](https://wordpress.org/plugins/role-based-edit-control/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-green.svg)](https://php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)

## Features

- **Role-Based Control**: Hide/show Edit and "Edit with Elementor" buttons based on user roles
- **UiPress Compatible**: Designed to work with UiPress dashboard panels without breaking layout
- **Elementor Integration**: Full support for Elementor edit buttons and admin menu items
- **Scalable Configuration**: Easy to add new roles or modify button visibility rules
- **Dual Strategy**: PHP hooks for server-side filtering + JavaScript for client-side fallback
- **Debug Support**: Built-in debugging for troubleshooting role assignments

## Installation

1. Upload the plugin files to your WordPress installation
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically start working based on the default role configuration

## Admin Settings Page

The plugin includes a visual admin settings page for easy configuration:

1. Go to **Settings > Role Button Settings** in your WordPress admin
2. Configure which buttons each user role can see
3. Use the **Test Current User** button to verify permissions
4. Export/import configuration for backup or migration
5. Reset to defaults if needed

### Quick Actions Available:
- **Reset to Defaults**: Restore original configuration
- **Export Configuration**: Download settings as JSON file
- **Test Current User**: Check what the current user can see

## WP-CLI Commands

The plugin provides comprehensive command-line management through WP-CLI:

### Basic Commands
```bash
# List all roles and permissions
wp rbec list-roles

# Update role permissions
wp rbec update-role editor --elementor=true

# Test user permissions
wp rbec test-user --user-id=123

# Export configuration
wp rbec export config.json

# Import configuration
wp rbec import config.json

# Bulk update multiple roles
wp rbec bulk-update --roles="editor,author" --edit=false
```

### Advanced Features
- **Configuration Export/Import**: Backup and migrate settings
- **Bulk Updates**: Update multiple roles simultaneously
- **User Testing**: Test permissions for any user
- **Dry Run Mode**: Preview changes before applying
- **Multiple Formats**: JSON and PHP export/import support

See [WP-CLI.md](WP-CLI.md) for complete documentation and examples.

## Default Role Configuration

| Role | Edit Button | Elementor Button | Description |
|------|-------------|------------------|-------------|
| Administrator | ✅ Yes | ✅ Yes | Full access to all edit buttons |
| Editor | ✅ Yes | ❌ No | Can edit but not with Elementor |
| Shop Manager | ❌ No | ❌ No | View-only access |
| Author | ❌ No | ❌ No | View-only access |
| Contributor | ❌ No | ❌ No | View-only access |
| Subscriber | ❌ No | ❌ No | View-only access |

## How It Works

### Server-Side (PHP)
The plugin uses WordPress hooks to filter button visibility:

- `post_row_actions` / `page_row_actions` - Removes buttons from post/page list tables
- `admin_bar_menu` - Controls admin bar edit links
- `get_edit_post_link` - Filters edit post links
- `elementor/editor/footer` - Blocks Elementor editor access
- `admin_menu` - Hides Elementor admin menu items

### Client-Side (JavaScript)
JavaScript provides fallback hiding for elements not caught by PHP:

- DOM manipulation for dynamically loaded content
- UiPress dashboard panel integration
- Mutation observers for real-time content updates
- AJAX completion handlers for AJAX-loaded content

## Customization

### Adding New Roles

To add a new role with custom button visibility, edit `includes/role-config.php`:

```php
// Add to the $rbec_role_button_config array
'custom_role' => array(
    'show_edit' => true,           // Allow classic edit button
    'show_elementor' => false,     // Disallow Elementor button
    'description' => 'Custom role with limited edit access'
),
```

### Modifying Existing Roles

To change button visibility for existing roles:

```php
// Modify the existing role configuration
'editor' => array(
    'show_edit' => false,          // Remove edit access
    'show_elementor' => false,     // Keep Elementor disabled
    'description' => 'Editor role updated to view-only'
),
```

### Adding Custom Button Types

To add new button types beyond Edit and Elementor:

1. **Update Role Configuration** (`includes/role-config.php`):
```php
'administrator' => array(
    'show_edit' => true,
    'show_elementor' => true,
    'show_custom_button' => true,  // New button type
    'description' => 'Administrator with custom button access'
),
```

2. **Update Helper Functions** (`includes/role-config.php`):
```php
function rbec_user_can_see_custom_button() {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    foreach ($user_roles as $role) {
        $config = rbec_get_role_button_config($role);
        if ($config['show_custom_button']) {
            return true;
        }
    }
    
    return false;
}
```

3. **Update Button Manager** (`includes/class-role-button-manager.php`):
```php
// Add new filter methods
public function filter_custom_button($actions, $post) {
    if (!rbec_user_can_see_custom_button()) {
        unset($actions['custom_action']);
    }
    return $actions;
}
```

4. **Update JavaScript** (`assets/js/admin-button-control.js`):
```php
// Add to selectors configuration
customButtons: [
    '.custom-button',
    'a[href*="custom-action"]'
]
```

## Debugging

Enable WordPress debug mode to see role-based decisions in your error logs:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

The plugin will log:
- Current user roles
- Button visibility decisions
- Role configuration checks

## Hooks and Filters

### Available Filters

- `uipress_role_button_config` - Modify role configuration
- `rbec_user_can_see_edit_button` - Override edit button visibility
- `rbec_user_can_see_elementor_button` - Override Elementor button visibility

### Example Usage

```php
// Modify role configuration
add_filter('uipress_role_button_config', function($config) {
    $config['editor']['show_edit'] = false;
    return $config;
});

// Override button visibility
add_filter('rbec_user_can_see_edit_button', function($can_see) {
    // Custom logic here
    return $can_see;
});
```

## Browser Console Commands

When debug mode is enabled, you can use these console commands:

```javascript
// Check current configuration
RBECRoleButtonController.config

// Manually hide buttons
RBECRoleButtonController.hideButtons()

// Check if user can see specific buttons
RBECRoleButtonController.userCanSeeEditButton()
RBECRoleButtonController.userCanSeeElementorButton()
```

## Troubleshooting

### Buttons Still Visible

1. **Check Role Assignment**: Ensure users have the correct roles assigned
2. **Clear Cache**: Clear any caching plugins or server-side cache
3. **Check JavaScript Console**: Look for errors in browser console
4. **Enable Debug Mode**: Check WordPress error logs for role decisions

### UiPress Dashboard Issues

1. **Panel Loading**: UiPress panels load dynamically - the plugin handles this automatically
2. **Custom Widgets**: If you have custom UiPress widgets, you may need to add custom selectors
3. **Theme Conflicts**: Test with default WordPress theme to isolate issues

### Elementor Integration

1. **Plugin Activation**: Ensure Elementor is active for Elementor-specific features
2. **Menu Items**: Elementor admin menu items are hidden for restricted roles
3. **Editor Access**: Direct Elementor editor URLs are blocked for restricted roles

## File Structure

```
uipress-extended/
├── uipress-role-buttons.php          # Main plugin file
├── includes/
│   ├── class-role-button-manager.php # Core button management logic
│   └── role-config.php               # Role configuration and helper functions
├── assets/
│   └── js/
│       └── admin-button-control.js   # Client-side button hiding
└── README.md                         # This documentation
```

## Support

For issues or questions:

1. Check the WordPress error logs for debug information
2. Verify user roles are correctly assigned
3. Test with default WordPress theme and minimal plugins
4. Review browser console for JavaScript errors

## Changelog

### Version 1.2.0 (WP-CLI Enhanced)
- **WP-CLI Support**: Complete command-line management system
- **Bulk Operations**: Update multiple roles simultaneously
- **Configuration Management**: Export/import with validation
- **User Testing**: Test permissions for any user from command line
- **Advanced Features**: Dry-run mode, multiple formats, error handling
- **Documentation**: Comprehensive WP-CLI usage guide

### Version 1.1.0 (Enhanced)
- **Critical Fixes**: JavaScript-PHP role synchronization, proper uninstall cleanup
- **Performance**: User role caching to reduce database queries
- **Stability**: Enhanced error handling with try-catch blocks
- **Modern WordPress**: Support for bulk actions and newer hooks
- **Admin Interface**: Visual settings page for non-technical users
- **Configuration**: Built-in validation and error reporting
- **Testing**: User permission testing and configuration export/import

### Version 1.0.0 (Initial Release)
- Role-based button visibility for Edit and Elementor buttons
- UiPress dashboard compatibility
- JavaScript fallback for dynamic content
- Comprehensive role configuration system
