<?php
/*
Plugin Name: Website Status Alerts
Plugin URI: https://gutsycreatives.com/
Description: A plugin to check the status of a website and send an alert if it is down
Version: 1.2
Author: Gutsy Creatives
Author URI: https://gutsycreatives.com/
*/


function check_wp_status() {

    $url = sanitize_text_field(get_option('wp_status_url', 'https://example.com'));
    $email = sanitize_email(get_option('wp_status_email', 'admin@example.com'));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($httpcode != 200) {
        // send alert
        mail($email, 'Website Down', 'The website '.$url.' is down');
    }
}

function schedule_wp_status_check() {
    if (! wp_next_scheduled ( 'check_wp_status_hook' )) {
        wp_schedule_event(time(), 'hourly', 'check_wp_status_hook');
    }
}

add_action('wp', 'schedule_wp_status_check');
add_action('check_wp_status_hook', 'check_wp_status');


function clear_scheduled_event() {
    wp_clear_scheduled_hook('check_wp_status_hook');
}


register_deactivation_hook( __FILE__, 'clear_scheduled_event');

function wp_status_checker_menu() {
    add_options_page( 'WP Status Checker Options', 'WP Status Checker', 'manage_options', 'wp-status-checker', 'wp_status_checker_options' );
}
add_action( 'admin_menu', 'wp_status_checker_menu' );

function wp_status_checker_options(){
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    if(isset($_POST['submit'])){
        if(!wp_verify_nonce($_POST['_wpnonce'])){
            wp_die('Invalid nonce');
        }
        $email = sanitize_email($_POST['wp_status_email']);
        $url = sanitize_text_field($_POST['wp_status_url']);
        update_option( 'wp_status_email', $email );
        update_option( 'wp_status_url', $url );
    }
    $email = get_option('wp_status_email', 'admin@example.com');
    $url = get_option('wp_status_url', 'https://example.com');
    ?>
    <div class="wrap">
        <h1>WP Status Checker Options</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Email</th>
                    <td>
                        <input type="email" name="wp_status_email" value="<?php echo esc_attr( $email ); ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Website URL</th>
                    <td>
                        <input type="text" name="wp_status_url" value="<?php echo esc_attr( $url ); ?>"/>
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field(); ?>
            <?php submit_button(); ?>

            <input type="hidden" name="test_email" value="1">
            <?php submit_button('Send Test Email'); ?>

            <?php 
            if(isset($_POST['test_email']) && $_POST['test_email'] == 1) {
                if(wp_status_send_test_email()){
                    echo "<div id='message' class='updated notice is-dismissible'><p>Test email sent successfully.</p></div>";
                }
            }
            ?>
            
        </form>
    </div>
    <?php

    if(isset($_POST['test_email']) && $_POST['test_email'] == 1) {
    if(!wp_verify_nonce($_POST['_wpnonce'])){
        wp_die('Invalid nonce');
    }
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    if(!empty(get_option( 'wp_status_email' )) && !empty(get_option( 'wp_status_url' ))){
        wp_status_send_test_email();
    }else{
        echo '<div class="error notice">Please make sure Email and URL are set</div>';
    }
    }

}


function wp_status_checker_register_settings() {
    register_setting( 'wp-status-checker-settings-group', 'wp_status_email' );
    register_setting( 'wp-status-checker-settings-group', 'wp_status_url' );
}
add_action( 'admin_init', 'wp_status_checker_register_settings' );

function wp_status_send_test_email(){
    $email = get_option('wp_status_email');
    $url = get_option('wp_status_url');
    if(!empty($email) && !empty($url)){
        $subject = 'Test Email from WP Status Checker';
        $message = 'This is a test email sent from the WP Status Checker plugin. The website '.$url.' is currently UP.';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        return wp_mail( $email, $subject, $message, $headers );
    }
    return false;
}


?>