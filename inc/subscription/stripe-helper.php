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
