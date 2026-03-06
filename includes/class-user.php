<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Maps wallet addresses to WordPress users.
 * Wallet address is stored as usermeta with key `wallet_address`.
 */
class WL_User {

    const META_KEY = 'wallet_address';

    /**
     * Find an existing WP user by wallet address, or create one.
     *
     * @return int|WP_Error  WordPress user ID on success.
     */
    public static function get_or_create( string $address ) {
        $address = strtolower( trim( $address ) );

        // Look up existing user
        $users = get_users( [
            'meta_key'   => self::META_KEY,
            'meta_value' => $address,
            'number'     => 1,
            'fields'     => 'ids',
        ] );

        if ( ! empty( $users ) ) {
            return (int) $users[0];
        }

        // Check whether auto-creation is enabled
        $options = get_option( WL_OPTION_KEY, [] );
        if ( isset( $options['auto_create'] ) && ! $options['auto_create'] ) {
            return new WP_Error( 'no_user', 'No WordPress account found for this wallet.' );
        }

        // Create a new WordPress user
        $role     = $options['default_role'] ?? 'subscriber';
        $username = 'wallet_' . substr( $address, 2, 8 ); // e.g. wallet_a91f3e2b
        $email    = $address . '@wallet.local';            // placeholder — not a real address

        // Avoid duplicate usernames
        if ( username_exists( $username ) ) {
            $username .= '_' . substr( $address, -4 );
        }

        $user_id = wp_insert_user( [
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => wp_generate_password( 32 ),
            'role'       => $role,
            'display_name' => substr( $address, 0, 6 ) . '...' . substr( $address, -4 ),
        ] );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        update_user_meta( $user_id, self::META_KEY, $address );

        return $user_id;
    }
}
