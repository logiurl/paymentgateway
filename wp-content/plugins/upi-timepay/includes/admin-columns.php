<?php
if (!defined('ABSPATH')) { exit; }

function upi_timepay_columns($columns) {
    $new = array();
    $new['cb'] = $columns['cb'];
    $new['title'] = __('Title', 'upi-timepay');
    $new['ref'] = __('Ref', 'upi-timepay');
    $new['amount'] = __('Amount', 'upi-timepay');
    $new['txn'] = __('Txn ID', 'upi-timepay');
    $new['status'] = __('Status', 'upi-timepay');
    $new['screenshot'] = __('Screenshot', 'upi-timepay');
    $new['date'] = __('Date', 'upi-timepay');
    return $new;
}
add_filter('manage_edit-upi_payment_columns', 'upi_timepay_columns');

function upi_timepay_column_content($column, $post_id) {
    switch ($column) {
        case 'ref':
            echo esc_html(get_post_meta($post_id, 'upi_ref', true));
            break;
        case 'amount':
            echo esc_html(get_post_meta($post_id, 'amount', true));
            break;
        case 'txn':
            echo esc_html(get_post_meta($post_id, 'ocr_extracted_id', true));
            break;
        case 'status':
            $st = get_post_meta($post_id, 'payment_status', true);
            echo $st ? esc_html($st) : 'pending';
            break;
        case 'screenshot':
            $aid = intval(get_post_meta($post_id, 'screenshot_id', true));
            if ($aid) {
                $url = wp_get_attachment_url($aid);
                if ($url) { echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html__('View', 'upi-timepay') . '</a>'; }
            }
            break;
    }
}
add_action('manage_upi_payment_posts_custom_column', 'upi_timepay_column_content', 10, 2);

function upi_timepay_row_actions($actions, $post) {
    if ($post->post_type === 'upi_payment') {
        $status = get_post_meta($post->ID, 'payment_status', true);
        if ($status !== 'confirmed') {
            $url = wp_nonce_url(admin_url('admin-post.php?action=upi_timepay_confirm&post_id=' . $post->ID), 'upi_timepay_confirm_' . $post->ID);
            $actions['upi_timepay_confirm'] = '<a href="' . esc_url($url) . '">' . esc_html__('Confirm', 'upi-timepay') . '</a>';
        }
    }
    return $actions;
}
add_filter('post_row_actions', 'upi_timepay_row_actions', 10, 2);

function upi_timepay_handle_confirm() {
    if (!current_user_can('edit_post', isset($_GET['post_id']) ? intval($_GET['post_id']) : 0)) {
        wp_die(__('Not allowed.', 'upi-timepay'));
    }
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    check_admin_referer('upi_timepay_confirm_' . $post_id);

    update_post_meta($post_id, 'payment_status', 'confirmed');
    update_post_meta($post_id, 'confirmed_at', time());

    $redirect = add_query_arg(array(
        'post_type' => 'upi_payment',
        'upi_timepay_notice' => 'confirmed'
    ), admin_url('edit.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_upi_timepay_confirm', 'upi_timepay_handle_confirm');

function upi_timepay_admin_notices() {
    if (isset($_GET['upi_timepay_notice']) && $_GET['upi_timepay_notice'] === 'confirmed') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Payment confirmed.', 'upi-timepay') . '</p></div>';
    }
}
add_action('admin_notices', 'upi_timepay_admin_notices');
