=== Role-Based Edit Control ===
Contributors: arifhossin
Tags: permissions, roles, user-roles, access-control, elementor, admin, security, capabilities
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control who can see edit buttons in WordPress admin. Simple, visual interface to manage permissions by role or individual user.

== Description ==

**Role-Based Edit Control** gives you complete control over who can see edit buttons in your WordPress admin area. Perfect for multi-author sites, client projects, and any situation where you need granular permission control.

### 🎯 Key Features

* **Visual Interface** - No code editing required, manage everything through a clean admin panel
* **Role-Based Permissions** - Set default permissions for entire user roles
* **Individual User Overrides** - Target specific users with custom permissions
* **Dual Button Control** - Manage both classic Edit and "Edit with Elementor" buttons
* **Bulk Operations** - Update multiple users at once with bulk actions
* **Export/Import** - Backup and restore your permission settings
* **Keyboard Shortcuts** - Power user features for faster workflow (Ctrl+S to save, Ctrl+F to search)
* **Permission Matrix** - Visual overview of all permissions at a glance
* **UiPress Compatible** - Works seamlessly with UiPress admin theme

### 💡 Perfect For

* **Multi-author websites** - Control who can edit what
* **Client projects** - Prevent accidental edits by clients
* **WooCommerce shops** - Manage shop manager permissions
* **Membership sites** - Restrict editing by membership level
* **Agency workflows** - Give clients view-only access

### 🚀 How It Works

The plugin uses a simple **priority system**:
1. **User Override** (highest priority) - Individual user settings
2. **Role Permission** (default) - Role-based settings
3. **Deny Access** (fallback) - Default deny if no settings

### ✨ Simple to Use

1. Go to Settings → Role-Based Edit Control
2. Set role permissions (default for everyone in that role)
3. Add user overrides (for specific individuals)
4. Save and you're done!

### 🔒 Security Features

* Proper WordPress nonces on all actions
* Capability checks (only admins can manage)
* Sanitized inputs and escaped outputs
* No external API calls
* GPL-licensed and open source

### 🌟 Premium Features (Built-in, Free!)

* Bulk operations for managing multiple users
* Visual permission matrix
* Export/Import functionality
* Keyboard shortcuts
* Real-time user search
* Automatic migration from old settings

== Installation ==

### Automatic Installation

1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "Role-Based Edit Control"
4. Click "Install Now" and then "Activate"
5. Go to Settings → Role-Based Edit Control to configure

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/role-based-edit-control/`
3. Activate through the WordPress Plugins menu
4. Go to Settings → Role-Based Edit Control to configure

### Configuration

1. Navigate to **Settings → Role-Based Edit Control**
2. Click the **Role Permissions** tab
3. Check/uncheck permissions for each role
4. Click **Save All Role Permissions**
5. (Optional) Add user overrides in the **User Overrides** tab

== Frequently Asked Questions ==

= Does this work with Elementor? =

Yes! The plugin specifically supports hiding "Edit with Elementor" buttons separately from classic WordPress edit buttons.

= Can I target specific users instead of just roles? =

Absolutely! You can add individual user overrides that take priority over role-based permissions.

= Will this break my site if I make a mistake? =

No. The plugin only controls button visibility - it doesn't delete or modify any content. You can always reset to defaults with one click.

= Is it compatible with UiPress? =

Yes, the plugin was designed to work seamlessly with UiPress admin theme.

= Can I bulk-update multiple users at once? =

Yes! Select multiple users and apply bulk actions like enabling/disabling edit or Elementor buttons.

= Does it work with custom post types? =

Yes, the plugin works with all post types including custom post types.

= Can I export/import my settings? =

Yes, there's a built-in export/import feature in the Quick Actions tab.

= What happens to Shop Managers by default? =

By default, Shop Managers have view-only access (no edit buttons). You can change this in settings.

= Does it support multisite? =

The plugin works on multisite installations. Settings are managed per-site.

= How do I give one Editor access to Elementor? =

1. Go to User Overrides tab
2. Search for the user's name
3. Click on them to add an override
4. Check "Elementor" permission
5. Click Save

== Screenshots ==

1. **Role Permissions Tab** - Set default permissions for each WordPress role with simple checkboxes
2. **User Overrides Tab** - Search and add individual user overrides that take priority over roles
3. **Permission Matrix** - Visual overview showing all role permissions at a glance
4. **Bulk Operations** - Select multiple users and apply bulk actions efficiently
5. **Quick Actions** - Export/import settings and test current user permissions

== Changelog ==

= 1.0.0 - 2024-01-20 =
* Initial release
* Role-based permission control for Edit and Elementor buttons
* Individual user override system with priority handling
* Visual admin interface with tabbed navigation
* Bulk operations for managing multiple users
* Permission matrix for quick overview
* Export/Import functionality for backup/migration
* Keyboard shortcuts (Ctrl+S, Ctrl+F, F1)
* Real-time user search with AJAX
* Automatic migration from legacy configurations
* WP-CLI support for command-line management
* Full sanitization and security measures
* UiPress compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of Role-Based Edit Control. Simple, powerful permission management for WordPress edit buttons.

== Additional Info ==

**Development**
This plugin is actively maintained. Report bugs and request features on the support forum.

**Credits**
Developed with ❤️ for the WordPress community.

**Support**
For support, please use the WordPress.org support forums.
