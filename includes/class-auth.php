<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles communication with the NFT SaaS backend for nonce + signature verification.
 */
class WL_Auth {

    /**
     * Fetch a fresh nonce from the backend for the given wallet address.
     *
     * @return string|WP_Error  The nonce string, or WP_Error on failure.
     */
    public static function get_nonce( string $address ) {
        $options = get_option( WL_OPTION_KEY, [] );
        $api_url = trailingslashit( $options['api_url'] ?? '' );
        $api_key = $options['api_key'] ?? '';

        if ( ! $api_url || ! $api_key ) {
            return new WP_Error( 'not_configured', 'Wallet Login plugin is not configured.' );
        }

        $url      = $api_url . 'api/v2/wallet-login/nonce?address=' . rawurlencode( $address );
        $response = wp_remote_get( $url, [
            'headers' => [ 'x-api-key' => $api_key ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'request_failed', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $msg = $body['error'] ?? 'Backend error.';
            return new WP_Error( 'backend_error', $msg );
        }

        $nonce = $body['nonce'] ?? '';
        if ( ! $nonce ) {
            return new WP_Error( 'no_nonce', 'No nonce returned from backend.' );
        }

        return $nonce;
    }

    /**
     * Verify a wallet signature against the backend.
     *
     * @return true|WP_Error
     */
    public static function verify( string $address, string $signature, string $nonce ) {
        $options = get_option( WL_OPTION_KEY, [] );
        $api_url = trailingslashit( $options['api_url'] ?? '' );
        $api_key = $options['api_key'] ?? '';

        if ( ! $api_url || ! $api_key ) {
            return new WP_Error( 'not_configured', 'Wallet Login plugin is not configured.' );
        }

        $response = wp_remote_post( $api_url . 'api/v2/wallet-login/verify', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key'    => $api_key,
            ],
            'body'    => wp_json_encode( compact( 'address', 'signature', 'nonce' ) ),
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'request_failed', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $msg = $body['error'] ?? 'Signature verification failed.';
            return new WP_Error( 'verify_failed', $msg );
        }

        if ( empty( $body['verified'] ) ) {
            return new WP_Error( 'not_verified', 'Signature not verified.' );
        }

        return true;
    }
}
