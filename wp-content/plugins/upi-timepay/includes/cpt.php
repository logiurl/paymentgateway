<?php
if (!defined('ABSPATH')) { exit; }

function upi_timepay_register_cpt() {
    $labels = array(
        'name'               => __('UPI Payments', 'upi-timepay'),
        'singular_name'      => __('UPI Payment', 'upi-timepay'),
        'menu_name'          => __('UPI Payments', 'upi-timepay'),
        'name_admin_bar'     => __('UPI Payment', 'upi-timepay'),
        'add_new'            => __('Add New', 'upi-timepay'),
        'add_new_item'       => __('Add New UPI Payment', 'upi-timepay'),
        'new_item'           => __('New UPI Payment', 'upi-timepay'),
        'edit_item'          => __('Edit UPI Payment', 'upi-timepay'),
        'view_item'          => __('View UPI Payment', 'upi-timepay'),
        'all_items'          => __('All UPI Payments', 'upi-timepay'),
        'search_items'       => __('Search UPI Payments', 'upi-timepay'),
        'not_found'          => __('No payments found.', 'upi-timepay'),
        'not_found_in_trash' => __('No payments found in Trash.', 'upi-timepay')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'exclude_from_search'=> true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-tickets',
        'supports'           => array('title'),
    );

    register_post_type('upi_payment', $args);
}
add_action('init', 'upi_timepay_register_cpt');
