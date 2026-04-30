<?php
/**
 * Plugin Name:       Wallet Login
 * Plugin URI:        https://github.com/spntnhub/Wallet-Login-for-WordPress
 * Description:       Let users log in to WordPress using their crypto wallet (MetaMask / WalletConnect). Powered by NFT SaaS backend.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            spntn
 * Author URI:        https://spntn.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wallet-login
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WL_VERSION',    '1.3.0' );
define( 'WL_OPTION_KEY', 'wl_wallet_login_options' );
define( 'WL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WL_PLUGIN_DIR . 'includes/class-user.php';
require_once WL_PLUGIN_DIR . 'includes/class-auth.php';
require_once WL_PLUGIN_DIR . 'includes/class-admin.php';

// ── Boot ──────────────────────────────────────────────────────────────────────
add_action( 'init',           'wl_init' );
add_action( 'login_form',     'wl_inject_login_button' );
add_action( 'wp_enqueue_scripts', 'wl_enqueue_assets' );
add_action( 'login_enqueue_scripts', 'wl_enqueue' );
add_shortcode( 'wallet_login', 'wl_shortcode' );

function wl_init() {
    // AJAX handlers (logged-in and logged-out users)
    add_action( 'wp_ajax_nopriv_wl_nonce',  'wl_ajax_nonce' );
    add_action( 'wp_ajax_nopriv_wl_verify', 'wl_ajax_verify' );
    // Allow already-logged-in users to re-link a wallet
    add_action( 'wp_ajax_wl_nonce',  'wl_ajax_nonce' );
    add_action( 'wp_ajax_wl_verify', 'wl_ajax_verify' );
}

// ── Enqueue assets ────────────────────────────────────────────────────────────
function wl_enqueue_assets() {
    wp_enqueue_style(
        'wl-wallet-login',
        WL_PLUGIN_URL . 'assets/wallet-login.css',
        [],
        WL_VERSION
    );
    wp_enqueue_script(
        'wl-ethers',
        WL_PLUGIN_URL . 'assets/ethers.umd.min.js',
        [],
        '6.13.2',
        true
    );
    wp_enqueue_script(
        'wl-wallet-login',
        WL_PLUGIN_URL . 'assets/wallet-login.js',
        [ 'wl-ethers' ],
        WL_VERSION,
        true
    );
}

// login_enqueue_scripts fires on wp-login.php — reuse the same assets.
function wl_enqueue() {
    wl_enqueue_assets();
}

// ── Login page button injection ───────────────────────────────────────────────
function wl_inject_login_button() {
    $options = get_option( WL_OPTION_KEY, [] );
    if ( empty( $options['enabled'] ) ) return;
    echo wp_kses( wl_button_html(), wl_allowed_html() );
}

// ── Shortcode ─────────────────────────────────────────────────────────────────
function wl_shortcode( $atts = [] ) {
    $options = get_option( WL_OPTION_KEY, [] );
    if ( empty( $options['enabled'] ) ) return '';
    return wp_kses( wl_button_html(), wl_allowed_html() );
}

function wl_allowed_html(): array {
    return [
        'div'    => [ 'class' => [], 'id' => [] ],
        'button' => [ 'type' => [], 'id' => [], 'class' => [] ],
        'svg'    => [ 'width' => [], 'height' => [], 'viewbox' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'aria-hidden' => [] ],
        'path'   => [ 'd' => [] ],
        'circle' => [ 'cx' => [], 'cy' => [], 'r' => [] ],
        'p'      => [ 'id' => [], 'class' => [], 'aria-live' => [] ],
    ];
}

function wl_button_html(): string {
    $label = esc_html( get_option( WL_OPTION_KEY, [] )['button_label'] ?? 'Login with Wallet' );
    ob_start();
    ?>
    <div class="wl-wrap">
      <button type="button" id="wl-connect-btn" class="wl-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><circle cx="17" cy="12" r="1"/></svg>
        <?php echo esc_html( $label ); ?>
      </button>
      <p id="wl-status" class="wl-status" aria-live="polite"></p>
    </div>
    <?php
    return ob_get_clean();
}

// ── AJAX: get nonce ───────────────────────────────────────────────────────────
function wl_ajax_nonce() {
    check_ajax_referer( 'wl_nonce', 'nonce' );
    $address = sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) );
    if ( ! $address ) wp_send_json_error( [ 'message' => 'Address required.' ] );

    $result = WL_Auth::get_nonce( $address );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => $result->get_error_message() ] );
    }
    wp_send_json_success( [ 'nonce' => $result ] );
}

// ── AJAX: verify signature & log in ──────────────────────────────────────────
function wl_ajax_verify() {
    check_ajax_referer( 'wl_nonce', 'nonce' );
    $address   = sanitize_text_field( wp_unslash( $_POST['address']   ?? '' ) );
    $signature = sanitize_text_field( wp_unslash( $_POST['signature'] ?? '' ) );
    $nonce     = sanitize_text_field( wp_unslash( $_POST['wl_nonce']  ?? '' ) );

    if ( ! $address || ! $signature || ! $nonce ) {
        wp_send_json_error( [ 'message' => 'Missing parameters.' ] );
    }

    $verified = WL_Auth::verify( $address, $signature, $nonce );
    if ( is_wp_error( $verified ) ) {
        wp_send_json_error( [ 'message' => $verified->get_error_message() ] );
    }

    // Find or create WordPress user
    $user_id = WL_User::get_or_create( $address );
    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
    }

    wp_set_auth_cookie( $user_id, true );
    wp_send_json_success( [ 'message' => 'Logged in.' ] );
}
