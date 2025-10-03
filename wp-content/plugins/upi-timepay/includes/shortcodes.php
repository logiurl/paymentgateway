<?php
if (!defined('ABSPATH')) { exit; }

function upi_timepay_shortcode($atts) {
    $opts = get_option('upi_timepay_settings', array());
    $upi_id = isset($opts['upi_id']) ? $opts['upi_id'] : '';
    $payee_name = isset($opts['payee_name']) ? $opts['payee_name'] : '';
    $default_expiry = isset($opts['default_expiry']) ? intval($opts['default_expiry']) : 900;
    $note_prefix = isset($opts['note_prefix']) ? $opts['note_prefix'] : 'Order';

    $a = shortcode_atts(array(
        'amount' => '',
        'ref'    => '',
        'expires'=> $default_expiry,
        'note'   => '',
    ), $atts, 'upi_timepay');

    $amount = trim($a['amount']);
    $ref = $a['ref'] !== '' ? sanitize_text_field($a['ref']) : ('UTP' . time() . wp_rand(1000, 9999));
    $expires_in = max(60, intval($a['expires']));
    $note = $a['note'] !== '' ? sanitize_text_field($a['note']) : ($note_prefix . ' ' . $ref);

    if (empty($upi_id) || empty($payee_name)) {
        return '<div class="upi-timepay-error">' . esc_html__('UPI is not configured. Please set UPI ID and Payee Name in settings.', 'upi-timepay') . '</div>';
    }

    $params = array(
        'pa' => $upi_id,
        'pn' => $payee_name,
        'cu' => 'INR',
        'tn' => $note,
        'tr' => $ref,
    );
    if ($amount !== '') { $params['am'] = $amount; }

    $upi_link = 'upi://pay?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $uniq = uniqid('utp_');
    $expires_at = time() + $expires_in;

    // Enqueue assets only when shortcode is present
    wp_enqueue_style('upi-timepay-style');
    wp_enqueue_script('qrcodejs');
    wp_enqueue_script('tesseractjs');
    wp_enqueue_script('upi-timepay-frontend');

    // Signature to prevent tampering
    $sig = upi_timepay_build_signature($ref, $expires_at, $amount, $upi_id, $payee_name, $note);

    ob_start();
    ?>
    <div class="upi-timepay" data-uniq="<?php echo esc_attr($uniq); ?>" data-link="<?php echo esc_attr($upi_link); ?>" data-expires-at="<?php echo esc_attr($expires_at); ?>" data-amount="<?php echo esc_attr($amount); ?>" data-ref="<?php echo esc_attr($ref); ?>" data-upi-id="<?php echo esc_attr($upi_id); ?>" data-payee-name="<?php echo esc_attr($payee_name); ?>" data-note="<?php echo esc_attr($note); ?>" data-sig="<?php echo esc_attr($sig); ?>">
        <div class="upi-timepay-grid">
            <div class="upi-timepay-left">
                <div class="upi-timepay-qr" id="qr-<?php echo esc_attr($uniq); ?>"></div>
                <a class="upi-timepay-btn" id="btn-<?php echo esc_attr($uniq); ?>" target="_self" rel="noopener nofollow">Pay with UPI</a>
                <div class="upi-timepay-countdown" id="cd-<?php echo esc_attr($uniq); ?>"></div>
            </div>
            <div class="upi-timepay-right">
                <label><?php echo esc_html__('Upload payment screenshot', 'upi-timepay'); ?></label>
                <input type="file" accept="image/*" id="shot-<?php echo esc_attr($uniq); ?>" />

                <div class="upi-timepay-ocr" id="ocr-<?php echo esc_attr($uniq); ?>"></div>

                <label><?php echo esc_html__('Transaction ID', 'upi-timepay'); ?></label>
                <input type="text" id="txn-<?php echo esc_attr($uniq); ?>" placeholder="e.g., 1234ABCD5678" />

                <button type="button" class="upi-timepay-submit" id="sub-<?php echo esc_attr($uniq); ?>"><?php echo esc_html__('Submit Proof', 'upi-timepay'); ?></button>
                <div class="upi-timepay-msg" id="msg-<?php echo esc_attr($uniq); ?>"></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('upi_timepay', 'upi_timepay_shortcode');

// Optional review list for team members
function upi_timepay_review_shortcode() {
    if (!current_user_can('edit_posts')) {
        return '<p>' . esc_html__('You do not have permission to view this page.', 'upi-timepay') . '</p>';
    }

    $q = new WP_Query(array(
        'post_type' => 'upi_payment',
        'posts_per_page' => 50,
        'meta_key' => 'payment_status',
        'meta_value' => 'pending',
    ));

    ob_start();
    echo '<div class="upi-timepay-review">';
    if ($q->have_posts()) {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>' . esc_html__('Ref', 'upi-timepay') . '</th><th>' . esc_html__('Amount', 'upi-timepay') . '</th><th>' . esc_html__('Txn ID', 'upi-timepay') . '</th><th>' . esc_html__('Screenshot', 'upi-timepay') . '</th><th>' . esc_html__('Actions', 'upi-timepay') . '</th></tr></thead><tbody>';
        while ($q->have_posts()) { $q->the_post();
            $id = get_the_ID();
            $ref = get_post_meta($id, 'upi_ref', true);
            $amount = get_post_meta($id, 'amount', true);
            $txn = get_post_meta($id, 'ocr_extracted_id', true);
            $attach_id = intval(get_post_meta($id, 'screenshot_id', true));
            $url = $attach_id ? wp_get_attachment_url($attach_id) : '';
            $confirm_url = wp_nonce_url(admin_url('admin-post.php?action=upi_timepay_confirm&post_id=' . $id), 'upi_timepay_confirm_' . $id);
            echo '<tr>';
            echo '<td>' . esc_html($ref) . '</td>';
            echo '<td>' . esc_html($amount) . '</td>';
            echo '<td>' . esc_html($txn) . '</td>';
            echo '<td>' . ($url ? '<a href="' . esc_url($url) . '" target="_blank">' . esc_html__('View', 'upi-timepay') . '</a>' : '-') . '</td>';
            echo '<td><a class="button button-primary" href="' . esc_url($confirm_url) . '">' . esc_html__('Confirm', 'upi-timepay') . '</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__('No pending payments.', 'upi-timepay') . '</p>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('upi_timepay_review', 'upi_timepay_review_shortcode');
