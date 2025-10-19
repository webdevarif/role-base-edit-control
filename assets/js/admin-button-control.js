/**
 * RBEC Role-Based Button Control JavaScript
 * 
 * This script provides client-side button hiding for elements that might
 * not be caught by PHP hooks. It works as a fallback and enhancement
 * to the server-side filtering.
 * 
 * Features:
 * - Role-based button visibility
 * - Admin dashboard compatibility
 * - Elementor integration
 * - Non-destructive DOM manipulation
 */

(function($) {
    'use strict';

    /**
     * RBEC Role Button Controller
     */
    var RBECRoleButtonController = {
        
        /**
         * Configuration
         */
        config: {
            userRole: '',
            debug: false,
            selectors: {
                editButtons: [
                    '.edit-link',
                    '.edit a',
                    '.post-edit-link',
                    '.page-edit-link',
                    'a[href*="post.php?post="]',
                    'a[href*="post-new.php"]'
                ],
                elementorButtons: [
                    '.elementor-edit-link',
                    '.elementor-edit-page',
                    '.elementor-preview-link',
                    'a[href*="elementor"]',
                    'a[href*="action=elementor"]'
                ],
                rbecPanels: [
                    '.uip-admin-page',
                    '.uip-dashboard',
                    '.uip-content'
                ]
            }
        },

        /**
         * Initialize the controller
         */
        init: function() {
            // Get configuration from localized script
            if (typeof rbecRoleButtons !== 'undefined') {
                this.config.userRole = rbecRoleButtons.userRole || '';
                this.config.debug = rbecRoleButtons.debug || false;
            }

            // Only proceed if we have a valid role
            if (!this.config.userRole) {
                this.log('No user role detected, skipping button control');
                return;
            }

            this.log('Initializing RBEC Role Button Controller for role: ' + this.config.userRole);
            
            // Run button hiding
            this.hideButtons();
            
            // Set up observers for dynamic content
            this.setupObservers();
            
            // Handle Admin dashboard panels
            this.handleAdminPanels();
        },

        /**
         * Hide buttons based on user role
         */
        hideButtons: function() {
            var self = this;
            
            // Hide edit buttons if user can't see them
            if (!this.userCanSeeEditButton()) {
                this.hideButtonGroup('editButtons');
                this.log('Hidden edit buttons for role: ' + this.config.userRole);
            }

            // Hide Elementor buttons if user can't see them
            if (!this.userCanSeeElementorButton()) {
                this.hideButtonGroup('elementorButtons');
                this.log('Hidden Elementor buttons for role: ' + this.config.userRole);
            }
        },

        /**
         * Hide a group of buttons
         * 
         * @param {string} groupName Name of the button group
         */
        hideButtonGroup: function(groupName) {
            var selectors = this.config.selectors[groupName];
            var self = this;

            selectors.forEach(function(selector) {
                $(selector).each(function() {
                    var $button = $(this);
                    
                    // Add transition class for smooth hiding
                    $button.addClass('rbec-role-button-transition');
                    
                    // Hide the button
                    $button.addClass('rbec-role-button-hidden');
                    
                    // Remove from DOM after transition
                    setTimeout(function() {
                        $button.remove();
                    }, 300);
                });
            });
        },

        /**
         * Handle Admin dashboard panels
         */
        handleAdminPanels: function() {
            var self = this;
            
            // Wait for Admin panels to load
            setTimeout(function() {
                self.config.selectors.rbecPanels.forEach(function(panelSelector) {
                    $(panelSelector).each(function() {
                        var $panel = $(this);
                        self.processAdminPanel($panel);
                    });
                });
            }, 1000);
        },

        /**
         * Process a Admin panel for button hiding
         * 
         * @param {jQuery} $panel Panel element
         */
        processAdminPanel: function($panel) {
            // Hide edit buttons in Admin panels
            if (!this.userCanSeeEditButton()) {
                $panel.find('a[href*="post.php"], a[href*="post-new.php"]').addClass('rbec-role-button-hidden');
            }

            // Hide Elementor buttons in Admin panels
            if (!this.userCanSeeElementorButton()) {
                $panel.find('a[href*="elementor"]').addClass('rbec-role-button-hidden');
            }
        },

        /**
         * Set up observers for dynamic content
         */
        setupObservers: function() {
            var self = this;

            // Mutation observer for dynamically added content
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    var $node = $(node);
                                    
                                    // Process the new node for buttons
                                    self.processNewContent($node);
                                }
                            });
                        }
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            // Handle AJAX-loaded content
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
                    setTimeout(function() {
                        self.hideButtons();
                        self.handleAdminPanels();
                    }, 100);
                }
            });
        },

        /**
         * Process newly added content for button hiding
         * 
         * @param {jQuery} $content Content element
         */
        processNewContent: function($content) {
            // Hide edit buttons in new content
            if (!this.userCanSeeEditButton()) {
                this.config.selectors.editButtons.forEach(function(selector) {
                    $content.find(selector).addClass('rbec-role-button-hidden');
                });
            }

            // Hide Elementor buttons in new content
            if (!this.userCanSeeElementorButton()) {
                this.config.selectors.elementorButtons.forEach(function(selector) {
                    $content.find(selector).addClass('rbec-role-button-hidden');
                });
            }
        },

        /**
         * Check if user can see edit button based on role
         * 
         * @return {boolean} True if user can see edit button
         */
        userCanSeeEditButton: function() {
            if (!this.config.userRole || !this.config.roleConfig) {
                return false;
            }
            
            var roleConfig = this.config.roleConfig[this.config.userRole];
            return roleConfig && roleConfig.show_edit === true;
        },

        /**
         * Check if user can see Elementor button based on role
         * 
         * @return {boolean} True if user can see Elementor button
         */
        userCanSeeElementorButton: function() {
            if (!this.config.userRole || !this.config.roleConfig) {
                return false;
            }
            
            var roleConfig = this.config.roleConfig[this.config.userRole];
            return roleConfig && roleConfig.show_elementor === true;
        },

        /**
         * Log debug messages
         * 
         * @param {string} message Log message
         */
        log: function(message) {
            if (this.config.debug) {
                console.log('[Admin Role Buttons] ' + message);
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        RBECRoleButtonController.init();
    });

    /**
     * Re-initialize on window load (for late-loading content)
     */
    $(window).on('load', function() {
        setTimeout(function() {
            RBECRoleButtonController.hideButtons();
            RBECRoleButtonController.handleAdminPanels();
        }, 500);
    });

    /**
     * Handle Admin dashboard events
     */
    $(document).on('uipress:panel:loaded', function() {
        RBECRoleButtonController.handleAdminPanels();
    });

    /**
     * Expose controller globally for debugging
     */
    window.RBECRoleButtonController = RBECRoleButtonController;

})(jQuery);
