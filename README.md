# advanced-role-manager
A powerful WordPress role and capability manager. View, edit, and delete custom roles, manage permissions with an intuitive interface, and automatically clean up orphaned capabilities left behind by deactivated plugins.

# Advanced Role Manager

**Version:** 2.0.0  
**Author:** Villy Goutova  
**License:** GPL v2 or later  
**Requires WordPress:** 5.0 or higher  
**Tested up to:** 6.4

A powerful and intuitive WordPress plugin for managing user roles and capabilities. View, edit, delete custom roles, and clean up orphaned capabilities left behind by deactivated plugins.

## Description

Advanced Role Manager provides WordPress administrators with complete control over user roles and capabilities. Whether you need to manage custom roles, audit role permissions, or clean up leftover capabilities from deleted plugins, this plugin offers a clean, user-friendly interface to handle it all.

Perfect for:
- Managing WooCommerce, LearnDash, and other plugin-specific roles
- Cleaning up orphaned capabilities from deactivated plugins
- Auditing role permissions across your site
- Creating and managing custom user roles
- Bulk operations on roles and capabilities

## Key Features

### 📊 **Role Management Dashboard**
- View all WordPress roles in one place
- See user counts for each role
- Quick overview of role capabilities
- Protected role indicators for built-in WordPress roles
- Plugin-specific role badges (WooCommerce, etc.)

### ✏️ **Edit Role Capabilities**
- Intuitive checkbox interface for capability management
- Visual grouping of related capabilities
- Copy capabilities from other roles
- Add custom capabilities
- Real-time capability preview

### 🧹 **Capability Cleanup Tool**
- Identify orphaned capabilities from deactivated plugins
- Group capabilities by plugin origin
- Bulk remove unused capabilities
- Safely clean up your WordPress database
- Automatic detection of WooCommerce, LearnDash, Elementor, and more

### 🛡️ **Safety Features**
- Protected default WordPress roles (Administrator, Editor, Author, Contributor, Subscriber)
- Confirmation dialogs for destructive actions
- User count warnings before role deletion
- Nonce verification for all actions
- Permission checks on all operations

### 📈 **Statistics & Analytics**
- Total role count
- Custom role tracking
- Plugin role identification
- User distribution across roles

## Installation

### Automatic Installation
1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "Advanced Role Manager"
4. Click **Install Now** and then **Activate**

### Manual Installation
1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New > Upload Plugin**
4. Choose the downloaded zip file and click **Install Now**
5. Click **Activate Plugin**

### FTP Installation
1. Download and unzip the plugin
2. Upload the `advanced-role-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

## Usage

### Accessing the Plugin
After activation, you'll find **Role Manager** in your WordPress admin sidebar with a groups icon.

### Managing Roles

#### Viewing Roles
1. Navigate to **Role Manager** in the admin menu
2. View all roles with their user counts and capability previews
3. Use the checkbox to select roles for bulk operations

#### Editing a Role
1. Click the **Edit** button next to any role
2. Check or uncheck capabilities as needed
3. Add custom capabilities in the provided field
4. Click **Update Capabilities** to save changes

#### Copying Capabilities
1. While editing a role, locate the **Copy Capabilities** section
2. Select the source role you want to copy from
3. Click **Copy Capabilities**
4. Capabilities will be merged with existing ones

#### Deleting Roles
1. Select one or more custom roles using checkboxes
2. Click the **Delete Selected Roles** button
3. Confirm the deletion
4. **Note:** Protected WordPress roles cannot be deleted

### Cleaning Up Orphaned Capabilities

#### What are Orphaned Capabilities?
Orphaned capabilities are permissions left behind when plugins are deactivated or deleted. For example, if you remove WooCommerce, capabilities like `manage_woocommerce` may remain in your database unnecessarily.

#### Using the Cleanup Tool
1. Navigate to **Role Manager > Cleanup**
2. Review the list of orphaned capabilities grouped by plugin
3. Select individual capabilities or entire plugin groups
4. Click **Remove Selected Capabilities**
5. Confirm the cleanup operation

The tool automatically identifies capabilities from:
- WooCommerce
- LearnDash
- Elementor
- WP Pro Quiz
- Amelia
- Yoast SEO
- Contact Form 7
- And many more...

## Frequently Asked Questions

### Can I delete the Administrator role?
No, the plugin protects all default WordPress roles (Administrator, Editor, Author, Contributor, Subscriber) from deletion to prevent accidental site lockouts.

### Will deleting a role delete users assigned to that role?
No, users are never deleted. WordPress will automatically reassign users to the default Subscriber role when their role is deleted.

### Is it safe to remove orphaned capabilities?
Yes, if a plugin is no longer active, its capabilities are not needed. However, if you plan to reactivate a plugin, you may want to keep its capabilities.

### Can I undo capability changes?
The plugin doesn't have a built-in undo feature. However, you can:
1. Copy capabilities from another role to restore them
2. Manually re-add capabilities
3. Use a database backup to restore previous states

### Does this work with multisite?
The plugin works on multisite installations but operates on a per-site basis. Each site in the network has its own role management.

### Will this affect my site's performance?
No, the plugin only loads on admin pages and has minimal impact on site performance. All operations are performed only when needed.

## Capability Groups

The plugin automatically groups capabilities for easier management:

- **Core WordPress:** Basic post, page, and media capabilities
- **Administration:** User and plugin management capabilities
- **WooCommerce:** Shop and product management capabilities
- **LearnDash:** Course and lesson management capabilities
- **Custom Plugin:** Capabilities from other installed plugins

## Security & Permissions

- All actions require the `manage_options` capability (Administrator only)
- CSRF protection via WordPress nonces on all forms
- Input sanitization on all user inputs
- No direct file access allowed
- Protected roles cannot be modified or deleted

## Developer Notes

### Filters Available

The plugin is designed to be extensible. Developers can hook into various actions:

- `admin_post_arm_delete_roles`
- `admin_post_arm_update_capabilities`
- `admin_post_arm_copy_capabilities`
- `admin_post_arm_cleanup_capabilities`

### Protected Roles Array
```php
['administrator', 'editor', 'author', 'contributor', 'subscriber']
```

### Common Capabilities Array
The plugin maintains a list of 25+ standard WordPress capabilities used to identify orphaned capabilities.

## Changelog

### Version 2.0.0
- Complete UI redesign with modern WordPress admin styling
- Added capability cleanup tool for orphaned capabilities
- Improved role editing interface with grouped capabilities
- Added copy capabilities feature
- Enhanced statistics dashboard
- Added plugin-specific role detection
- Improved security with better nonce verification
- Added user count tracking per role
- Better error handling and user feedback
- Mobile-responsive interface

### Version 1.0.0
- Initial release
- Basic role viewing and editing
- Role deletion functionality

## Support

For support, feature requests, or bug reports:
- **Website:** https://codeangels.solutions
- **Author:** Villy Goutova

## Credits

Developed by [Villy Goutova](https://codeangels.solutions)

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Administrator access to WordPress

## Contributing

Contributions are welcome! If you'd like to contribute to this plugin, please ensure your code follows WordPress coding standards.

---

**Made with ❤️ for the WordPress community**

