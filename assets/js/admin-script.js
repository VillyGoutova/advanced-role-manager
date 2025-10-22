/**
 * Advanced Role Manager - Admin Scripts
 */

jQuery(document).ready(function($) {
    
    // ============================================
    // Main Roles Page Scripts
    // ============================================
    
    // Select all checkbox functionality
    $('#select-all').on('change', function() {
        $('.role-checkbox').prop('checked', $(this).is(':checked'));
        updateDeleteButton();
    });
    
    // Individual checkbox change
    $('.role-checkbox').on('change', function() {
        updateDeleteButton();
        
        // Update "select all" checkbox state
        var total = $('.role-checkbox').length;
        var checked = $('.role-checkbox:checked').length;
        $('#select-all').prop('checked', total === checked);
    });
    
    // Update delete button state
    function updateDeleteButton() {
        var count = $('.role-checkbox:checked').length;
        $('#delete-btn').prop('disabled', count === 0);
        
        if (count > 0) {
            $('#selected-count').text(count + ' role' + (count > 1 ? 's' : '') + ' selected');
        } else {
            $('#selected-count').text('');
        }
    }
    
    // Form submission with confirmation
    $('#roles-form').on('submit', function(e) {
        var checkedBoxes = $('.role-checkbox:checked');
        var count = checkedBoxes.length;
        
        if (count === 0) {
            e.preventDefault();
            return false;
        }
        
        var usersAffected = 0;
        var pluginRoles = [];
        
        checkedBoxes.each(function() {
            var users = parseInt($(this).data('users')) || 0;
            var pluginName = $(this).data('plugin');
            var roleName = $(this).val();
            
            usersAffected += users;
            
            var isPlugin = pluginName && pluginName.length > 0;
            
            if (isPlugin) {
                pluginRoles.push(pluginName + ' (' + roleName + ')');
            }
        });
        
        var message = armData.strings.confirmDelete;
        
        if (usersAffected > 0) {
            message += '\n\n⚠️ ' + armData.strings.warning + ': ' + usersAffected + ' ' + armData.strings.usersAffected;
        }
        
        if (pluginRoles.length > 0) {
            message += '\n\n⚡ ' + armData.strings.pluginRoles + ':\n' + pluginRoles.join('\n');
            message += '\n\n' + armData.strings.pluginWarning;
        }
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // ============================================
    // Edit Role Page Scripts
    // ============================================
    
    // Search functionality for current capabilities
    $('#search-current').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        $('#current-caps-list .arm-capability-item').each(function() {
            var cap = $(this).data('cap').toLowerCase();
            $(this).toggle(cap.indexOf(search) > -1);
        });
    });
    
    // Search functionality for available capabilities
    $('#search-available').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        $('#available-caps-list .arm-capability-item').each(function() {
            var cap = $(this).data('cap').toLowerCase();
            var isVisible = cap.indexOf(search) > -1;
            
            // Also check filter
            var activeFilter = $('.arm-filter-tab.active').data('filter');
            if (activeFilter !== 'all') {
                var type = $(this).data('type');
                isVisible = isVisible && type === activeFilter;
            }
            
            $(this).toggle(isVisible);
        });
    });
    
    // Filter tabs
    $('.arm-filter-tab').on('click', function() {
        $('.arm-filter-tab').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        var search = $('#search-available').val().toLowerCase();
        
        $('#available-caps-list .arm-capability-item').each(function() {
            var cap = $(this).data('cap').toLowerCase();
            var type = $(this).data('type');
            var matchesSearch = cap.indexOf(search) > -1;
            var matchesFilter = filter === 'all' || type === filter;
            $(this).toggle(matchesSearch && matchesFilter);
        });
    });
    
    // Remove capabilities form
    $('#remove-caps-form input[type="checkbox"]').on('change', function() {
        updateRemoveButton();
    });
    
    function updateRemoveButton() {
        var count = $('#remove-caps-form input[type="checkbox"]:checked').length;
        $('#remove-caps-btn').prop('disabled', count === 0);
        
        if (count > 0) {
            $('#remove-count').text(count + ' selected');
        } else {
            $('#remove-count').text('');
        }
    }
    
    // Add capabilities form
    $('#add-caps-form input[type="checkbox"]').on('change', function() {
        updateAddButton();
    });
    
    function updateAddButton() {
        var count = $('#add-caps-form input[type="checkbox"]:checked').length;
        $('#add-caps-btn').prop('disabled', count === 0);
        
        if (count > 0) {
            $('#add-count').text(count + ' selected');
        } else {
            $('#add-count').text('');
        }
    }
    
    // Form submission confirmation for remove capabilities
    $('#remove-caps-form').on('submit', function(e) {
        var count = $('#remove-caps-form input[type="checkbox"]:checked').length;
        if (count === 0) {
            e.preventDefault();
            return false;
        }
        
        var capabilities = [];
        $('#remove-caps-form input[type="checkbox"]:checked').each(function() {
            capabilities.push($(this).val());
        });
        
        var message = armData.strings.confirmRemove + '\n\n';
        message += count + ' capabilit' + (count > 1 ? 'ies' : 'y') + ' will be removed.\n\n';
        message += 'Capabilities:\n' + capabilities.slice(0, 10).join('\n');
        
        if (capabilities.length > 10) {
            message += '\n... and ' + (capabilities.length - 10) + ' more';
        }
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // ============================================
    // Cleanup Page Scripts
    // ============================================
    
    // Group checkbox functionality
    $('.group-checkbox').on('change', function() {
        var group = $(this).data('group');
        var isChecked = $(this).is(':checked');
        $('.cap-checkbox[data-group="' + group + '"]').prop('checked', isChecked);
        updateCleanupButton();
    });
    
    // Individual capability checkbox
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
    
    // Form submission confirmation for cleanup
    $('#cleanup-form').on('submit', function(e) {
        var count = $('.cap-checkbox:checked').length;
        
        if (count === 0) {
            e.preventDefault();
            return false;
        }
        
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
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // ============================================
    // AJAX Functions (Optional Enhancement)
    // ============================================
    
    // Quick remove capability via AJAX
    $('.arm-quick-remove').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var role = $btn.data('role');
        var capability = $btn.data('capability');
        
        if (!confirm('Remove this capability?')) {
            return;
        }
        
        $btn.prop('disabled', true).text('Removing...');
        
        $.ajax({
            url: armData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'arm_quick_remove_cap',
                nonce: armData.nonce,
                role: role,
                capability: capability
            },
            success: function(response) {
                if (response.success) {
                    $btn.closest('.arm-capability-item').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + response.data);
                    $btn.prop('disabled', false).text('Remove');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).text('Remove');
            }
        });
    });
});