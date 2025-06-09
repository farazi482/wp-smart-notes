<?php
/*
Plugin Name: Smart Notes
Description: Allow users to highlight text and save personal notes. Includes dashboard and shortcode.
Version: 1.2.2
Author: Hafiz Faraz
Author URI: https://hfarazm.com/wordpress-plugins/smart-notes/
Plugin URI: https://hfarazm.com/wordpress-plugins/smart-notes/
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
    wp_localize_script('snp-script', 'snp_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
    wp_enqueue_style('snp-style', plugin_dir_url(__FILE__) . 'css/snp-style.css');
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

    wp_send_json_success('Saved');
}

// Handle AJAX delete
add_action('wp_ajax_snp_delete_note', 'snp_delete_note');
function snp_delete_note() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    global $wpdb;
    $note_id = intval($_POST['note_id']);
    $user_id = get_current_user_id();
    $table = $wpdb->prefix . 'smart_notes';

    // Admin can delete any note, user only their own
    $can_delete = current_user_can('manage_options') || $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id = %d AND user_id = %d", $note_id, $user_id));
    
    if ($can_delete) {
        $wpdb->delete($table, ['id' => $note_id]);
        wp_send_json_success('Deleted');
    } else {
        wp_send_json_error('Permission denied');
    }
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

	
    $output = '<div class="user-notes-list">';
    foreach ($notes as $note) {
        $snippet = wp_trim_words($note->selected_text, 10);
        $output .= "<div class='note-item'>
            <strong>Page:</strong> <a href='{$note->page_url}'>{$note->page_url}</a><br>
            <strong>Text:</strong> {$snippet}<br>
            <strong>Note:</strong> {$note->comment}<br>
            <small>{$note->created_at}</small>
			<button class='snp-delete-note' data-id='{$note->id}'>Remove</button>
        </div><hr>";
    }
    $output .= '</div>';
    return $output;
});

// Shortcode for listing ALL notes for admin
add_shortcode('all_user_notes_list', function () {
    if (!is_user_logged_in()) return 'Please log in to view your notes.';

    global $wpdb;
    $table = $wpdb->prefix . 'smart_notes';
    $notes = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC"); // Fixed line

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
        <button class='snp-delete-note' data-id='{$note->id}'>Remove</button>
        </div><hr>";	
    }
    $output .= '</div>';
    return $output;
});



// Admin menu
add_action('admin_menu', function () {
    add_menu_page('Smart Notes', 'Smart Notes', 'manage_options', 'smart-notes-dashboard', 'snp_admin_dashboard');
});

function snp_admin_dashboard() {
    global $wpdb;
    $table = $wpdb->prefix . 'smart_notes';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table");

	
    echo "<div class='wrap'><h1>Smart Notes Dashboard</h1>";
    echo "<p>Total Notes: <strong>$total</strong></p>";
    echo "<p>Unique Users: <strong>$users</strong></p>";
    echo "</div>";
	
	echo "<div class='wrap'><h2>All notes</h2>	";
	echo do_shortcode('[all_user_notes_list]');
	 echo "<div class='wrap'><h2>ShortCodes</h2>
	<table>
	<tr><th style='text-align: left;'>Shortcode</th><th style='text-align: left;'>Description</th><tr>
	<tr><td>[user_notes_list]</td><td>Shortcode for listing notes</td></tr>
	<tr><td>[user_notes_quantity]</td><td>Shortcode for number of notes</td></tr>
	</table>
	<div>";
}


