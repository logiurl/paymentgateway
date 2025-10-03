<?php
if (!defined('ABSPATH')) { exit; }

function upi_timepay_handle_submit() {
    check_ajax_referer('upi_timepay_nonce', 'nonce');

    $ref = isset($_POST['ref']) ? sanitize_text_field(wp_unslash($_POST['ref'])) : '';
    $expires_at = isset($_POST['expires_at']) ? intval($_POST['expires_at']) : 0;
    $amount = isset($_POST['amount']) ? sanitize_text_field(wp_unslash($_POST['amount'])) : '';
    $note = isset($_POST['note']) ? sanitize_text_field(wp_unslash($_POST['note'])) : '';
    $upi_id = isset($_POST['upi_id']) ? sanitize_text_field(wp_unslash($_POST['upi_id'])) : '';
    $payee_name = isset($_POST['payee_name']) ? sanitize_text_field(wp_unslash($_POST['payee_name'])) : '';
    $extracted_id = isset($_POST['extracted_id']) ? sanitize_text_field(wp_unslash($_POST['extracted_id'])) : '';
    $ocr_text = isset($_POST['ocr_text']) ? wp_kses_post(wp_unslash($_POST['ocr_text'])) : '';
    $sig = isset($_POST['sig']) ? sanitize_text_field(wp_unslash($_POST['sig'])) : '';

    if (empty($ref) || empty($upi_id) || empty($payee_name)) {
        wp_send_json_error(array('message' => 'Missing data'));
    }

    // Verify signature (prevents tampering with expiry and parameters)
    $expected_sig = upi_timepay_build_signature($ref, $expires_at, $amount, $upi_id, $payee_name, $note);
    if (empty($sig) || !hash_equals($expected_sig, $sig)) {
        wp_send_json_error(array('message' => 'Invalid signature'));
    }

    if (time() > $expires_at) {
        wp_send_json_error(array('message' => 'Expired'));
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    $user = wp_get_current_user();

    $post_id = wp_insert_post(array(
        'post_type' => 'upi_payment',
        'post_status' => 'publish',
        'post_title' => sprintf('UPI Payment - %s', $ref),
    ));

    if (is_wp_error($post_id) || !$post_id) {
        wp_send_json_error(array('message' => 'Could not create record'));
    }

    // Upload screenshot if provided
    $attachment_id = 0;
    if (!empty($_FILES['screenshot']) && isset($_FILES['screenshot']['tmp_name']) && is_uploaded_file($_FILES['screenshot']['tmp_name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Limit size ~5MB
        if (intval($_FILES['screenshot']['size']) > 5 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File too large'));
        }

        $attachment_id = media_handle_upload('screenshot', $post_id);
        if (is_wp_error($attachment_id)) {
            $attachment_id = 0;
        }
    }

    update_post_meta($post_id, 'upi_ref', $ref);
    update_post_meta($post_id, 'amount', $amount);
    update_post_meta($post_id, 'note', $note);
    update_post_meta($post_id, 'upi_id', $upi_id);
    update_post_meta($post_id, 'payee_name', $payee_name);
    update_post_meta($post_id, 'ocr_extracted_id', $extracted_id);
    update_post_meta($post_id, 'ocr_text', $ocr_text);
    update_post_meta($post_id, 'expires_at', $expires_at);
    update_post_meta($post_id, 'payment_status', 'pending');
    update_post_meta($post_id, 'screenshot_id', $attachment_id);
    update_post_meta($post_id, 'submitter_ip', $ip);
    if ($user && $user->ID) {
        update_post_meta($post_id, 'user_id', intval($user->ID));
        update_post_meta($post_id, 'user_email', sanitize_email($user->user_email));
    }

    $opts = get_option('upi_timepay_settings', array());
    $redirect = isset($opts['thank_you_url']) && $opts['thank_you_url'] ? esc_url_raw($opts['thank_you_url']) : '';

    wp_send_json_success(array(
        'redirect' => $redirect,
        'message' => __('Thank you! We will confirm your order once finalized.', 'upi-timepay')
    ));
}
add_action('wp_ajax_upi_timepay_submit', 'upi_timepay_handle_submit');
add_action('wp_ajax_nopriv_upi_timepay_submit', 'upi_timepay_handle_submit');
