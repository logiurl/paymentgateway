<?php
if (!defined('ABSPATH')) { exit; }

function upi_timepay_register_settings() {
    register_setting('upi_timepay', 'upi_timepay_settings', array(
        'type' => 'array',
        'sanitize_callback' => 'upi_timepay_sanitize_settings',
        'default' => array(
            'upi_id' => '',
            'payee_name' => '',
            'default_expiry' => 900,
            'thank_you_url' => '',
            'note_prefix' => 'Order',
        )
    ));

    add_settings_section('upi_timepay_main', __('General', 'upi-timepay'), '__return_false', 'upi_timepay');

    add_settings_field('upi_id', __('UPI ID (pa)', 'upi-timepay'), 'upi_timepay_field_text', 'upi_timepay', 'upi_timepay_main', array('key' => 'upi_id', 'placeholder' => 'merchant@upi'));
    add_settings_field('payee_name', __('Payee Name (pn)', 'upi-timepay'), 'upi_timepay_field_text', 'upi_timepay', 'upi_timepay_main', array('key' => 'payee_name'));
    add_settings_field('default_expiry', __('Default Expiry (seconds)', 'upi-timepay'), 'upi_timepay_field_number', 'upi_timepay', 'upi_timepay_main', array('key' => 'default_expiry'));
    add_settings_field('thank_you_url', __('Thank You URL', 'upi-timepay'), 'upi_timepay_field_text', 'upi_timepay', 'upi_timepay_main', array('key' => 'thank_you_url', 'placeholder' => home_url('/thank-you')));
    add_settings_field('note_prefix', __('Note Prefix (tn)', 'upi-timepay'), 'upi_timepay_field_text', 'upi_timepay', 'upi_timepay_main', array('key' => 'note_prefix'));
}
add_action('admin_init', 'upi_timepay_register_settings');

function upi_timepay_sanitize_settings($input) {
    $out = array();
    $out['upi_id'] = isset($input['upi_id']) ? sanitize_text_field($input['upi_id']) : '';
    $out['payee_name'] = isset($input['payee_name']) ? sanitize_text_field($input['payee_name']) : '';
    $out['default_expiry'] = isset($input['default_expiry']) ? max(60, intval($input['default_expiry'])) : 900;
    $out['thank_you_url'] = isset($input['thank_you_url']) ? esc_url_raw($input['thank_you_url']) : '';
    $out['note_prefix'] = isset($input['note_prefix']) ? sanitize_text_field($input['note_prefix']) : 'Order';
    return $out;
}

function upi_timepay_settings_menu() {
    add_options_page(
        __('UPI TimePay', 'upi-timepay'),
        __('UPI TimePay', 'upi-timepay'),
        'manage_options',
        'upi_timepay',
        'upi_timepay_render_settings_page'
    );
}
add_action('admin_menu', 'upi_timepay_settings_menu');

function upi_timepay_field_text($args) {
    $opts = get_option('upi_timepay_settings', array());
    $key = $args['key'];
    $val = isset($opts[$key]) ? $opts[$key] : '';
    $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
    printf('<input type="text" name="upi_timepay_settings[%1$s]" value="%2$s" placeholder="%3$s" class="regular-text"/>', esc_attr($key), esc_attr($val), esc_attr($placeholder));
}

function upi_timepay_field_number($args) {
    $opts = get_option('upi_timepay_settings', array());
    $key = $args['key'];
    $val = isset($opts[$key]) ? intval($opts[$key]) : 900;
    printf('<input type="number" min="60" step="1" name="upi_timepay_settings[%1$s]" value="%2$s" class="small-text"/>', esc_attr($key), esc_attr($val));
}

function upi_timepay_render_settings_page() {
    if (!current_user_can('manage_options')) { return; }
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('UPI TimePay Settings', 'upi-timepay') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('upi_timepay');
    do_settings_sections('upi_timepay');
    submit_button();
    echo '</form>';
    echo '</div>';
}
