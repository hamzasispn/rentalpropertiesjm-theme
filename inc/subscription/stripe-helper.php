<?php
/**
 * Stripe Helper Functions
 * 
 * Common utility functions for Stripe API interactions
 */

/**
 * Make a call to Stripe API using cURL
 * 
 * @param string $method HTTP method (GET, POST, DELETE)
 * @param string $endpoint API endpoint (e.g., '/v1/customers')
 * @param array $params Request parameters
 * @param string $api_key Stripe API key
 * @return array Decoded JSON response
 */
function property_theme_stripe_api_call($method, $endpoint, $params, $api_key) {
    $url = 'https://api.stripe.com' . $endpoint;
    
    $ch = curl_init();
    
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $api_key . ':',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CUSTOMREQUEST => $method,
    ));

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        error_log('[PropertyTheme] Stripe cURL Error: ' . $curl_error);
        return array('error' => array('message' => 'Connection error: ' . $curl_error));
    }

    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('[PropertyTheme] Stripe JSON Decode Error: ' . json_last_error_msg() . ' | Response: ' . $response);
        return array('error' => array('message' => 'Invalid JSON response from Stripe'));
    }

    return $decoded ?: array('error' => array('message' => 'Empty response from Stripe'));
}

/**
 * Verify Stripe webhook signature
 * 
 * @param string $payload Raw request body
 * @param string $sig_header Stripe signature header
 * @param string $webhook_secret Webhook signing secret
 * @return bool True if signature is valid
 */
function property_theme_verify_stripe_webhook($payload, $sig_header, $webhook_secret) {
    if (!$webhook_secret || !$sig_header) {
        return false;
    }

    // Extract timestamp and signature from header
    $sig_parts = explode(',', $sig_header);
    $timestamp = '';
    $signature = '';

    foreach ($sig_parts as $part) {
        if (strpos($part, 't=') === 0) {
            $timestamp = substr($part, 2);
        } elseif (strpos($part, 'v1=') === 0) {
            $signature = substr($part, 3);
        }
    }

    if (!$timestamp || !$signature) {
        return false;
    }

    // Verify timestamp is not too old (5 minutes)
    if (time() - intval($timestamp) > 300) {
        error_log('[PropertyTheme] Stripe webhook timestamp too old: ' . $timestamp);
        return false;
    }

    // Compute expected signature
    $signed_content = $timestamp . '.' . $payload;
    $computed_signature = hash_hmac('sha256', $signed_content, $webhook_secret);

    return hash_equals($computed_signature, $signature);
}

/**
 * Format price for display
 * 
 * @param int $amount Amount in cents
 * @param string $currency Currency code
 * @return string Formatted price
 */
function property_theme_format_price($amount, $currency = 'USD') {
    return number_format($amount / 100, 2) . ' ' . strtoupper($currency);
}

/**
 * Wrap content in branded HTML email template
 *
 * @param string $title Email heading shown in hero
 * @param string $body_html Inner HTML body content
 * @param array $cta Optional ['text' => 'Click', 'url' => 'https://...']
 * @param string $accent Hex color (default brand blue)
 * @return string Full HTML email
 */
function property_theme_email_template($title, $body_html, $cta = array(), $accent = '#2563eb') {
    $site_name = esc_html(get_bloginfo('name'));
    $site_url  = esc_url(home_url('/'));
    $year      = date('Y');
    $logo      = function_exists('get_custom_logo') ? get_theme_mod('custom_logo') : 0;
    $logo_html = $logo
        ? wp_get_attachment_image($logo, array(160, 48), false, array('style' => 'max-height:48px;width:auto;'))
        : '<div style="font-size:22px;font-weight:700;color:#ffffff;letter-spacing:.3px;">' . $site_name . '</div>';

    $cta_html = '';
    if (!empty($cta['url']) && !empty($cta['text'])) {
        $cta_html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:28px 0;">
            <tr><td bgcolor="' . esc_attr($accent) . '" style="border-radius:8px;">
                <a href="' . esc_url($cta['url']) . '" target="_blank" style="display:inline-block;padding:14px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">'
                . esc_html($cta['text']) . '</a>
            </td></tr>
        </table>';
    }

    return '<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html($title) . '</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f5f9;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,.06);">
            <tr><td style="background:' . esc_attr($accent) . ';padding:28px 32px;">' . $logo_html . '</td></tr>
            <tr><td style="padding:36px 32px 8px 32px;">
                <h1 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;color:#0f172a;">' . esc_html($title) . '</h1>
                <div style="font-size:15px;line-height:1.65;color:#334155;">' . $body_html . '</div>
                ' . $cta_html . '
            </td></tr>
            <tr><td style="padding:18px 32px 32px 32px;font-size:13px;color:#64748b;line-height:1.55;">
                Need help? Reply to this email and our team will get back to you.
            </td></tr>
            <tr><td style="background:#0f172a;color:#94a3b8;padding:20px 32px;font-size:12px;text-align:center;">
                &copy; ' . $year . ' <a href="' . $site_url . '" style="color:#cbd5e1;text-decoration:none;">' . $site_name . '</a>. All rights reserved.
            </td></tr>
        </table>
    </td></tr>
</table>
</body></html>';
}

/**
 * Send a branded HTML email
 */
function property_theme_send_html_email($to, $subject, $title, $body_html, $cta = array(), $accent = '#2563eb') {
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $html = property_theme_email_template($title, $body_html, $cta, $accent);
    return wp_mail($to, $subject, $html, $headers);
}

/**
 * Render a 2-column key/value table for email bodies
 */
function property_theme_email_kv_table($rows) {
    $html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;margin:8px 0 4px 0;">';
    foreach ($rows as $label => $value) {
        $html .= '<tr>'
            . '<td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;width:42%;">' . esc_html($label) . '</td>'
            . '<td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:14px;font-weight:600;text-align:right;">' . wp_kses_post($value) . '</td>'
            . '</tr>';
    }
    $html .= '</table>';
    return $html;
}
