<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin settings page for Wallet Login.
 */
class WL_Admin {
    public static function boot(): void {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_menu(): void {
        add_options_page(
            'Wallet Login',
            'Wallet Login',
            'manage_options',
            'wallet-login',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function register_settings(): void {
        register_setting( 'wl_settings', WL_OPTION_KEY, [
            'sanitize_callback' => [ __CLASS__, 'sanitize' ],
        ] );
    }

    public static function sanitize( $input ): array {
        return [
            'enabled'      => ! empty( $input['enabled'] ),
            'api_url'      => esc_url_raw( $input['api_url'] ?? '' ),
            'api_key'      => sanitize_text_field( $input['api_key'] ?? '' ),
            'auto_create'  => ! empty( $input['auto_create'] ),
            'default_role' => sanitize_text_field( $input['default_role'] ?? 'subscriber' ),
            'button_label' => sanitize_text_field( $input['button_label'] ?? 'Login with Wallet' ),
            'redirect_url' => esc_url_raw( $input['redirect_url'] ?? home_url() ),
        ];
    }

    public static function render_page(): void {
        $opts = get_option( WL_OPTION_KEY, [] );
        $roles = wp_roles()->get_names();
        ?>
        <div class="wrap">
          <h1>Wallet Login — Settings</h1>
          <form method="post" action="options.php">
            <?php settings_fields( 'wl_settings' ); ?>
            <table class="form-table" role="presentation">

              <tr>
                <th scope="row">Enable Plugin</th>
                <td>
                  <label>
                    <input type="checkbox" name="<?= WL_OPTION_KEY ?>[enabled]" value="1" <?php checked( ! empty( $opts['enabled'] ) ) ?>>
                    Show wallet login button
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-api-url">Backend URL</label></th>
                <td>
                  <input id="wl-api-url" type="url" name="<?= WL_OPTION_KEY ?>[api_url]"
                    value="<?= esc_attr( $opts['api_url'] ?? '' ) ?>" class="regular-text"
                    placeholder="https://nft-saas-production.up.railway.app">
                  <p class="description">URL of your NFT SaaS backend (no trailing slash needed).</p>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-api-key">API Key</label></th>
                <td>
                  <input id="wl-api-key" type="password" name="<?= WL_OPTION_KEY ?>[api_key]"
                    value="<?= esc_attr( $opts['api_key'] ?? '' ) ?>" class="regular-text" autocomplete="off">
                  <p class="description">Your NFT SaaS API key (Dashboard → API Keys).</p>
                </td>
              </tr>

              <tr>
                <th scope="row">Auto-create users</th>
                <td>
                  <label>
                    <input type="checkbox" name="<?= WL_OPTION_KEY ?>[auto_create]" value="1" <?php checked( $opts['auto_create'] ?? true ) ?>>
                    Create a new WordPress user when an unknown wallet signs in
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-role">Default Role</label></th>
                <td>
                  <select id="wl-role" name="<?= WL_OPTION_KEY ?>[default_role]">
                    <?php foreach ( $roles as $slug => $name ): ?>
                      <option value="<?= esc_attr( $slug ) ?>" <?php selected( ( $opts['default_role'] ?? 'subscriber' ), $slug ) ?>><?= esc_html( $name ) ?></option>
                    <?php endforeach ?>
                  </select>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-label">Button Label</label></th>
                <td>
                  <input id="wl-label" type="text" name="<?= WL_OPTION_KEY ?>[button_label]"
                    value="<?= esc_attr( $opts['button_label'] ?? 'Login with Wallet' ) ?>" class="regular-text">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-redirect">Redirect After Login</label></th>
                <td>
                  <input id="wl-redirect" type="url" name="<?= WL_OPTION_KEY ?>[redirect_url]"
                    value="<?= esc_attr( $opts['redirect_url'] ?? home_url() ) ?>" class="regular-text"
                    placeholder="<?= esc_attr( home_url() ) ?>">
                </td>
              </tr>

            </table>
            <?php submit_button() ?>
          </form>

          <hr>
          <h2>Shortcode</h2>
          <p>Add <code>[wallet_login]</code> to any page or widget to display the login button.</p>
        </div>
        <?php
    }
}

WL_Admin::boot();
