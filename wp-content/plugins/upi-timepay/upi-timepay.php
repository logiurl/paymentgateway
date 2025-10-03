<?php
/**
 * Plugin Name: UPI TimePay
 * Description: Time-limited UPI QR/button with OCR proof upload and admin confirmation.
 * Version: 0.1.0
 * Author: Cursor Assistant
 * License: GPLv2 or later
 * Text Domain: upi-timepay
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UPI_TIMEPAY_VERSION', '0.1.0');
define('UPI_TIMEPAY_DIR', plugin_dir_path(__FILE__));
define('UPI_TIMEPAY_URL', plugin_dir_url(__FILE__));

require_once UPI_TIMEPAY_DIR . 'includes/utils.php';
require_once UPI_TIMEPAY_DIR . 'includes/cpt.php';
require_once UPI_TIMEPAY_DIR . 'includes/settings.php';
require_once UPI_TIMEPAY_DIR . 'includes/shortcodes.php';
require_once UPI_TIMEPAY_DIR . 'includes/ajax.php';
require_once UPI_TIMEPAY_DIR . 'includes/admin-columns.php';

function upi_timepay_activate() {
    // Ensure CPT exists then flush rules
    upi_timepay_register_cpt();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'upi_timepay_activate');

function upi_timepay_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'upi_timepay_deactivate');

function upi_timepay_enqueue_assets() {
    // Styles (registered only; enqueued when shortcode renders)
    wp_register_style('upi-timepay-style', UPI_TIMEPAY_URL . 'assets/css/style.css', array(), UPI_TIMEPAY_VERSION);

    // External libs
    wp_register_script('qrcodejs', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', array(), '1.0.0', true);
    wp_register_script('tesseractjs', 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js', array(), '5.0.0', true);

    // Frontend
    wp_register_script('upi-timepay-frontend', UPI_TIMEPAY_URL . 'assets/js/frontend.js', array('jquery', 'qrcodejs', 'tesseractjs'), UPI_TIMEPAY_VERSION, true);

    $localized = array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('upi_timepay_nonce'),
        'nowTs'   => time(),
        'i18n'    => array(
            'expired' => __('Expired', 'upi-timepay'),
            'countdownPrefix' => __('Time left', 'upi-timepay'),
            'uploading' => __('Uploading…', 'upi-timepay'),
            'recognizing' => __('Recognizing text…', 'upi-timepay'),
            'thankYou' => __('Thank you! We will confirm your order shortly.', 'upi-timepay'),
            'error' => __('Something went wrong. Please try again.', 'upi-timepay'),
        ),
    );
    wp_localize_script('upi-timepay-frontend', 'UPITimePay', $localized);
}
add_action('wp_enqueue_scripts', 'upi_timepay_enqueue_assets');
