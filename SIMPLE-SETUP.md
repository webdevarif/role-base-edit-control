# Simple Setup Guide - Role-Based Edit Control

## 🎯 **What's New: Simple User-Based System**

The plugin now has a **much simpler interface** that lets you:
- ✅ **Target specific users** (individual people)
- ✅ **Target user roles** (groups)  
- ✅ **Give permissions** with simple checkboxes
- ✅ **Save and apply** instantly

## 🚀 **Quick Start (3 Steps)**

### 1. **Go to Settings**
Navigate to: **Settings → Role-Based Edit Control**

### 2. **Set Role Permissions** (Default for everyone)
- Click the **"Role Permissions"** tab
- Check/uncheck boxes for each role:
  - **Administrator**: ✅ Edit ✅ Elementor
  - **Editor**: ✅ Edit ❌ Elementor  
  - **Shop Manager**: ❌ Edit ❌ Elementor
  - **Author**: ❌ Edit ❌ Elementor
- Click **"Save All Role Permissions"**

### 3. **Add User Overrides** (Optional - for specific people)
- Click the **"User Overrides"** tab
- Search for a user by name/email
- Click on the user to add them
- Check/uncheck their individual permissions
- Click **"Save"**

**Done!** ✅ Changes apply immediately.

---

## 📋 **How It Works (Priority System)**

1. **User Override** (highest priority)
   - If a user has individual settings → Use those
   
2. **Role Permission** (default)
   - If no individual settings → Use their role's default
   
3. **No Access** (fallback)
   - If no settings found → Deny access

---

## 🎯 **Example Use Cases**

### **Case 1: Give One Editor Elementor Access**
**Problem**: All Editors can't use Elementor, but you want John (an Editor) to use it.

**Solution**:
1. Go to **User Overrides** tab
2. Search "John"
3. Click on John
4. Check **"Elementor"** box
5. Click **"Save"**

**Result**: John can now use Elementor while other Editors still can't.

### **Case 2: Temporarily Disable All Authors**
**Problem**: You want to temporarily prevent all Authors from editing.

**Solution**:
1. Go to **Role Permissions** tab
2. Find **Author** row
3. Uncheck **"Edit"** box
4. Click **"Save All Role Permissions"**

**Result**: All Authors lose edit access immediately.

### **Case 3: See Who Can Do What**
**Problem**: You want to see all current permissions at a glance.

**Solution**:
1. Look at the **Role Permissions** table
2. Look at the **User Overrides** table
3. Everything is visual and clear

---

## 🔧 **Advanced Features**

### **Export/Import Settings**
- **Export**: Download your settings as JSON file
- **Import**: Upload JSON file to restore settings
- **Perfect for**: Backup, migration, or copying settings between sites

### **Reset to Defaults**
- One-click reset of all permissions
- Returns to original plugin defaults
- **Use when**: You want to start fresh

### **Test Current User**
- See what the current user can actually do
- Shows effective permissions (role + overrides)
- **Great for**: Debugging permission issues

---

## 🆚 **Old vs New System**

### **Old System (Complex)**:
```
❌ Edit PHP files
❌ Add arrays and code
❌ Upload via FTP
❌ Hope it works
❌ Only role-based
❌ Hard to debug
```

### **New System (Simple)**:
```
✅ Click checkboxes in WordPress admin
✅ Search and add users visually
✅ Save with one click
✅ See results immediately
✅ Both users AND roles
✅ Easy to test and debug
```

---

## 📱 **Interface Overview**

### **Tab 1: Role Permissions**
- Simple table with checkboxes
- Shows all WordPress roles
- Set default permissions for each role
- Bulk save all changes

### **Tab 2: User Overrides**
- Search bar to find users
- Visual list of users with overrides
- Individual permission checkboxes
- Easy to add/remove overrides

### **Tab 3: Quick Actions**
- Export/Import settings
- Reset to defaults
- Test current user permissions
- Maintenance tools

---

## 🎯 **Permission Types**

### **Edit Button**
- Controls access to classic WordPress "Edit" button
- Shows in post/page lists, admin bar, etc.

### **Elementor Button**
- Controls access to "Edit with Elementor" button
- Shows in post/page lists, admin bar, etc.

---

## 🔍 **Troubleshooting**

### **"User can't see buttons but should"**
1. Go to **User Overrides** tab
2. Search for the user
3. If they have overrides, check the settings
4. If no overrides, check their **Role Permissions**

### **"Changes not applying"**
1. Make sure you clicked **"Save"** after making changes
2. Try refreshing the page
3. Check if you have the right user permissions

### **"Can't find a user"**
1. Make sure you're searching by name or email
2. Try different search terms
3. Check if the user exists in WordPress

---

## 🎉 **Benefits**

✅ **No Code Required** - Everything in WordPress admin
✅ **Visual Interface** - See permissions at a glance  
✅ **Individual Users** - Override any user's permissions
✅ **Role Defaults** - Set permissions for entire roles
✅ **Priority System** - User overrides beat role defaults
✅ **One-Click Save** - Changes apply immediately
✅ **Export/Import** - Backup and restore easily
✅ **Easy Testing** - Test permissions instantly

---

## 🚀 **Ready to Use!**

The simplified system is now active. Go to **Settings → Simple Role Buttons** to start managing permissions the easy way!

**Need Help?** Check the main README.md for advanced features and WP-CLI commands.
