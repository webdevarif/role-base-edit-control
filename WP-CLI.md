# WP-CLI Commands for RBEC Role-Based Edit Control

This plugin provides comprehensive WP-CLI commands for managing role-based button visibility from the command line.

## Installation

WP-CLI commands are automatically available when:
1. The plugin is activated
2. WP-CLI is installed on your system

## Available Commands

### `wp rbec list-roles`

List all available roles and their button permissions.

**Examples:**
```bash
# List all roles in table format
wp rbec list-roles

# List roles in JSON format
wp rbec list-roles --format=json

# List roles in CSV format
wp rbec list-roles --format=csv
```

**Output:**
```
+---------------+------------+-----------------+-----------------------------------+
| Role          | Edit Button| Elementor Button| Description                       |
+---------------+------------+-----------------+-----------------------------------+
| administrator | Yes        | Yes             | Administrators can see all edit   |
|               |            |                 | buttons                           |
| editor        | Yes        | No              | Editors can see classic edit      |
|               |            |                 | button only                       |
| shop_manager  | No         | No              | Shop Managers have view-only      |
|               |            |                 | access                            |
+---------------+------------+-----------------+-----------------------------------+
```

---

### `wp rbec update-role`

Update role permissions for a specific role.

**Options:**
- `--edit=<value>`: Set edit button permission (true/false)
- `--elementor=<value>`: Set Elementor button permission (true/false)

**Examples:**
```bash
# Allow editors to use Elementor
wp rbec update-role editor --elementor=true

# Remove edit permissions for authors
wp rbec update-role author --edit=false

# Update both permissions for shop_manager
wp rbec update-role shop_manager --edit=true --elementor=false
```

---

### `wp rbec test-user`

Test current user's permissions or a specific user's permissions.

**Options:**
- `--user-id=<id>`: Test specific user by ID

**Examples:**
```bash
# Test current user's permissions
wp rbec test-user

# Test specific user's permissions
wp rbec test-user --user-id=123
```

**Output:**
```
User: John Doe (ID: 1)
Roles: administrator
Can see Edit buttons: Yes
Can see Elementor buttons: Yes

Role Details:
  administrator: Edit=Yes, Elementor=Yes
```

---

### `wp rbec export`

Export current configuration to a file.

**Options:**
- `--format=<format>`: Export format (json, php)

**Examples:**
```bash
# Export to JSON file
wp rbec export config.json

# Export to PHP file
wp rbec export config.php --format=php
```

**Generated JSON file:**
```json
{
    "administrator": {
        "show_edit": true,
        "show_elementor": true,
        "description": "Administrators can see all edit buttons"
    },
    "editor": {
        "show_edit": true,
        "show_elementor": false,
        "description": "Editors can see classic edit button only"
    }
}
```

---

### `wp rbec import`

Import configuration from a file.

**Options:**
- `--format=<format>`: Import format (json, php, auto)
- `--dry-run`: Preview the import without applying changes

**Examples:**
```bash
# Import from JSON file
wp rbec import config.json

# Import from PHP file
wp rbec import config.php --format=php

# Preview import without applying
wp rbec import config.json --dry-run
```

**Import file format (JSON):**
```json
{
    "editor": {
        "show_edit": true,
        "show_elementor": true,
        "description": "Updated: Editors can now use Elementor"
    },
    "author": {
        "show_edit": false,
        "show_elementor": false,
        "description": "Authors have view-only access"
    }
}
```

---

### `wp rbec reset`

Reset configuration to defaults.

**Options:**
- `--confirm`: Confirm the reset operation

**Examples:**
```bash
# Reset to defaults
wp rbec reset --confirm
```

**⚠️ Warning:** This will overwrite all current settings!

---

### `wp rbec bulk-update`

Bulk update multiple roles at once.

**Options:**
- `--file=<file>`: JSON file with role updates
- `--roles=<roles>`: Comma-separated list of roles to update
- `--edit=<value>`: Set edit permission for all specified roles
- `--elementor=<value>`: Set Elementor permission for all specified roles

**Examples:**
```bash
# Update multiple roles at once
wp rbec bulk-update --roles="editor,author,contributor" --edit=false

# Update from JSON file
wp rbec bulk-update --file=bulk-updates.json
```

**Bulk update JSON file format:**
```json
{
    "editor": {
        "show_edit": true,
        "show_elementor": false
    },
    "author": {
        "show_edit": false,
        "show_elementor": false
    },
    "contributor": {
        "show_edit": false,
        "show_elementor": false
    }
}
```

---

## Use Cases

### 1. **Site Migration**
```bash
# Export configuration from source site
wp rbec export production-config.json

# Import to destination site
wp rbec import production-config.json
```

### 2. **Bulk Permission Changes**
```bash
# Remove edit permissions for all non-admin roles
wp rbec bulk-update --roles="editor,author,contributor,subscriber" --edit=false
```

### 3. **Testing Different Configurations**
```bash
# Test current configuration
wp rbec test-user --user-id=123

# Make changes
wp rbec update-role editor --elementor=true

# Test again
wp rbec test-user --user-id=123
```

### 4. **Configuration Backup**
```bash
# Create timestamped backup
wp rbec export "backup-$(date +%Y%m%d-%H%M%S).json"
```

### 5. **Multi-site Management**
```bash
# Apply same configuration to multiple sites
for site in site1 site2 site3; do
    wp --url=$site rbec import shared-config.json
done
```

---

## Error Handling

The commands include comprehensive error handling:

- **Invalid role names**: Commands will warn about non-existent roles
- **Invalid values**: Boolean validation for true/false values
- **File errors**: Proper error messages for missing or invalid files
- **JSON validation**: Syntax checking for import files
- **Permission checks**: User capability verification

---

## Integration with Other Tools

### **Ansible Playbook Example**
```yaml
- name: Configure RBEC Role-Based Edit Control
  command: wp rbec import /path/to/config.json
  become_user: www-data
```

### **Docker Compose Example**
```yaml
services:
  wp-cli:
    image: wordpress:cli
    volumes:
      - ./config.json:/config.json
    command: wp rbec import /config.json
```

### **Shell Script Example**
```bash
#!/bin/bash
# Deploy configuration to multiple environments

ENVIRONMENTS=("staging" "production")
CONFIG_FILE="role-buttons-config.json"

for env in "${ENVIRONMENTS[@]}"; do
    echo "Deploying to $env..."
    wp --url="$env" rbec import "$CONFIG_FILE"
done
```

---

## Troubleshooting

### **Command Not Found**
```bash
# Check if WP-CLI is available
wp --version

# Check if plugin is active
wp plugin list | grep rbec
```

### **Permission Denied**
```bash
# Run as correct user
sudo -u www-data wp rbec list-roles
```

### **Invalid Configuration**
```bash
# Validate configuration before import
wp rbec import config.json --dry-run
```

---

## Advanced Usage

### **Custom Script Integration**
```php
<?php
// Custom PHP script using WP-CLI commands
$output = shell_exec('wp rbec list-roles --format=json');
$config = json_decode($output, true);

foreach ($config as $role => $settings) {
    echo "Role: $role\n";
    echo "Edit: " . ($settings['show_edit'] ? 'Yes' : 'No') . "\n";
    echo "Elementor: " . ($settings['show_elementor'] ? 'Yes' : 'No') . "\n\n";
}
?>
```

### **Cron Job Example**
```bash
# Daily configuration backup
0 2 * * * cd /path/to/wordpress && wp rbec export "/backups/role-config-$(date +\%Y\%m\%d).json"
```
