<?php
/*
Plugin Name: Smart Notes
Description: Allow users to highlight text and save personal notes. Includes dashboard and shortcode.
<<<<<<< Updated upstream:smart-notes-plugin.php
Version: 1.1
=======
Version: 1.2.3
>>>>>>> Stashed changes:smart-notes.php
Author: Hafiz Faraz
Author URI: https://hfarazm.com/wordpress-plugins/smart-notes/
*/

register_activation_hook(__FILE__, 'snp_create_table');
function snp_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smart_notes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) NOT NULL,
        page_url TEXT NOT NULL,
        selected_text TEXT NOT NULL,
        comment TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'snp_enqueue_scripts');
function snp_enqueue_scripts() {
    wp_enqueue_script('snp-script', plugin_dir_url(__FILE__) . 'js/snp-script.js', ['jquery'], null, true);
    
    // Get customizable text settings
    $text_settings = snp_get_text_settings();
    
    wp_localize_script('snp-script', 'snp_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'texts' => $text_settings
    ]);
    wp_enqueue_style('snp-style', plugin_dir_url(__FILE__) . 'css/snp-style.css');
}

// Get text settings with defaults
function snp_get_text_settings() {
    $defaults = [
        'placeholder_text' => 'Add a personal note',
        'save_button_text' => 'Save',
        'cancel_button_text' => 'Cancel',
        'delete_confirmation' => 'Are you sure you want to remove this note?',
        'remove_button_text' => 'Remove',
        'error_prefix' => 'Error: ',
        'success_message' => 'Note saved successfully!',
        'delete_success' => 'Note deleted successfully!'
    ];
    
    $saved_settings = get_option('snp_text_settings', []);
    return wp_parse_args($saved_settings, $defaults);
}

// Handle AJAX save
add_action('wp_ajax_snp_save_note', 'snp_save_note');
function snp_save_note() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $text = sanitize_text_field($_POST['text']);
    $comment = sanitize_text_field($_POST['comment']);
    $url = esc_url_raw($_POST['url']);

    $table = $wpdb->prefix . 'smart_notes';
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'page_url' => $url,
        'selected_text' => $text,
        'comment' => $comment,
        'created_at' => current_time('mysql')
    ]);

    $settings = snp_get_text_settings();
    wp_send_json_success($settings['success_message']);
}


// Shortcode for number of notes 
add_shortcode('user_notes_quantity', function () {
    if (!is_user_logged_in()) return 'Please log in to view your notes.';

    global $wpdb;
    $user_id = get_current_user_id();
    $table = $wpdb->prefix . 'smart_notes';
    $notes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id));

    $output = '<div class="user-notes-quantity">';
   	echo count($notes);
    $output .= '</div>';
    return $output;
});


// Shortcode for listing notes
add_shortcode('user_notes_list', function () {
    if (!is_user_logged_in()) return 'Please log in to view your notes.';

    global $wpdb;
    $user_id = get_current_user_id();
    $table = $wpdb->prefix . 'smart_notes';
    $notes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id));

    $settings = snp_get_text_settings();
    $output = '<div class="user-notes-list">';
    foreach ($notes as $note) {
        $snippet = wp_trim_words($note->selected_text, 10);
        $output .= "<div class='note-item'>
            <strong>Page:</strong> <a href='{$note->page_url}'>{$note->page_url}</a><br>
            <strong>Text:</strong> {$snippet}<br>
            <strong>Note:</strong> {$note->comment}<br>
            <small>{$note->created_at}</small>
<<<<<<< Updated upstream:smart-notes-plugin.php
=======
			<button class='snp-delete-note' data-id='{$note->id}'>{$settings['remove_button_text']}</button>
>>>>>>> Stashed changes:smart-notes.php
        </div><hr>";
    }
    $output .= '</div>';
    return $output;
});

<<<<<<< Updated upstream:smart-notes-plugin.php
=======
// Shortcode for listing ALL notes for admin
add_shortcode('all_user_notes_list', function () {
    if (!is_user_logged_in()) return 'Please log in to view your notes.';

    global $wpdb;
    $table = $wpdb->prefix . 'smart_notes';
    $notes = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC"); // Fixed line

    $settings = snp_get_text_settings();
    $output = '<div class="user-notes-list">';
    foreach ($notes as $note) {
        $user_id = $note->user_id;
        $user_info = get_userdata($user_id);
        $admin_url = admin_url('user-edit.php?user_id=' .  $note->user_id);
        $user_name = esc_html($user_info->display_name);
        
        $snippet = wp_trim_words($note->selected_text, 10);
        $output .= "<div class='note-item'>
        <strong>Page:</strong> <a href='{$note->page_url}'>{$note->page_url}</a><br>
        <strong>Text:</strong> {$snippet}<br>
        <strong>Note:</strong> {$note->comment}<br>
        <strong>Date:</strong> {$note->created_at}</small><br>
        <strong>User:</strong> <a href='" . esc_url($admin_url) . "'>" . esc_html($user_name) . "</a></small>
        <button class='snp-delete-note' data-id='{$note->id}'>{$settings['remove_button_text']}</button>
        </div><hr>";	
    }
    $output .= '</div>';
    return $output;
});



>>>>>>> Stashed changes:smart-notes.php
// Admin menu
add_action('admin_menu', function () {
    add_menu_page('Smart Notes', 'Smart Notes', 'manage_options', 'smart-notes-dashboard', 'snp_admin_dashboard');
    add_submenu_page('smart-notes-dashboard', 'Text Settings', 'Text Settings', 'manage_options', 'smart-notes-settings', 'snp_settings_page');
});

// Register settings
add_action('admin_init', 'snp_register_settings');
function snp_register_settings() {
    register_setting('snp_settings_group', 'snp_text_settings', 'snp_sanitize_settings');
}

// Sanitize settings
function snp_sanitize_settings($input) {
    $sanitized = [];
    $allowed_keys = [
        'placeholder_text', 'save_button_text', 'cancel_button_text', 
        'delete_confirmation', 'remove_button_text', 'error_prefix',
        'success_message', 'delete_success'
    ];
    
    foreach ($allowed_keys as $key) {
        if (isset($input[$key])) {
            $sanitized[$key] = sanitize_text_field($input[$key]);
        }
    }
    
    return $sanitized;
}

// Settings page
function snp_settings_page() {
    $settings = snp_get_text_settings();
    
    if (isset($_POST['submit'])) {
        check_admin_referer('snp_settings_nonce');
        update_option('snp_text_settings', snp_sanitize_settings($_POST['snp_text_settings']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        $settings = snp_get_text_settings(); // Refresh settings
    }
    
    ?>
    <div class="wrap">
        <h1>Smart Notes - Text Settings</h1>
        <p>Customize the text elements that appear in the Smart Notes popup and interface.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('snp_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Popup Placeholder Text</th>
                    <td>
                        <input type="text" name="snp_text_settings[placeholder_text]" 
                               value="<?php echo esc_attr($settings['placeholder_text']); ?>" 
                               class="regular-text" />
                        <p class="description">Text shown in the note textarea placeholder</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Save Button Text</th>
                    <td>
                        <input type="text" name="snp_text_settings[save_button_text]" 
                               value="<?php echo esc_attr($settings['save_button_text']); ?>" 
                               class="regular-text" />
                        <p class="description">Text displayed on the save button</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Cancel Button Text</th>
                    <td>
                        <input type="text" name="snp_text_settings[cancel_button_text]" 
                               value="<?php echo esc_attr($settings['cancel_button_text']); ?>" 
                               class="regular-text" />
                        <p class="description">Text displayed on the cancel button</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Delete Confirmation Message</th>
                    <td>
                        <input type="text" name="snp_text_settings[delete_confirmation]" 
                               value="<?php echo esc_attr($settings['delete_confirmation']); ?>" 
                               class="regular-text" />
                        <p class="description">Confirmation message when deleting a note</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Remove Button Text</th>
                    <td>
                        <input type="text" name="snp_text_settings[remove_button_text]" 
                               value="<?php echo esc_attr($settings['remove_button_text']); ?>" 
                               class="regular-text" />
                        <p class="description">Text displayed on the remove/delete button</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Error Message Prefix</th>
                    <td>
                        <input type="text" name="snp_text_settings[error_prefix]" 
                               value="<?php echo esc_attr($settings['error_prefix']); ?>" 
                               class="regular-text" />
                        <p class="description">Prefix for error messages</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Success Message</th>
                    <td>
                        <input type="text" name="snp_text_settings[success_message]" 
                               value="<?php echo esc_attr($settings['success_message']); ?>" 
                               class="regular-text" />
                        <p class="description">Message shown when note is saved successfully</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Delete Success Message</th>
                    <td>
                        <input type="text" name="snp_text_settings[delete_success]" 
                               value="<?php echo esc_attr($settings['delete_success']); ?>" 
                               class="regular-text" />
                        <p class="description">Message shown when note is deleted successfully</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
            
            <h3>Reset to Defaults</h3>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-notes-settings&reset=1'), 'snp_reset_nonce'); ?>" 
                   class="button" onclick="return confirm('Are you sure you want to reset all text settings to defaults?');">
                   Reset All Settings
                </a>
            </p>
        </form>
    </div>
    <?php
    
    // Handle reset
    if (isset($_GET['reset']) && wp_verify_nonce($_GET['_wpnonce'], 'snp_reset_nonce')) {
        delete_option('snp_text_settings');
        wp_redirect(admin_url('admin.php?page=smart-notes-settings&reset_done=1'));
        exit;
    }
    
    if (isset($_GET['reset_done'])) {
        echo '<div class="notice notice-success"><p>Settings reset to defaults!</p></div>';
    }
}

function snp_admin_dashboard() {
    global $wpdb;
    $table = $wpdb->prefix . 'smart_notes';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table");

    echo "<div class='wrap'><h1>Smart Notes Dashboard</h1>";
    echo "<p>Total Notes: <strong>$total</strong></p>";
    echo "<p>Unique Users: <strong>$users</strong></p>";
    echo "</div>";
}