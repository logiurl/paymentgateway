<?php
if (!defined('ABSPATH')) { exit; }

function upi_timepay_build_signature($ref, $expires_at, $amount, $upi_id, $payee_name, $note) {
    $parts = array((string)$ref, (string)intval($expires_at), (string)$amount, (string)$upi_id, (string)$payee_name, (string)$note);
    $data = implode('|', $parts);
    $key = wp_salt('auth');
    return hash_hmac('sha256', $data, $key);
}
