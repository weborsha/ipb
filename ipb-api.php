<?php
/**
 * Plugin Name: API Ipb
 * Description: Integrates with an external API and saves the results in the database.
 * Version: 1.0
 * Author: Weborsha
 * Author URI: https://github.com/weborsha
 */

register_activation_hook(__FILE__, 'api_ipb_integration_create_table');
function api_ipb_integration_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'api_ipb_integration';
    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        completed BOOLEAN NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Integrate with the API and save the results in the database
function api_ipb_integration_sync_data() {
    $response = wp_remote_get('https://jsonplaceholder.typicode.com/todos');
    if (is_wp_error($response)) {
        // Log the error
        return;
    }
    $todos = json_decode(wp_remote_retrieve_body($response));
    global $wpdb;
    $table_name = $wpdb->prefix . 'api_ipb_integration';
    foreach ($todos as $todo) {
        $wpdb->insert($table_name, array(
            'title' => $todo->title,
            'completed' => $todo->completed,
        ));
    }
}

add_action('admin_menu', 'api_ipb_integration_menu');
function api_ipb_integration_menu() {
    add_menu_page('API Ipb Integration', 'API Ipb Integration', 'manage_options', 'api-ipb-integration', 'api_ipb_integration_page');
    add_submenu_page('api-ipb-integration', 'Sync Data', 'Sync Data', 'manage_options', 'api-ipb-integration-sync', 'api_ipb_integration_sync_page');
    add_submenu_page('api-ipb-integration', 'Search', 'Search', 'manage_options', 'api-ipb-integration-search', 'api_ipb_integration_search_page');
}

function api_ipb_integration_page() {
    echo '<div class="wrap"><h1>API Integration</h1></div>';
}

function api_ipb_integration_sync_page() {
    echo '<div class="wrap"><h1>Sync Data</h1>';
    echo '<form method="post" action="">
        <input type="submit" name="api_ipb_integration_sync" value="Sync Data" class="button button-primary">
    </form>';
    if (isset($_POST['api_ipb_integration_sync'])) {
        api_ipb_integration_sync_data();
        echo '<div class="notice notice-success"><p>Data synced successfully.</p></div>';
    }
    echo '</div>';
}

function api_ipb_integration_search_page() {
    echo '<div class="wrap"><h1>Search</h1>';
    echo '<form method="get" action="">
        <input type="text" name="s" value="' . esc_attr($_GET['s']) . '">
        <input type="submit" value="Search">
    </form>';
    if (isset($_GET['s'])) {
        $search_term = sanitize_text_field($_GET['s']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'api_ipb_integration';
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name
            WHERE title LIKE %s
            AND completed = 0
            ORDER BY RAND()
            LIMIT 5
        ", '%' . $search_term . '%'));
        if ($results) {
            echo '<ul>';
            foreach ($results as $result) {
                echo '<li>' . $result->title . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No results found.</p>';
        }
    }
    echo '</div>';
}
