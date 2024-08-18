<?php
/**
 *
 * @package   Sms Sender by D I X Y
 * @author    Dinura Sellapperuma
 * @copyright 2024 Dinura Sellapperuma
 * @license   GPL-2.0-or-later
 *
 * Plugin Name: Sms Sender by D I X Y
 * Description: A simple plugin to send SMS..
 * Plugin URI:  https://dev.dinurasellapperuma.com/
 * Author:      Dinura Sellapperuma
 * Author URI:  https://dinurasellapperuma.com/
 * Created:     18.05.2024
 * Version:     1.9.3
 * Text Domain: sms-sender-by-dixy
 * Domain Path: /lang
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Copyright (C) 2024 Dinura Sellapperuma
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include Plugin Update Checker library
require_once plugin_dir_path(__FILE__) . 'includes/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Set up the update checker
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://update.dinurasellapperuma.com/plugins/sms-sender/meta-data/update-check.json',
    __FILE__,
    'sms-sender-by-dixy'
);

// Register the menu item
add_action('admin_menu', 'sms_sender_menu');
function sms_sender_menu() {
    add_menu_page(
        'SMS Sender', 
        'SMS Sender', 
        'manage_options', 
        'sms-sender', 
        'sms_sender_page', 
        'dashicons-email', 
        6
    );

    add_submenu_page(
        'sms-sender',
        'Send SMS',
        'Send SMS',
        'manage_options',
        'sms-sender-send',
        'sms_sender_send_page'
    );

    add_submenu_page(
        'sms-sender',
        'Check Balance',
        'Check Balance',
        'manage_options',
        'sms-sender-balance',
        'sms_sender_balance_page'
    );
}

// Plugin settings page
function sms_sender_page() {
    ?>
    <div class="wrap">
        <h1>SMS Sender Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('sms_sender_options_group');
                do_settings_sections('sms-sender');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register plugin settings
add_action('admin_init', 'sms_sender_settings');
function sms_sender_settings() {
    register_setting('sms_sender_options_group', 'sms_sender_api_key');
    register_setting('sms_sender_options_group', 'sms_sender_mask');
    
    add_settings_section(
        'sms_sender_settings_section',
        'SMS Settings',
        'sms_sender_settings_section_callback',
        'sms-sender'
    );
    
    add_settings_field(
        'sms_sender_api_key',
        'API Key',
        'sms_sender_api_key_callback',
        'sms-sender',
        'sms_sender_settings_section'
    );
    
    add_settings_field(
        'sms_sender_mask',
        'Sender Mask',
        'sms_sender_mask_callback',
        'sms-sender',
        'sms_sender_settings_section'
    );
}

function sms_sender_settings_section_callback() {
    echo 'Enter your API settings below:';
}

function sms_sender_api_key_callback() {
    $api_key = esc_attr(get_option('sms_sender_api_key'));
    echo "<input type='text' name='sms_sender_api_key' value='$api_key' />";
}

function sms_sender_mask_callback() {
    $mask = esc_attr(get_option('sms_sender_mask'));
    echo "<input type='text' name='sms_sender_mask' value='$mask' />";
}

// Add submenu for sending SMS
function sms_sender_send_page() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $phone_numbers = [];
        $message = sanitize_textarea_field($_POST['message']);
        
        if (!empty($_POST['phone_numbers'])) {
            $phone_numbers_csv = sanitize_text_field($_POST['phone_numbers']);
            $phone_numbers = array_map('trim', explode(',', $phone_numbers_csv));
        }

        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $csv_file = $_FILES['csv_file']['tmp_name'];
            $csv_data = array_map('str_getcsv', file($csv_file));
            foreach ($csv_data as $row) {
                foreach ($row as $phone) {
                    $phone_numbers[] = trim($phone);
                }
            }
        }

        $normalized_numbers = array_map('normalize_phone_number', $phone_numbers);
        
        $api_key = get_option('sms_sender_api_key');
        $mask = get_option('sms_sender_mask');
        
        foreach ($normalized_numbers as $phone) {
            $url = "https://portal.richmo.lk/api/v1/sms/send/?dst=$phone&from=$mask&msg=" . urlencode($message) . "&key=$api_key";
            $response = wp_remote_get($url);
            
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "<div class='error'><p>Error sending to $phone: $error_message</p></div>";
            } else {
                $response_body = wp_remote_retrieve_body($response);
                echo "<div class='updated'><p>Response from $phone: $response_body</p></div>";
            }
        }
    }
    ?>
    <div class="wrap">
        <h1>Send SMS</h1>
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Phone Numbers (CSV)</th>
                    <td><input type="text" name="phone_numbers" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Upload CSV File</th>
                    <td>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" style="display: none;" />
                        <label for="csv_file" class="button">Choose File</label>
                        <span id="file-name"></span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Message</th>
                    <td><textarea name="message" rows="5" cols="50" required></textarea></td>
                </tr>
            </table>
            <?php submit_button('Send SMS'); ?>
        </form>
    </div>
    <?php
}

function normalize_phone_number($phone) {
    // Remove any non-numeric characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // If the phone number doesn't start with '94', prepend '94'
    if (substr($phone, 0, 2) !== '94') {
        $phone = '94' . ltrim($phone, '0');
    }
    
    return $phone;
}

// Enqueue admin styles and scripts conditionally
add_action('admin_enqueue_scripts', 'sms_sender_admin_styles');
function sms_sender_admin_styles($hook_suffix) {
    // Load styles and scripts only on our plugin's pages
    if ($hook_suffix == 'toplevel_page_sms-sender' || 
        $hook_suffix == 'sms-sender_page_sms-sender-send' || 
        $hook_suffix == 'sms-sender_page_sms-sender-balance') {
        
        wp_enqueue_style('sms_sender_styles', plugin_dir_url(__FILE__) . 'styles.css');
        wp_enqueue_script('sms_sender_script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);
        wp_localize_script('sms_sender_script', 'smsSenderAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_key' => get_option('sms_sender_api_key')
        ]);
    }
}

// Add submenu for checking balance
function sms_sender_balance_page() {
    ?>
    <div class="wrap">
        <h1>Check Balance</h1>
        <button id="check-balance" class="button button-primary">Check Balance</button>
        <div id="balance-result" style="margin-top: 20px;"></div>
    </div>
    <?php
}

// AJAX handler for checking balance
add_action('wp_ajax_check_balance', 'sms_sender_check_balance');
function sms_sender_check_balance() {
    $api_key = get_option('sms_sender_api_key');
    $response = wp_remote_get('https://portal.richmo.lk/api/account/balance', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true); // Decode JSON response
        if (isset($data['balance'])) {
            // If balance is available in the response
            $balance = $data['balance'];
            wp_send_json_success('LKR: ' . $balance);
        } else {
            // If balance is not available in the response
            wp_send_json_error('Balance not found in response.');
        }
    }
}


?>
