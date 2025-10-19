/**
 * Simple Admin JavaScript for RBEC Role-Based Button Visibility
 * 
 * This script provides a clean, interactive interface for managing
 * role permissions and user overrides with AJAX functionality.
 */

(function($) {
    'use strict';

    /**
     * RBEC Simple Admin Controller
     */
    var RBECSimpleAdmin = {
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                self.switchTab($(this).data('tab'));
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + S = Save all role permissions
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    $('.save-all-role-permissions').click();
                    self.showNotice('Saved all role permissions!', 'success');
                }
                
                // Ctrl/Cmd + F = Focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    $('#user-search').focus();
                }
                
                // F1 = Show keyboard shortcuts
                if (e.key === 'F1') {
                    e.preventDefault();
                    self.toggleKeyboardShortcuts();
                }
            });
            
            // Role permission checkboxes
            $('.role-permission').on('change', function() {
                var $checkbox = $(this);
                var $row = $checkbox.closest('tr');
                var role = $checkbox.data('role');
                var permission = $checkbox.data('permission');
                
                // Update the display text
                var $cell = $checkbox.closest('td');
                var newText = $checkbox.is(':checked') ? '✅ Yes' : '❌ No';
                $cell.find('label').html($checkbox.prop('outerHTML') + ' ' + newText);
            });
            
            // Save role permissions
            $('.save-role-permissions').on('click', function() {
                var role = $(this).data('role');
                self.saveRolePermissions(role);
            });
            
            // Save all role permissions
            $('.save-all-role-permissions').on('click', function() {
                self.saveAllRolePermissions();
            });
            
            // User search
            $('#user-search').on('input', function() {
                var search = $(this).val();
                if (search.length >= 2) {
                    self.searchUsers(search);
                } else {
                    $('#user-search-results').hide();
                }
            });
            
            // User override permission checkboxes
            $(document).on('change', '.user-override-permission', function() {
                var $checkbox = $(this);
                var $cell = $checkbox.closest('td');
                var newText = $checkbox.is(':checked') ? '✅ Yes' : '❌ No';
                $cell.find('label').html($checkbox.prop('outerHTML') + ' ' + newText);
            });
            
            // Save user override
            $(document).on('click', '.save-user-override', function() {
                var userId = $(this).data('user-id');
                self.saveUserOverride(userId);
            });
            
            // Remove user override
            $(document).on('click', '.remove-user-override', function() {
                var userId = $(this).data('user-id');
                self.removeUserOverride(userId);
            });
            
            // Export permissions
            $('#export-permissions').on('click', function() {
                self.exportPermissions();
            });
            
            // Import permissions
            $('#import-permissions').on('click', function() {
                self.importPermissions();
            });
            
            // Reset permissions
            $('#reset-permissions').on('click', function() {
                self.resetPermissions();
            });
            
            // Test current user
            $('#test-current-user').on('click', function() {
                self.testCurrentUser();
            });
            
            // Bulk actions
            $('#apply-bulk-action').on('click', function() {
                self.applyBulkAction();
            });
            
            // Select all users
            $('#select-all-users').on('click', function() {
                self.toggleSelectAll();
            });
            
            // Select all checkbox
            $('#select-all-checkbox').on('change', function() {
                $('.user-select').prop('checked', $(this).is(':checked'));
            });
        },

        /**
         * Initialize tabs
         */
        initTabs: function() {
            // Show first tab by default
            $('.nav-tab').first().addClass('nav-tab-active');
            $('.tab-pane').first().addClass('active');
        },

        /**
         * Switch between tabs
         */
        switchTab: function(tabName) {
            // Update nav tabs
            $('.nav-tab').removeClass('nav-tab-active');
            $('.nav-tab[data-tab="' + tabName + '"]').addClass('nav-tab-active');
            
            // Update tab content
            $('.tab-pane').removeClass('active');
            $('#tab-' + tabName).addClass('active');
        },

        /**
         * Save role permissions
         */
        saveRolePermissions: function(role) {
            var $row = $('tr[data-role="' + role + '"]');
            var permissions = {
                edit: $row.find('.role-permission[data-permission="edit"]').is(':checked'),
                elementor: $row.find('.role-permission[data-permission="elementor"]').is(':checked')
            };
            
            this.showLoading($row.find('.save-role-permissions'));
            
            $.ajax({
                url: rbecAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rbec_save_role_permissions',
                    nonce: rbecAdmin.nonce,
                    role: role,
                    edit: permissions.edit,
                    elementor: permissions.elementor
                },
                success: function(response) {
                    RBECSimpleAdmin.hideLoading($row.find('.save-role-permissions'));
                    if (response.success) {
                        RBECSimpleAdmin.showNotice('Role permissions saved!', 'success');
                    } else {
                        RBECSimpleAdmin.showNotice('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RBECSimpleAdmin.hideLoading($row.find('.save-role-permissions'));
                    RBECSimpleAdmin.showNotice('Error occurred while saving', 'error');
                }
            });
        },

        /**
         * Save all role permissions
         */
        saveAllRolePermissions: function() {
            var $button = $('.save-all-role-permissions');
            this.showLoading($button);
            
            var promises = [];
            $('.role-permission').each(function() {
                var $checkbox = $(this);
                var role = $checkbox.data('role');
                promises.push(RBECSimpleAdmin.saveRolePermissionsPromise(role));
            });
            
            Promise.all(promises).then(function() {
                RBECSimpleAdmin.hideLoading($button);
                RBECSimpleAdmin.showNotice('All role permissions saved!', 'success');
            }).catch(function(error) {
                RBECSimpleAdmin.hideLoading($button);
                RBECSimpleAdmin.showNotice('Error saving some permissions', 'error');
            });
        },

        /**
         * Save role permissions (Promise version)
         */
        saveRolePermissionsPromise: function(role) {
            return new Promise(function(resolve, reject) {
                var $row = $('tr[data-role="' + role + '"]');
                var permissions = {
                    edit: $row.find('.role-permission[data-permission="edit"]').is(':checked'),
                    elementor: $row.find('.role-permission[data-permission="elementor"]').is(':checked')
                };
                
                $.ajax({
                    url: rbecAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'rbec_save_role_permissions',
                        nonce: rbecAdmin.nonce,
                        role: role,
                        edit: permissions.edit,
                        elementor: permissions.elementor
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response);
                        }
                    },
                    error: function() {
                        reject();
                    }
                });
            });
        },

        /**
         * Search users
         */
        searchUsers: function(search) {
            var self = this;
            
            $.ajax({
                url: rbecAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rbec_search_users',
                    nonce: rbecAdmin.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        self.displayUserSearchResults(response.data);
                    }
                }
            });
        },

        /**
         * Display user search results
         */
        displayUserSearchResults: function(users) {
            var $container = $('#user-search-results');
            $container.empty();
            
            if (users.length === 0) {
                $container.html('<div class="user-search-result">No users found</div>');
            } else {
                users.forEach(function(user) {
                    var $result = $('<div class="user-search-result" data-user-id="' + user.id + '">' +
                        '<strong>' + user.name + '</strong> (' + user.email + ')<br>' +
                        '<small>Roles: ' + user.roles.join(', ') + '</small>' +
                        '</div>');
                    
                    $result.on('click', function() {
                        RBECSimpleAdmin.addUserOverride(user);
                    });
                    
                    $container.append($result);
                });
            }
            
            $container.show();
        },

        /**
         * Add user override
         */
        addUserOverride: function(user) {
            // Hide search results
            $('#user-search-results').hide();
            $('#user-search').val('');
            
            // Create new row in user overrides table
            var $table = $('.tab-pane#tab-user-overrides table tbody');
            if ($table.length === 0) {
                // Create table if it doesn't exist
                $table = $('<table class="widefat fixed striped"><thead><tr><th>User</th><th>Role</th><th>Edit Button</th><th>Elementor Button</th><th>Actions</th></tr></thead><tbody></tbody></table>');
                $('.tab-pane#tab-user-overrides .inside').append($table);
            }
            
            var $row = $('<tr data-user-id="' + user.id + '">' +
                '<td><strong>' + user.name + '</strong><br><small>' + user.email + '</small></td>' +
                '<td>' + user.roles.join(', ') + '</td>' +
                '<td><label><input type="checkbox" class="user-override-permission" data-user-id="' + user.id + '" data-permission="edit"> ❌ No</label></td>' +
                '<td><label><input type="checkbox" class="user-override-permission" data-user-id="' + user.id + '" data-permission="elementor"> ❌ No</label></td>' +
                '<td><button type="button" class="button save-user-override" data-user-id="' + user.id + '">Save</button> ' +
                '<button type="button" class="button button-link-delete remove-user-override" data-user-id="' + user.id + '">Remove Override</button></td>' +
                '</tr>');
            
            $table.append($row);
            
            this.showNotice('User override added. Configure permissions and save.', 'info');
        },

        /**
         * Save user override
         */
        saveUserOverride: function(userId) {
            var $row = $('tr[data-user-id="' + userId + '"]');
            var permissions = {
                edit: $row.find('.user-override-permission[data-permission="edit"]').is(':checked'),
                elementor: $row.find('.user-override-permission[data-permission="elementor"]').is(':checked')
            };
            
            this.showLoading($row.find('.save-user-override'));
            
            $.ajax({
                url: rbecAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rbec_save_user_override',
                    nonce: rbecAdmin.nonce,
                    user_id: userId,
                    edit: permissions.edit,
                    elementor: permissions.elementor
                },
                success: function(response) {
                    RBECSimpleAdmin.hideLoading($row.find('.save-user-override'));
                    if (response.success) {
                        RBECSimpleAdmin.showNotice('User override saved!', 'success');
                    } else {
                        RBECSimpleAdmin.showNotice('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RBECSimpleAdmin.hideLoading($row.find('.save-user-override'));
                    RBECSimpleAdmin.showNotice('Error occurred while saving', 'error');
                }
            });
        },

        /**
         * Remove user override
         */
        removeUserOverride: function(userId) {
            if (!confirm(rbecAdmin.strings.confirmRemove)) {
                return;
            }
            
            var $row = $('tr[data-user-id="' + userId + '"]');
            this.showLoading($row.find('.remove-user-override'));
            
            $.ajax({
                url: rbecAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rbec_remove_user_override',
                    nonce: rbecAdmin.nonce,
                    user_id: userId
                },
                success: function(response) {
                    RBECSimpleAdmin.hideLoading($row.find('.remove-user-override'));
                    if (response.success) {
                        $row.fadeOut(function() {
                            $row.remove();
                        });
                        RBECSimpleAdmin.showNotice('User override removed!', 'success');
                    } else {
                        RBECSimpleAdmin.showNotice('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RBECSimpleAdmin.hideLoading($row.find('.remove-user-override'));
                    RBECSimpleAdmin.showNotice('Error occurred while removing', 'error');
                }
            });
        },

        /**
         * Export permissions
         */
        exportPermissions: function() {
            window.location.href = rbecAdmin.ajaxUrl + '?action=rbec_export_permissions&nonce=' + rbecAdmin.nonce;
        },

        /**
         * Import permissions
         */
        importPermissions: function() {
            var fileInput = document.getElementById('import-file');
            var file = fileInput.files[0];
            
            if (!file) {
                this.showNotice('Please select a file to import', 'error');
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'rbec_import_permissions');
            formData.append('nonce', rbecAdmin.nonce);
            formData.append('file', file);
            
            this.showLoading($('#import-permissions'));
            
            $.ajax({
                url: rbecAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    RBECSimpleAdmin.hideLoading($('#import-permissions'));
                    if (response.success) {
                        RBECSimpleAdmin.showNotice('Permissions imported successfully!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        RBECSimpleAdmin.showNotice('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RBECSimpleAdmin.hideLoading($('#import-permissions'));
                    RBECSimpleAdmin.showNotice('Error occurred while importing', 'error');
                }
            });
        },

        /**
         * Reset permissions
         */
        resetPermissions: function() {
            if (!confirm(rbecAdmin.strings.confirmReset)) {
                return;
            }
            
            var $button = $('#reset-permissions');
            this.showLoading($button);
            this.showNotice('Resetting permissions...', 'info');
            
            // Make AJAX call to reset
            $.ajax({
                url: rbecAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rbec_reset_permissions',
                    nonce: rbecAdmin.nonce
                },
                success: function(response) {
                    RBECSimpleAdmin.hideLoading($button);
                    if (response.success) {
                        RBECSimpleAdmin.showNotice('Permissions reset to defaults!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        RBECSimpleAdmin.showNotice('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RBECSimpleAdmin.hideLoading($button);
                    RBECSimpleAdmin.showNotice('Error occurred while resetting', 'error');
                }
            });
        },

        /**
         * Test current user
         */
        testCurrentUser: function() {
            var $button = $('#test-current-user');
            var $results = $('#test-results');
            
            this.showLoading($button);
            $results.html('<p>Testing current user permissions...</p>');
            
            $.ajax({
                url: rbecAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rbec_test_current_user',
                    nonce: rbecAdmin.nonce
                },
                success: function(response) {
                    RBECSimpleAdmin.hideLoading($button);
                    if (response.success) {
                        var data = response.data;
                        var html = '<h4>Current User: ' + data.display_name + '</h4>';
                        html += '<p><strong>Roles:</strong> ' + data.roles.join(', ') + '</p>';
                        html += '<p><strong>Can see Edit buttons:</strong> ' + (data.permissions.edit ? '✅ Yes' : '❌ No') + '</p>';
                        html += '<p><strong>Can see Elementor buttons:</strong> ' + (data.permissions.elementor ? '✅ Yes' : '❌ No') + '</p>';
                        
                        $results.html(html);
                        RBECSimpleAdmin.showNotice('User test completed!', 'success');
                    } else {
                        $results.html('<p class="error">Error: ' + response.data + '</p>');
                        RBECSimpleAdmin.showNotice('Error testing user: ' + response.data, 'error');
                    }
                },
                error: function() {
                    RBECSimpleAdmin.hideLoading($button);
                    $results.html('<p class="error">Error occurred while testing user</p>');
                    RBECSimpleAdmin.showNotice('Error occurred while testing', 'error');
                }
            });
        },

        /**
         * Show loading state
         */
        showLoading: function($element) {
            $element.data('original-text', $element.text());
            $element.text(rbecAdmin.strings.saving);
            $element.prop('disabled', true);
        },

        /**
         * Hide loading state
         */
        hideLoading: function($element) {
            $element.text($element.data('original-text') || 'Save');
            $element.prop('disabled', false);
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 3000);
        },

        /**
         * Toggle keyboard shortcuts display
         */
        toggleKeyboardShortcuts: function() {
            var $shortcuts = $('.keyboard-shortcuts');
            
            if ($shortcuts.length === 0) {
                var shortcutsHtml = '<div class="keyboard-shortcuts">' +
                    '<h4>Keyboard Shortcuts</h4>' +
                    '<p><kbd>Ctrl+S</kbd> - Save all role permissions</p>' +
                    '<p><kbd>Ctrl+F</kbd> - Focus user search</p>' +
                    '<p><kbd>F1</kbd> - Show/hide shortcuts</p>' +
                    '</div>';
                $('body').append(shortcutsHtml);
                $shortcuts = $('.keyboard-shortcuts');
            }
            
            $shortcuts.toggle();
            
            // Auto-hide after 5 seconds
            if ($shortcuts.is(':visible')) {
                setTimeout(function() {
                    $shortcuts.fadeOut();
                }, 5000);
            }
        },

        /**
         * Apply bulk action to selected users
         */
        applyBulkAction: function() {
            var action = $('#bulk-action').val();
            var selectedUsers = [];
            
            $('.user-select:checked').each(function() {
                selectedUsers.push($(this).data('user-id'));
            });
            
            if (selectedUsers.length === 0) {
                this.showNotice('Please select at least one user', 'error');
                return;
            }
            
            if (!action) {
                this.showNotice('Please select a bulk action', 'error');
                return;
            }
            
            var confirmMessage = 'Are you sure you want to ' + action.replace('-', ' ') + ' for ' + selectedUsers.length + ' selected users?';
            if (!confirm(confirmMessage)) {
                return;
            }
            
            this.showNotice('Applying bulk action...', 'info');
            
            var promises = [];
            selectedUsers.forEach(function(userId) {
                promises.push(this.applyBulkActionToUser(userId, action));
            }.bind(this));
            
            Promise.all(promises).then(function() {
                this.showNotice('Bulk action completed successfully!', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }.bind(this)).catch(function(error) {
                this.showNotice('Some bulk actions failed', 'error');
            }.bind(this));
        },

        /**
         * Apply bulk action to a single user
         */
        applyBulkActionToUser: function(userId, action) {
            return new Promise(function(resolve, reject) {
                var permissions = {};
                var $row = $('tr[data-user-id="' + userId + '"]');
                
                switch (action) {
                    case 'enable-edit':
                        permissions.edit = true;
                        break;
                    case 'disable-edit':
                        permissions.edit = false;
                        break;
                    case 'enable-elementor':
                        permissions.elementor = true;
                        break;
                    case 'disable-elementor':
                        permissions.elementor = false;
                        break;
                    case 'remove-overrides':
                        // Remove user override
                        $.ajax({
                            url: rbecAdmin.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'rbec_remove_user_override',
                                nonce: rbecAdmin.nonce,
                                user_id: userId
                            },
                            success: function(response) {
                                if (response.success) {
                                    resolve(response);
                                } else {
                                    reject(response);
                                }
                            },
                            error: function() {
                                reject();
                            }
                        });
                        return;
                }
                
                // Apply permission changes
                $.ajax({
                    url: rbecAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'rbec_save_user_override',
                        nonce: rbecAdmin.nonce,
                        user_id: userId,
                        edit: permissions.edit,
                        elementor: permissions.elementor
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response);
                        }
                    },
                    error: function() {
                        reject();
                    }
                });
            });
        },

        /**
         * Toggle select all users
         */
        toggleSelectAll: function() {
            var $selectAllCheckbox = $('#select-all-checkbox');
            var $userCheckboxes = $('.user-select');
            
            if ($selectAllCheckbox.is(':checked')) {
                $userCheckboxes.prop('checked', false);
                $selectAllCheckbox.prop('checked', false);
            } else {
                $userCheckboxes.prop('checked', true);
                $selectAllCheckbox.prop('checked', true);
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        RBECSimpleAdmin.init();
    });

})(jQuery);
