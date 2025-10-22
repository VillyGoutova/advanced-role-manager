<?php
/**
 * Plugin Name: Advanced Role Manager
 * Plugin URI: https://codeangels.solutions
 * Description: Manage WordPress user roles - list, view capabilities, edit, and delete roles
 * Version: 2.1.0
 * Author: Villy Goutova
 * Author URI: https://codeangels.solutions
 * License: GPL v2 or later
 * Text Domain: advanced-role-manager
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Advanced_Role_Manager {
    
    private $protected_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
    
    // Plugin-specific roles that should be handled carefully
    private $plugin_roles = [
        'shop_manager' => 'WooCommerce',
        'customer' => 'WooCommerce',
    ];
    
    /**
     * Complete WordPress core capabilities (WP 6.0+)
     * These are built-in WordPress capabilities that should NOT be flagged as plugin/custom capabilities
     */
    private $core_capabilities = [
        // Basic
        'read',
        
        // Posts
        'edit_posts',
        'delete_posts',
        'publish_posts',
        'edit_published_posts',
        'delete_published_posts',
        'edit_others_posts',
        'delete_others_posts',
        'read_private_posts',
        'edit_private_posts',
        'delete_private_posts',
        
        // Pages
        'edit_pages',
        'delete_pages',
        'publish_pages',
        'edit_published_pages',
        'delete_published_pages',
        'edit_others_pages',
        'delete_others_pages',
        'read_private_pages',
        'edit_private_pages',
        'delete_private_pages',
        
        // Media
        'upload_files',
        'unfiltered_upload',
        
        // Categories & Tags
        'manage_categories',
        'edit_categories',
        'delete_categories',
        'assign_categories',
        'manage_post_tags',
        'edit_post_tags',
        'delete_post_tags',
        'assign_post_tags',
        
        // Links (legacy but still core)
        'manage_links',
        'edit_links',
        'delete_links',
        
        // Comments
        'moderate_comments',
        'edit_comment',
        
        // Themes
        'switch_themes',
        'edit_themes',
        'edit_theme_options',
        'delete_themes',           // This was missing!
        'install_themes',
        'update_themes',
        'resume_themes',
        
        // Plugins
        'activate_plugins',
        'edit_plugins',
        'install_plugins',
        'update_plugins',
        'delete_plugins',
        'resume_plugins',
        
        // Users
        'list_users',
        'create_users',
        'edit_users',
        'delete_users',
        'promote_users',
        'remove_users',
        'add_users',
        
        // General Administration
        'manage_options',
        'edit_dashboard',
        'customize',
        'unfiltered_html',
        
        // Core Updates
        'update_core',
        'update_php',
        
        // Import/Export
        'import',
        'export',
        
        // Site Management
        'delete_site',
        
        // Privacy (GDPR)
        'manage_privacy_options',
        'export_others_personal_data',
        'erase_others_personal_data',
        
        // Site Health
        'view_site_health_checks',
        
        // Multisite Specific
        'manage_network',
        'manage_sites',
        'manage_network_users',
        'manage_network_themes',
        'manage_network_plugins',
        'manage_network_options',
        'create_sites',
        'delete_sites',
        'upload_plugins',
        'upload_themes',
    ];
    
    private $plugin_version = '2.1.0';
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_arm_delete_roles', [$this, 'handle_delete_roles']);
        add_action('admin_post_arm_update_capabilities', [$this, 'handle_update_capabilities']);
        add_action('admin_post_arm_copy_capabilities', [$this, 'handle_copy_capabilities']);
        add_action('admin_post_arm_cleanup_capabilities', [$this, 'handle_cleanup_capabilities']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // AJAX handlers for better UX
        add_action('wp_ajax_arm_quick_remove_cap', [$this, 'ajax_quick_remove_capability']);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('advanced-role-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Role Manager', 'advanced-role-manager'),
            __('Role Manager', 'advanced-role-manager'),
            'manage_options',
            'advanced-role-manager',
            [$this, 'render_admin_page'],
            'dashicons-groups',
            71
        );
        
        add_submenu_page(
            'advanced-role-manager',
            __('Edit Role', 'advanced-role-manager'),
            null, // Hide from menu
            'manage_options',
            'arm-edit-role',
            [$this, 'render_edit_page']
        );
        
        add_submenu_page(
            'advanced-role-manager',
            __('Cleanup Capabilities', 'advanced-role-manager'),
            __('Cleanup', 'advanced-role-manager'),
            'manage_options',
            'arm-cleanup',
            [$this, 'render_cleanup_page']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'advanced-role-manager') === false && 
            strpos($hook, 'arm-edit-role') === false && 
            strpos($hook, 'arm-cleanup') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'arm-admin-style',
            plugins_url('assets/css/admin-style.css', __FILE__),
            [],
            $this->plugin_version
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'arm-admin-script',
            plugins_url('assets/js/admin-script.js', __FILE__),
            ['jquery'],
            $this->plugin_version,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('arm-admin-script', 'armData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('arm_ajax_nonce'),
            'strings' => [
                'confirmDelete' => __('Are you sure you want to delete the selected role(s)?', 'advanced-role-manager'),
                'confirmRemove' => __('Are you sure you want to remove these capabilities?', 'advanced-role-manager'),
                'warning' => __('WARNING', 'advanced-role-manager'),
                'usersAffected' => __('role(s) have active users who will be reassigned to Subscriber!', 'advanced-role-manager'),
                'pluginRoles' => __('PLUGIN ROLES DETECTED', 'advanced-role-manager'),
                'pluginWarning' => __('These roles may be recreated by their plugins on reactivation.', 'advanced-role-manager'),
            ]
        ]);
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'advanced-role-manager'));
        }
        
        $wp_roles = wp_roles();
        $all_roles = $wp_roles->roles;
        $role_names = $wp_roles->role_names;
        
        // Get statistics
        $total_roles = count($all_roles);
        $protected_count = count(array_intersect(array_keys($all_roles), $this->protected_roles));
        $custom_count = $total_roles - $protected_count;
        
        // Count users per role
        $user_counts = $this->get_user_counts();
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=arm-cleanup')); ?>" class="page-title-action">
                    <?php esc_html_e('🧹 Cleanup Capabilities', 'advanced-role-manager'); ?>
                </a>
            </h1>
            
            <div class="arm-container">
                <div class="arm-header">
                    <h2 style="margin-top:0;"><?php esc_html_e('User Roles Overview', 'advanced-role-manager'); ?></h2>
                    <div class="arm-stats">
                        <div class="arm-stat-box">
                            <div class="arm-stat-number"><?php echo esc_html($total_roles); ?></div>
                            <div class="arm-stat-label"><?php esc_html_e('Total Roles', 'advanced-role-manager'); ?></div>
                        </div>
                        <div class="arm-stat-box">
                            <div class="arm-stat-number"><?php echo esc_html($protected_count); ?></div>
                            <div class="arm-stat-label"><?php esc_html_e('Protected Roles', 'advanced-role-manager'); ?></div>
                        </div>
                        <div class="arm-stat-box">
                            <div class="arm-stat-number"><?php echo esc_html($custom_count); ?></div>
                            <div class="arm-stat-label"><?php esc_html_e('Custom Roles', 'advanced-role-manager'); ?></div>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="roles-form">
                    <?php wp_nonce_field('arm_delete_roles_action', 'arm_delete_roles_nonce'); ?>
                    <input type="hidden" name="action" value="arm_delete_roles">
                    
                    <div class="arm-table-container">
                        <table class="arm-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="select-all" class="arm-checkbox">
                                    </th>
                                    <th><?php esc_html_e('Role Name', 'advanced-role-manager'); ?></th>
                                    <th><?php esc_html_e('Users', 'advanced-role-manager'); ?></th>
                                    <th><?php esc_html_e('Capabilities', 'advanced-role-manager'); ?></th>
                                    <th><?php esc_html_e('Actions', 'advanced-role-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_roles as $role_slug => $role_data): 
                                    $is_protected = in_array($role_slug, $this->protected_roles);
                                    $is_plugin_role = isset($this->plugin_roles[$role_slug]);
                                    $user_count = isset($user_counts[$role_slug]) ? $user_counts[$role_slug] : 0;
                                    $caps = array_keys($role_data['capabilities']);
                                    $caps_preview = implode(', ', array_slice($caps, 0, 5));
                                    if (count($caps) > 5) {
                                        $caps_preview .= '...';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!$is_protected): ?>
                                            <input type="checkbox" 
                                                   name="roles[]" 
                                                   value="<?php echo esc_attr($role_slug); ?>" 
                                                   class="arm-checkbox role-checkbox"
                                                   data-users="<?php echo esc_attr($user_count); ?>"
                                                   data-plugin="<?php echo $is_plugin_role ? esc_attr($this->plugin_roles[$role_slug]) : ''; ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="arm-role-name"><?php echo esc_html($role_data['name']); ?></span>
                                        <span class="arm-role-slug"><?php echo esc_html($role_slug); ?></span>
                                        <?php if ($is_protected): ?>
                                            <span class="arm-protected-badge"><?php esc_html_e('PROTECTED', 'advanced-role-manager'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($is_plugin_role): ?>
                                            <span class="arm-plugin-badge"><?php echo esc_html($this->plugin_roles[$role_slug]); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="arm-user-count"><?php echo esc_html($user_count); ?></span>
                                    </td>
                                    <td>
                                        <div class="arm-caps-preview" title="<?php echo esc_attr(implode(', ', $caps)); ?>">
                                            <?php echo esc_html($caps_preview); ?>
                                        </div>
                                        <small style="color: #646970;">
                                            <?php 
                                            printf(
                                                esc_html(_n('%d capability', '%d capabilities', count($caps), 'advanced-role-manager')),
                                                count($caps)
                                            ); 
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=arm-edit-role&role=' . urlencode($role_slug))); ?>" 
                                           class="arm-edit-btn">
                                            <?php esc_html_e('Edit', 'advanced-role-manager'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="arm-actions">
                            <div class="arm-bulk-actions">
                                <span style="color: #646970; font-size: 13px;" id="selected-count"></span>
                            </div>
                            <button type="submit" class="arm-delete-btn" id="delete-btn" disabled>
                                <?php esc_html_e('🗑️ Delete Selected Roles', 'advanced-role-manager'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render edit page
     */
    public function render_edit_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'advanced-role-manager'));
        }
        
        $role_slug = isset($_GET['role']) ? sanitize_key($_GET['role']) : '';
        
        if (empty($role_slug)) {
            wp_die(__('Invalid role.', 'advanced-role-manager'));
        }
        
        $role = get_role($role_slug);
        
        if (!$role) {
            wp_die(__('Role not found.', 'advanced-role-manager'));
        }
        
        $wp_roles = wp_roles();
        $role_data = $wp_roles->roles[$role_slug];
        $role_name = $role_data['name'];
        $current_caps = array_keys($role->capabilities);
        
        // Get all available capabilities from all roles
        $all_caps = $this->get_plugin_capabilities();
        
        // User count
        $user_counts = $this->get_user_counts();
        $user_count = isset($user_counts[$role_slug]) ? $user_counts[$role_slug] : 0;
        
        $is_protected = in_array($role_slug, $this->protected_roles);
        
        ?>
        <div class="wrap"> 
            <h1>
                <?php 
                printf(
                    esc_html__('Edit Role: %s', 'advanced-role-manager'),
                    esc_html($role_name)
                ); 
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=advanced-role-manager')); ?>" class="page-title-action">
                    <?php esc_html_e('← Back to Roles', 'advanced-role-manager'); ?>
                </a>
            </h1>
            
            <div class="arm-edit-container">
                <div class="arm-info-box">
                    <p>
                        <strong><?php esc_html_e('Role Slug:', 'advanced-role-manager'); ?></strong> 
                        <code><?php echo esc_html($role_slug); ?></code> &nbsp;|&nbsp; 
                        <strong><?php esc_html_e('Users:', 'advanced-role-manager'); ?></strong> 
                        <?php echo esc_html($user_count); ?> &nbsp;|&nbsp;
                        <strong><?php esc_html_e('Capabilities:', 'advanced-role-manager'); ?></strong> 
                        <?php echo esc_html(count($current_caps)); ?>
                        <?php if ($is_protected): ?>
                            &nbsp;|&nbsp; <span style="color: #00a32a; font-weight: 600;">
                                <?php esc_html_e('🛡️ PROTECTED ROLE', 'advanced-role-manager'); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="arm-two-column">
                    <!-- Current Capabilities -->
                    <div class="arm-panel">
                        <div class="arm-panel-header">
                            <h3>
                                <?php 
                                printf(
                                    esc_html__('✅ Current Capabilities (%d)', 'advanced-role-manager'),
                                    count($current_caps)
                                ); 
                                ?>
                            </h3>
                        </div>
                        <div class="arm-panel-body">
                            <div class="arm-search-box">
                                <input type="text" 
                                       id="search-current" 
                                       placeholder="<?php esc_attr_e('Search current capabilities...', 'advanced-role-manager'); ?>" />
                            </div>
                            
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="remove-caps-form">
                                <?php wp_nonce_field('arm_update_capabilities_action', 'arm_update_capabilities_nonce'); ?>
                                <input type="hidden" name="action" value="arm_update_capabilities">
                                <input type="hidden" name="role" value="<?php echo esc_attr($role_slug); ?>">
                                <input type="hidden" name="operation" value="remove">
                                
                                <div class="arm-capability-list" id="current-caps-list">
                                    <?php foreach ($current_caps as $cap): ?>
                                        <div class="arm-capability-item" data-cap="<?php echo esc_attr($cap); ?>">
                                            <label>
                                                <input type="checkbox" name="capabilities[]" value="<?php echo esc_attr($cap); ?>">
                                                <span class="arm-capability-name"><?php echo esc_html($cap); ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="arm-button-group">
                                    <button type="submit" class="arm-btn arm-delete-btn" id="remove-caps-btn" disabled>
                                        <?php esc_html_e('Remove Selected', 'advanced-role-manager'); ?>
                                    </button>
                                    <span id="remove-count" style="color: #646970; font-size: 13px;"></span>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Available Capabilities -->
                    <div class="arm-panel">
                        <div class="arm-panel-header">
                            <h3>
                                <?php 
                                printf(
                                    esc_html__('➕ Available Capabilities (%d)', 'advanced-role-manager'),
                                    count($all_caps) - count($current_caps)
                                ); 
                                ?>
                            </h3>
                        </div>
                        <div class="arm-panel-body">
                            <div class="arm-filter-tabs">
                                <button type="button" class="arm-filter-tab active" data-filter="all">
                                    <?php esc_html_e('All', 'advanced-role-manager'); ?>
                                </button>
                                <button type="button" class="arm-filter-tab" data-filter="common">
                                    <?php esc_html_e('Common', 'advanced-role-manager'); ?>
                                </button>
                                <button type="button" class="arm-filter-tab" data-filter="custom">
                                    <?php esc_html_e('Custom', 'advanced-role-manager'); ?>
                                </button>
                            </div>
                            
                            <div class="arm-search-box">
                                <input type="text" 
                                       id="search-available" 
                                       placeholder="<?php esc_attr_e('Search available capabilities...', 'advanced-role-manager'); ?>" />
                            </div>
                            
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="add-caps-form">
                                <?php wp_nonce_field('arm_update_capabilities_action', 'arm_update_capabilities_nonce'); ?>
                                <input type="hidden" name="action" value="arm_update_capabilities">
                                <input type="hidden" name="role" value="<?php echo esc_attr($role_slug); ?>">
                                <input type="hidden" name="operation" value="add">
                                
                                <div class="arm-capability-list" id="available-caps-list">
                                    <?php 
                                    $available_caps = array_diff($all_caps, $current_caps);
                                    foreach ($available_caps as $cap): 
                                        $is_common = in_array($cap, $this->core_capabilities);
                                        $type = $is_common ? 'common' : 'custom';
                                    ?>
                                        <div class="arm-capability-item" data-cap="<?php echo esc_attr($cap); ?>" data-type="<?php echo esc_attr($type); ?>">
                                            <label>
                                                <input type="checkbox" name="capabilities[]" value="<?php echo esc_attr($cap); ?>">
                                                <span class="arm-capability-name"><?php echo esc_html($cap); ?></span>
                                                <?php if ($is_common): ?>
                                                    <span class="arm-capability-status active" style="background: #d7f0db; color: #00a32a;">
                                                        <?php esc_html_e('WP Core', 'advanced-role-manager'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="arm-button-group">
                                    <button type="submit" class="arm-btn arm-btn-primary" id="add-caps-btn" disabled>
                                        <?php esc_html_e('Add Selected', 'advanced-role-manager'); ?>
                                    </button>
                                    <span id="add-count" style="color: #646970; font-size: 13px;"></span>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="arm-panel" style="margin-top: 20px;">
                    <div class="arm-panel-header">
                        <h3><?php esc_html_e('⚡ Quick Actions', 'advanced-role-manager'); ?></h3>
                    </div>
                    <div class="arm-panel-body">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                            <?php wp_nonce_field('arm_copy_capabilities_action', 'arm_copy_capabilities_nonce'); ?>
                            <input type="hidden" name="action" value="arm_copy_capabilities">
                            <input type="hidden" name="target_role" value="<?php echo esc_attr($role_slug); ?>">
                            
                            <div class="arm-form-group" style="display: inline-block; width: 300px; margin-right: 10px;">
                                <label><?php esc_html_e('Copy capabilities from:', 'advanced-role-manager'); ?></label>
                                <select name="source_role" required>
                                    <option value=""><?php esc_html_e('-- Select Role --', 'advanced-role-manager'); ?></option>
                                    <?php foreach ($wp_roles->role_names as $slug => $name): ?>
                                        <?php if ($slug !== $role_slug): ?>
                                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" 
                                    class="arm-btn arm-btn-secondary" 
                                    onclick="return confirm('<?php echo esc_js(__('This will replace all current capabilities with those from the selected role. Continue?', 'advanced-role-manager')); ?>');">
                                <?php esc_html_e('Copy Capabilities', 'advanced-role-manager'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get all plugin/custom capabilities (non-core)
     */
    private function get_plugin_capabilities() {
        // Try to get from cache first
        $cached = get_transient('arm_plugin_capabilities');
        if ($cached !== false) {
            return $cached;
        }

        $wp_roles = wp_roles();
        
        // Get all capabilities across all roles
        $all_caps = [];
        foreach ($wp_roles->roles as $role_data) {
            $all_caps = array_merge($all_caps, array_keys($role_data['capabilities']));
        }
        $all_caps = array_unique($all_caps);
        
        // Filter out core WordPress capabilities
        $plugin_caps = array_diff($all_caps, $this->core_capabilities);
        
        // Cache for 1 hour
        set_transient('arm_plugin_capabilities', $plugin_caps, HOUR_IN_SECONDS);
        
        return $plugin_caps;
    }

    /**
     * Render cleanup page
     */
    public function render_cleanup_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'advanced-role-manager'));
        }
        
        $wp_roles = wp_roles();
        
        // Get all capabilities across all roles
        $all_caps = [];
        $cap_usage = []; // Track which roles use which capabilities
        
        foreach ($wp_roles->roles as $role_slug => $role_data) {
            foreach (array_keys($role_data['capabilities']) as $cap) {
                $all_caps[] = $cap;
                
                if (!isset($cap_usage[$cap])) {
                    $cap_usage[$cap] = [];
                }
                $cap_usage[$cap][] = $role_slug;
            }
        }
        
        $all_caps = array_unique($all_caps);
        
        // Get plugin/custom capabilities (not core WordPress capabilities)
        $plugin_caps = array_diff($all_caps, $this->core_capabilities);
        
        // Group by plugin prefix
        $grouped_caps = $this->group_capabilities_by_plugin($plugin_caps);
        
        // Calculate statistics
        $total_plugin_caps = count($plugin_caps);
        $total_groups = count($grouped_caps);
        
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e('Plugin & Custom Capabilities', 'advanced-role-manager'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=advanced-role-manager')); ?>" class="page-title-action">
                    <?php esc_html_e('← Back to Roles', 'advanced-role-manager'); ?>
                </a>
            </h1>
            
            <div class="arm-container">
                <!-- Info Box -->
                <div class="arm-info-box">
                    <h3><?php esc_html_e('What are Plugin & Custom Capabilities?', 'advanced-role-manager'); ?></h3>
                    <p>
                        <?php esc_html_e('These are capabilities added by plugins, themes, or custom code that extend beyond standard WordPress functionality. They control access to features provided by extensions to your site.', 'advanced-role-manager'); ?>
                    </p>
                    <p>
                        <strong><?php esc_html_e('⚠️ Important:', 'advanced-role-manager'); ?></strong>
                        <?php esc_html_e('Only remove capabilities from plugins that have been permanently deleted from your site. If a plugin is just deactivated temporarily, keep its capabilities to avoid issues if you reactivate it.', 'advanced-role-manager'); ?>
                    </p>
                </div>
                
                <?php if (empty($plugin_caps)): ?>
                    <!-- No plugin capabilities found -->
                    <div class="arm-panel">
                        <div class="arm-panel-body">
                            <p style="text-align: center; padding: 40px; color: #646970;">
                                <span style="font-size: 48px;">✨</span><br>
                                <strong><?php esc_html_e('No plugin or custom capabilities found!', 'advanced-role-manager'); ?></strong><br>
                                <?php esc_html_e('Your site only uses WordPress core capabilities.', 'advanced-role-manager'); ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Statistics -->
                    <div class="arm-header">
                        <h2 style="margin-top:0;"><?php esc_html_e('Found Plugin & Custom Capabilities', 'advanced-role-manager'); ?></h2>
                        <div class="arm-stats">
                            <div class="arm-stat-box">
                                <div class="arm-stat-number"><?php echo esc_html($total_plugin_caps); ?></div>
                                <div class="arm-stat-label"><?php esc_html_e('Plugin/Custom Capabilities', 'advanced-role-manager'); ?></div>
                            </div>
                            <div class="arm-stat-box">
                                <div class="arm-stat-number"><?php echo esc_html($total_groups); ?></div>
                                <div class="arm-stat-label"><?php esc_html_e('Plugin Groups', 'advanced-role-manager'); ?></div>
                            </div>
                            <div class="arm-stat-box">
                                <div class="arm-stat-number"><?php echo esc_html(count($wp_roles->roles)); ?></div>
                                <div class="arm-stat-label"><?php esc_html_e('Total Roles', 'advanced-role-manager'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- THIS IS THE MISSING PART: The actual cleanup form with capability list -->
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="cleanup-form">
                        <?php wp_nonce_field('arm_cleanup_capabilities_action', 'arm_cleanup_capabilities_nonce'); ?>
                        <input type="hidden" name="action" value="arm_cleanup_capabilities">
                        
                        <div class="arm-table-container">
                            <div style="padding: 20px;">
                                <h3 style="margin-top: 0;"><?php esc_html_e('Select Capabilities to Remove', 'advanced-role-manager'); ?></h3>
                                
                                <?php foreach ($grouped_caps as $group => $caps): ?>
                                    <div class="arm-cap-group" style="margin-bottom: 30px; border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden;">
                                        <!-- Group Header with Select All -->
                                        <div class="arm-cap-group-header" style="background: #f6f7f7; padding: 15px; border-bottom: 1px solid #c3c4c7; display: flex; align-items: center; gap: 10px;">
                                            <input type="checkbox" 
                                                   class="group-checkbox arm-checkbox" 
                                                   data-group="<?php echo esc_attr($group); ?>" 
                                                   id="group-<?php echo esc_attr(sanitize_title($group)); ?>">
                                            <label for="group-<?php echo esc_attr(sanitize_title($group)); ?>" 
                                                   style="margin: 0; font-weight: 600; flex: 1; cursor: pointer;">
                                                <?php echo esc_html($group); ?> 
                                                <span style="color: #646970; font-weight: normal;">
                                                    (<?php echo count($caps); ?> <?php esc_html_e('capabilities', 'advanced-role-manager'); ?>)
                                                </span>
                                            </label>
                                        </div>
                                        
                                        <!-- Individual Capabilities List -->
                                        <div class="arm-cap-list" style="padding: 10px;">
                                            <?php foreach ($caps as $cap): ?>
                                                <div class="arm-capability-item" style="padding: 8px 15px; border-bottom: 1px solid #f0f0f1; display: flex; align-items: center;">
                                                    <label style="display: flex; align-items: center; width: 100%; cursor: pointer; margin: 0;">
                                                        <input type="checkbox" 
                                                               name="capabilities[]" 
                                                               value="<?php echo esc_attr($cap); ?>" 
                                                               class="cap-checkbox arm-checkbox" 
                                                               data-group="<?php echo esc_attr($group); ?>">
                                                        <span class="arm-capability-name" style="font-family: monospace; font-size: 13px; color: #1d2327; margin-left: 10px; flex: 1;">
                                                            <?php echo esc_html($cap); ?>
                                                        </span>
                                                        <span style="font-size: 11px; color: #646970;">
                                                            <?php 
                                                            $role_count = isset($cap_usage[$cap]) ? count($cap_usage[$cap]) : 0;
                                                            printf(
                                                                esc_html(_n('Used in %d role', 'Used in %d roles', $role_count, 'advanced-role-manager')),
                                                                $role_count
                                                            );
                                                            ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="arm-actions">
                                <div class="arm-bulk-actions">
                                    <button type="button" id="select-all-caps" class="arm-btn arm-btn-secondary">
                                        <?php esc_html_e('Select All', 'advanced-role-manager'); ?>
                                    </button>
                                    <button type="button" id="deselect-all-caps" class="arm-btn arm-btn-secondary">
                                        <?php esc_html_e('Deselect All', 'advanced-role-manager'); ?>
                                    </button>
                                    <span id="cleanup-count" style="margin-left: 15px; color: #646970; font-size: 13px;"></span>
                                </div>
                                <button type="submit" class="arm-btn arm-delete-btn" id="cleanup-btn" disabled>
                                    🗑️ <?php esc_html_e('Remove Selected Capabilities', 'advanced-role-manager'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Group checkbox functionality
            $('.group-checkbox').on('change', function() {
                var group = $(this).data('group');
                var isChecked = $(this).is(':checked');
                $('.cap-checkbox[data-group="' + group + '"]').prop('checked', isChecked);
                updateCleanupButton();
            });
            
            // Individual checkbox
            $('.cap-checkbox').on('change', function() {
                updateCleanupButton();
                
                // Update group checkbox state
                var group = $(this).data('group');
                var totalInGroup = $('.cap-checkbox[data-group="' + group + '"]').length;
                var checkedInGroup = $('.cap-checkbox[data-group="' + group + '"]:checked').length;
                $('.group-checkbox[data-group="' + group + '"]').prop('checked', totalInGroup === checkedInGroup);
            });
            
            // Select all button
            $('#select-all-caps').on('click', function() {
                $('.cap-checkbox, .group-checkbox').prop('checked', true);
                updateCleanupButton();
            });
            
            // Deselect all button
            $('#deselect-all-caps').on('click', function() {
                $('.cap-checkbox, .group-checkbox').prop('checked', false);
                updateCleanupButton();
            });
            
            // Update cleanup button state
            function updateCleanupButton() {
                var count = $('.cap-checkbox:checked').length;
                $('#cleanup-btn').prop('disabled', count === 0);
                
                if (count > 0) {
                    $('#cleanup-count').text(count + ' capabilit' + (count > 1 ? 'ies' : 'y') + ' selected');
                } else {
                    $('#cleanup-count').text('');
                }
            }
            
            // Form submission confirmation
            $('#cleanup-form').on('submit', function(e) {
                var count = $('.cap-checkbox:checked').length;
                var capabilities = [];
                
                $('.cap-checkbox:checked').each(function() {
                    capabilities.push($(this).val());
                });
                
                var message = 'Are you sure you want to remove ' + count + ' capabilit' + (count > 1 ? 'ies' : 'y') + '?\n\n';
                message += 'These capabilities will be removed from ALL roles.\n\n';
                message += 'Affected capabilities:\n' + capabilities.slice(0, 10).join('\n');
                
                if (capabilities.length > 10) {
                    message += '\n... and ' + (capabilities.length - 10) + ' more';
                }
                
                return confirm(message);
            });
        });
        </script>
        <?php
    }
    
    
    /**
     * Clear capability cache
     */
    private function clear_capability_cache() {
        delete_transient('arm_plugin_capabilities');
    }
    
    /**
     * Group capabilities by plugin prefix (optimized)
     */
    private function group_capabilities_by_plugin($capabilities) {
        $groups = [];
        
        // Known plugin prefixes (ordered by frequency for early matching)
        $known_prefixes = [
            'woocommerce' => 'WooCommerce',
            'shop_' => 'WooCommerce',
            'manage_woocommerce' => 'WooCommerce',
            'wpProQuiz' => 'WP Pro Quiz',
            'amelia' => 'Amelia',
            'tinvwl' => 'TI WooCommerce Wishlist',
            'ap_' => 'AnsPress',
            'wpseo' => 'Yoast SEO',
            'loco' => 'Loco Translate',
            'wpcf7' => 'Contact Form 7',
            'optimizemember' => 'OptimizeMember',
            'access_optimizemember' => 'OptimizeMember',
            'learndash' => 'LearnDash',
            'ld_' => 'LearnDash',
            'sfwd' => 'LearnDash',
            'elementor' => 'Elementor',
        ];
        
        foreach ($capabilities as $cap) {
            $grouped = false;
            
            // Try exact prefix match first (most common case)
            foreach ($known_prefixes as $prefix => $plugin_name) {
                if (strpos($cap, $prefix) === 0) {
                    if (!isset($groups[$plugin_name])) {
                        $groups[$plugin_name] = [];
                    }
                    $groups[$plugin_name][] = $cap;
                    $grouped = true;
                    break; // Stop after first match
                }
            }
            
            // If not grouped, try to extract prefix
            if (!$grouped) {
                if (preg_match('/^([a-z0-9]+)_/', $cap, $matches)) {
                    $prefix = ucfirst($matches[1]);
                    if (!isset($groups[$prefix])) {
                        $groups[$prefix] = [];
                    }
                    $groups[$prefix][] = $cap;
                } else {
                    // No clear prefix, put in "Other"
                    if (!isset($groups['Other'])) {
                        $groups['Other'] = [];
                    }
                    $groups['Other'][] = $cap;
                }
            }
        }
        
        // Sort groups by name
        ksort($groups);
        
        return $groups;
    }
    
    /**
     * Get user counts per role 
     */
    private function get_user_counts() {
        // Try to get from cache first
        $counts = get_transient('arm_user_counts');
        if ($counts !== false) {
            return $counts;
        }
        
        global $wpdb;
        $counts = [];
        
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_value, COUNT(*) as count 
                 FROM {$wpdb->usermeta} 
                 WHERE meta_key = %s
                 GROUP BY meta_value",
                $wpdb->prefix . 'capabilities'
            )
        );
        
        foreach ($results as $result) {
            $capabilities = maybe_unserialize($result->meta_value);
            if (is_array($capabilities)) {
                foreach (array_keys($capabilities) as $role) {
                    if (!isset($counts[$role])) {
                        $counts[$role] = 0;
                    }
                    $counts[$role] += $result->count;
                }
            }
        }
        
        // Cache for 5 minutes
        set_transient('arm_user_counts', $counts, 5 * MINUTE_IN_SECONDS);
        
        return $counts;
    }
    
    /**
     * Clear user count cache
     */
    private function clear_user_count_cache() {
        delete_transient('arm_user_counts');
    }
    
    /**
     * Validate capability name
     */
    private function validate_capability_name($capability) {
        // Must be lowercase alphanumeric with underscores
        if (!preg_match('/^[a-z0-9_]+$/', $capability)) {
            return false;
        }
        
        // Reasonable length
        if (strlen($capability) < 2 || strlen($capability) > 100) {
            return false;
        }
        
        // Cannot be numeric only
        if (is_numeric($capability)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log error (for debugging)
     */
    private function log_error($message, $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[ARM] %s - Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }
    
    /**
     * Handle role deletion
     */
    public function handle_delete_roles() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'advanced-role-manager'));
        }
        
        // Check nonce
        if (!isset($_POST['arm_delete_roles_nonce']) || 
            !wp_verify_nonce($_POST['arm_delete_roles_nonce'], 'arm_delete_roles_action')) {
            wp_die(__('Security check failed.', 'advanced-role-manager'));
        }
        
        // Get selected roles
        $roles_to_delete = isset($_POST['roles']) ? $_POST['roles'] : [];
        
        if (empty($roles_to_delete)) {
            $this->add_admin_notice(__('Please select at least one role to delete.', 'advanced-role-manager'), 'error');
            wp_redirect(admin_url('admin.php?page=advanced-role-manager'));
            exit;
        }
        
        $deleted_count = 0;
        $protected_skipped = 0;
        $users_reassigned = 0;
        $plugin_roles_deleted = [];
        
        foreach ($roles_to_delete as $role_slug) {
            $role_slug = sanitize_key($role_slug);
            
            // Skip protected roles
            if (in_array($role_slug, $this->protected_roles)) {
                $protected_skipped++;
                continue;
            }
            
            // Get users with this role
            $users = get_users(['role' => $role_slug]);
            
            // Reassign users to subscriber
            foreach ($users as $user) {
                $user_obj = new WP_User($user->ID);
                $user_obj->set_role('subscriber');
                $users_reassigned++;
            }
            
            // Track if this is a plugin role
            if (isset($this->plugin_roles[$role_slug])) {
                $plugin_roles_deleted[] = $this->plugin_roles[$role_slug] . ' (' . $role_slug . ')';
            }
            
            // Delete the role
            remove_role($role_slug);
            $deleted_count++;
        }
        
        // Clear caches
        $this->clear_user_count_cache();
        $this->clear_capability_cache();
        
        // Set success message
        $message = sprintf(
            _n('Successfully deleted %d role.', 'Successfully deleted %d roles.', $deleted_count, 'advanced-role-manager'),
            $deleted_count
        );
        
        if ($users_reassigned > 0) {
            $message .= ' ' . sprintf(
                _n('%d user was reassigned to Subscriber role.', '%d users were reassigned to Subscriber role.', $users_reassigned, 'advanced-role-manager'),
                $users_reassigned
            );
        }
        
        if ($protected_skipped > 0) {
            $message .= ' ' . sprintf(
                _n('%d protected role was skipped.', '%d protected roles were skipped.', $protected_skipped, 'advanced-role-manager'),
                $protected_skipped
            );
        }
        
        if (!empty($plugin_roles_deleted)) {
            $message .= ' ' . sprintf(
                __('Plugin roles deleted: %s. These may be recreated if you reactivate the plugins.', 'advanced-role-manager'),
                implode(', ', $plugin_roles_deleted)
            );
        }
        
        $this->add_admin_notice($message, 'success');
        
        // Redirect back
        wp_redirect(admin_url('admin.php?page=advanced-role-manager'));
        exit;
    }
    
    /**
     * Handle capability updates
     */
    public function handle_update_capabilities() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'advanced-role-manager'));
        }
        
        // Check nonce
        if (!isset($_POST['arm_update_capabilities_nonce']) || 
            !wp_verify_nonce($_POST['arm_update_capabilities_nonce'], 'arm_update_capabilities_action')) {
            wp_die(__('Security check failed.', 'advanced-role-manager'));
        }
        
        $role_slug = isset($_POST['role']) ? sanitize_key($_POST['role']) : '';
        $operation = isset($_POST['operation']) ? sanitize_key($_POST['operation']) : '';
        $capabilities = isset($_POST['capabilities']) ? $_POST['capabilities'] : [];
        
        if (empty($role_slug) || empty($capabilities)) {
            $this->add_admin_notice(__('Invalid request.', 'advanced-role-manager'), 'error');
            wp_redirect(admin_url('admin.php?page=arm-edit-role&role=' . urlencode($role_slug)));
            exit;
        }
        
        $role = get_role($role_slug);
        
        if (!$role) {
            $this->add_admin_notice(__('Role not found.', 'advanced-role-manager'), 'error');
            wp_redirect(admin_url('admin.php?page=advanced-role-manager'));
            exit;
        }
        
        $count = 0;
        
        if ($operation === 'add') {
            foreach ($capabilities as $cap) {
                $cap = sanitize_key($cap);
                if ($this->validate_capability_name($cap)) {
                    $role->add_cap($cap);
                    $count++;
                }
            }
            $message = sprintf(
                _n('Successfully added %d capability to %s role.', 'Successfully added %d capabilities to %s role.', $count, 'advanced-role-manager'),
                $count,
                $role_slug
            );
        } elseif ($operation === 'remove') {
            foreach ($capabilities as $cap) {
                $cap = sanitize_key($cap);
                $role->remove_cap($cap);
                $count++;
            }
            $message = sprintf(
                _n('Successfully removed %d capability from %s role.', 'Successfully removed %d capabilities from %s role.', $count, 'advanced-role-manager'),
                $count,
                $role_slug
            );
        }
        
        // Clear caches
        $this->clear_capability_cache();
        
        $this->add_admin_notice($message, 'success');
        wp_redirect(admin_url('admin.php?page=arm-edit-role&role=' . urlencode($role_slug)));
        exit;
    }
    
    /**
     * Handle copy capabilities
     */
    public function handle_copy_capabilities() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'advanced-role-manager'));
        }
        
        // Check nonce
        if (!isset($_POST['arm_copy_capabilities_nonce']) || 
            !wp_verify_nonce($_POST['arm_copy_capabilities_nonce'], 'arm_copy_capabilities_action')) {
            wp_die(__('Security check failed.', 'advanced-role-manager'));
        }
        
        $source_role_slug = isset($_POST['source_role']) ? sanitize_key($_POST['source_role']) : '';
        $target_role_slug = isset($_POST['target_role']) ? sanitize_key($_POST['target_role']) : '';
        
        if (empty($source_role_slug) || empty($target_role_slug)) {
            $this->add_admin_notice(__('Invalid request.', 'advanced-role-manager'), 'error');
            wp_redirect(admin_url('admin.php?page=arm-edit-role&role=' . urlencode($target_role_slug)));
            exit;
        }
        
        $source_role = get_role($source_role_slug);
        $target_role = get_role($target_role_slug);
        
        if (!$source_role || !$target_role) {
            $this->add_admin_notice(__('One or more roles not found.', 'advanced-role-manager'), 'error');
            wp_redirect(admin_url('admin.php?page=arm-edit-role&role=' . urlencode($target_role_slug)));
            exit;
        }
        
        // Remove all current capabilities from target
        foreach ($target_role->capabilities as $cap => $granted) {
            $target_role->remove_cap($cap);
        }
        
        // Copy all capabilities from source
        foreach ($source_role->capabilities as $cap => $granted) {
            if ($granted) {
                $target_role->add_cap($cap);
            }
        }
        
        // Clear caches
        $this->clear_capability_cache();
        
        $message = sprintf(
            __('Successfully copied %d capabilities from %s to %s.', 'advanced-role-manager'),
            count($source_role->capabilities),
            $source_role_slug,
            $target_role_slug
        );
        
        $this->add_admin_notice($message, 'success');
        wp_redirect(admin_url('admin.php?page=arm-edit-role&role=' . urlencode($target_role_slug)));
        exit;
    }
    
    /**
     * Handle cleanup capabilities
     */
    public function handle_cleanup_capabilities() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'advanced-role-manager'));
        }
        
        // Check nonce
        if (!isset($_POST['arm_cleanup_capabilities_nonce']) || 
            !wp_verify_nonce($_POST['arm_cleanup_capabilities_nonce'], 'arm_cleanup_capabilities_action')) {
            wp_die(__('Security check failed.', 'advanced-role-manager'));
        }
        
        $capabilities = isset($_POST['capabilities']) ? $_POST['capabilities'] : [];
        
        if (empty($capabilities)) {
            $this->add_admin_notice(__('Please select at least one capability to remove.', 'advanced-role-manager'), 'error');
            wp_redirect(admin_url('admin.php?page=arm-cleanup'));
            exit;
        }
        
        $wp_roles = wp_roles();
        $removed_count = 0;
        $roles_affected = [];
        
        // Remove selected capabilities from all roles
        foreach ($wp_roles->roles as $role_slug => $role_data) {
            $role = get_role($role_slug);
            if (!$role) continue;
            
            foreach ($capabilities as $cap) {
                $cap = sanitize_key($cap);
                if (isset($role->capabilities[$cap])) {
                    $role->remove_cap($cap);
                    $removed_count++;
                    $roles_affected[$role_slug] = true;
                }
            }
        }
        
        // Clear caches
        $this->clear_capability_cache();
        
        $message = sprintf(
            __('Successfully removed %d capability instance(s) from %d role(s).', 'advanced-role-manager'),
            $removed_count,
            count($roles_affected)
        );
        
        $this->add_admin_notice($message, 'success');
        wp_redirect(admin_url('admin.php?page=arm-cleanup'));
        exit;
    }
    
    /**
     * AJAX: Quick remove capability
     */
    public function ajax_quick_remove_capability() {
        check_ajax_referer('arm_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'advanced-role-manager'));
        }
        
        $role_slug = isset($_POST['role']) ? sanitize_key($_POST['role']) : '';
        $capability = isset($_POST['capability']) ? sanitize_key($_POST['capability']) : '';
        
        if (empty($role_slug) || empty($capability)) {
            wp_send_json_error(__('Invalid request', 'advanced-role-manager'));
        }
        
        $role = get_role($role_slug);
        if (!$role) {
            wp_send_json_error(__('Role not found', 'advanced-role-manager'));
        }
        
        $role->remove_cap($capability);
        $this->clear_capability_cache();
        
        wp_send_json_success([
            'message' => __('Capability removed successfully', 'advanced-role-manager')
        ]);
    }
    
    /**
     * Add admin notice
     */
    private function add_admin_notice($message, $type = 'success') {
        $notices = get_option('arm_admin_notices', []);
        $notices[] = ['message' => $message, 'type' => $type];
        update_option('arm_admin_notices', $notices);
    }
    
    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        $notices = get_option('arm_admin_notices', []);
        if (empty($notices)) {
            return;
        }
        
        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
        }
        
        delete_option('arm_admin_notices');
    }
}

// Initialize the plugin
new Advanced_Role_Manager();