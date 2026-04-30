<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin settings page for Wallet Login.
 */
class WL_Admin {
    public static function boot(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_init',            [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
    }

    public static function enqueue_scripts( string $hook ): void {
        if ( 'settings_page_wallet-login' !== $hook ) return;
        $opts = get_option( WL_OPTION_KEY, [] );
        $default_api_url = 'https://nft-saas-production.up.railway.app';
        wp_register_script( 'wl-admin', false, [ 'jquery' ], WL_VERSION, true );
        wp_enqueue_script( 'wl-admin' );
        wp_localize_script( 'wl-admin', 'wlAdminData', [
            'defaultApiUrl' => $default_api_url,
            'currentApiUrl' => $opts['api_url'] ?? $default_api_url,
        ] );
        wp_add_inline_script( 'wl-admin', self::admin_inline_js() );
    }

    private static function admin_inline_js(): string {
        return <<<'JS'
(function($){
  var apiUrl = function() {
    return $('#wl-api-url').val().trim().replace(/\/$/, '')
      || wlAdminData.defaultApiUrl;
  };

  // Open modal
  $(document).on('click', '#wl-get-key-link', function(e){
    e.preventDefault();
    $('#wl-reg-feedback').hide();
    $('#wl-reg-email').val('');
    $('#wl-modal, #wl-modal-overlay').fadeIn(150);
    setTimeout(function(){ $('#wl-reg-email').focus(); }, 160);
  });

  // Close modal
  $(document).on('click', '#wl-modal-cancel, #wl-modal-overlay', function(){
    $('#wl-modal, #wl-modal-overlay').fadeOut(150);
  });

  // Submit
  $(document).on('click', '#wl-modal-submit', function(){
    var email = $('#wl-reg-email').val().trim();
    if (!email) {
      feedback('error', 'Please enter your email address.');
      return;
    }
    var $btn = $(this).prop('disabled', true).text('Connecting...');
    clearFeedback();

    $.ajax({
      url:         apiUrl() + '/api/auth/activate',
      method:      'POST',
      contentType: 'application/json',
      data:        JSON.stringify({ email: email, siteUrl: window.location.origin }),
      timeout:     15000,
    })
    .done(function(res){
      if (res.apiKey) {
        $('#wl-api-key').val(res.apiKey).attr('type', 'text');
        feedback('success', 'API key generated! <strong>Click Save Settings below to save it.</strong>');
        $('input[type="submit"]').first().css('background','#00a32a').focus();
        $('#wl-get-key-link').hide();
        setTimeout(function(){ $('#wl-modal, #wl-modal-overlay').fadeOut(200); }, 1800);
      } else {
        feedback('error', 'Unexpected response. Please try again.');
      }
    })
    .fail(function(xhr){
      var msg = (xhr.responseJSON && xhr.responseJSON.error)
        ? xhr.responseJSON.error
        : 'Could not connect to backend. Check the Backend URL field.';
      feedback('error', msg);
    })
    .always(function(){
      $btn.prop('disabled', false).text('Get API Key');
    });
  });

  function feedback(type, msg) {
    var colors = { success: { bg:'#d1fae5', border:'#6ee7b7', text:'#065f46' },
                   error:   { bg:'#fee2e2', border:'#fca5a5', text:'#991b1b' } };
    var c = colors[type];
    $('#wl-reg-feedback')
      .css({ background: c.bg, border: '1px solid ' + c.border, color: c.text })
      .html(msg).show();
  }
  function clearFeedback() { $('#wl-reg-feedback').hide(); }
})(jQuery);
JS;
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
        $opts            = get_option( WL_OPTION_KEY, [] );
        $roles           = wp_roles()->get_names();
        $default_api_url = 'https://nft-saas-production.up.railway.app';
        ?>
        <div class="wrap">
          <h1>Wallet Login &mdash; Settings</h1>
          <form method="post" action="options.php">
            <?php settings_fields( 'wl_settings' ); ?>
            <table class="form-table" role="presentation">

              <tr>
                <th scope="row">Enable Plugin</th>
                <td>
                  <label>
                    <input type="checkbox" name="<?php echo esc_attr( WL_OPTION_KEY ); ?>[enabled]" value="1" <?php checked( ! empty( $opts['enabled'] ) ); ?>>
                    Show wallet login button
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-api-url">Backend URL</label></th>
                <td>
                  <input id="wl-api-url" type="url" name="<?php echo esc_attr( WL_OPTION_KEY ); ?>[api_url]"
                    value="<?php echo esc_attr( $opts['api_url'] ?? $default_api_url ); ?>" class="regular-text"
                    placeholder="<?php echo esc_attr( $default_api_url ); ?>">
                  <p class="description">URL of your NFT SaaS backend. Leave as-is if you are using the hosted service.</p>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-api-key">API Key</label></th>
                <td>
                  <input id="wl-api-key" type="password" name="<?php echo esc_attr( WL_OPTION_KEY ); ?>[api_key]"
                    value="<?php echo esc_attr( $opts['api_key'] ?? '' ); ?>" class="regular-text" autocomplete="off">
                  <p class="description">
                    Your NFT SaaS API key.
                    <?php if ( empty( $opts['api_key'] ) ) : ?>
                      <br><a href="#" id="wl-get-key-link" style="font-weight:600;">Don't have one? Get your free API key here.</a>
                    <?php endif; ?>
                  </p>
                </td>
              </tr>

              <tr>
                <th scope="row">Auto-create users</th>
                <td>
                  <label>
                    <input type="checkbox" name="<?php echo esc_attr( WL_OPTION_KEY ); ?>[auto_create]" value="1" <?php checked( $opts['auto_create'] ?? true ); ?>>
                    Create a new WordPress user when an unknown wallet signs in
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-role">Default Role</label></th>
                <td>
                  <select id="wl-role" name="<?php echo esc_attr( WL_OPTION_KEY ); ?>[default_role]">
                    <?php foreach ( $roles as $slug => $name ) : ?>
                      <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( ( $opts['default_role'] ?? 'subscriber' ), $slug ); ?>><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-label">Button Label</label></th>
                <td>
                  <input id="wl-label" type="text" name="<?php echo esc_attr( WL_OPTION_KEY ); ?>[button_label]"
                    value="<?php echo esc_attr( $opts['button_label'] ?? 'Login with Wallet' ); ?>" class="regular-text">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="wl-redirect">Redirect After Login</label></th>
                <td>
                  <input id="wl-redirect" type="url" name="<?php echo esc_attr( WL_OPTION_KEY ); ?>[redirect_url]"
                    value="<?php echo esc_attr( $opts['redirect_url'] ?? home_url() ); ?>" class="regular-text"
                    placeholder="<?php echo esc_attr( home_url() ); ?>">
                </td>
              </tr>

            </table>
            <?php submit_button(); ?>
          </form>

          <hr>
          <h2>Shortcode</h2>
          <p>Add <code>[wallet_login]</code> to any page or widget to display the login button.</p>
        </div>

        <!-- Get API Key Modal -->
        <div id="wl-modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;"></div>
        <div id="wl-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:28px 32px;border-radius:8px;z-index:10000;box-shadow:0 10px 30px rgba(0,0,0,.2);width:420px;max-width:92%;">
          <h2 style="margin-top:0;">Get your free API Key</h2>
          <p style="color:#555;">Enter your email address. Your API key will be generated instantly and sent to your inbox from <strong>info@spntn.com</strong>.</p>
          <div id="wl-reg-feedback" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:5px;font-size:.875rem;"></div>
          <p>
            <label style="font-weight:600;display:block;margin-bottom:4px;">Email Address</label>
            <input type="email" id="wl-reg-email" class="large-text" placeholder="you@example.com" style="width:100%;">
          </p>
          <div style="text-align:right;margin-top:18px;display:flex;gap:8px;justify-content:flex-end;">
            <button type="button" class="button" id="wl-modal-cancel">Cancel</button>
            <button type="button" class="button button-primary" id="wl-modal-submit">Get API Key</button>
          </div>
        </div>
        <?php
    }
}

WL_Admin::boot();
