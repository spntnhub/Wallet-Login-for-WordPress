# Wallet Login for WordPress

Let users sign in to your WordPress site using their crypto wallet — no password needed.

📦 **Download:** [`wallet-login-wp.zip`](https://github.com/spntnhub/Wallet-Login-for-WordPress/releases/latest/download/wallet-login-wp.zip)  
🌍 **Repo:** [github.com/spntnhub/Wallet-Login-for-WordPress](https://github.com/spntnhub/Wallet-Login-for-WordPress)  
🔗 **Backend:** [github.com/spntnhub/nft-saas](https://github.com/spntnhub/nft-saas)

---

## How It Works

```
User clicks "Login with Wallet"
        ↓
Wallet connects (MetaMask / any EVM wallet)
        ↓
Backend issues a single-use nonce (5 min TTL)
        ↓
User signs the message — no gas, no transaction
        ↓
Backend verifies the EIP-191 signature (ethers.js)
        ↓
WordPress session created (user auto-created if new)
```

Signature verification happens **server-side** via the NFT SaaS backend. The API key is never exposed to the browser.

---

## Quick Start

1. Download `wallet-login-wp.zip` and upload via **Plugins → Add New → Upload Plugin**
2. Activate the plugin
3. Go to **Settings → Wallet Login** and enter:
   - **Backend URL** — your NFT SaaS backend (e.g. `https://nft-saas-production.up.railway.app`)
   - **API Key** — from your NFT SaaS dashboard (Dashboard → API Keys)
4. Check **Enable Plugin** and save
5. The login button now appears on the WordPress login page automatically

---

## Shortcode

Embed the login button anywhere on your site:

```
[wallet_login]
```

---

## Features

| Feature | Detail |
|---|---|
| Wallet connect | MetaMask and any `window.ethereum` provider |
| Nonce security | Single-use, 5-minute TTL, stored in Redis |
| Replay protection | Nonce deleted on first use |
| Auto user creation | Creates a WP user on first wallet login (optional) |
| Configurable role | Set default role for new wallet users |
| Login page injection | Button added to `wp-login.php` automatically |
| Shortcode | `[wallet_login]` for any page or widget |
| Redirect | Configurable redirect URL after login |
| Admin settings | Settings → Wallet Login |

---

## Settings

| Setting | Default | Description |
|---|---|---|
| Enable Plugin | off | Show the login button |
| Backend URL | — | NFT SaaS backend URL |
| API Key | — | Your NFT SaaS API key |
| Auto-create users | on | Create WP user for unknown wallets |
| Default Role | subscriber | Role assigned to new wallet users |
| Button Label | Login with Wallet | Text on the button |
| Redirect After Login | Home page | Where to send the user after sign-in |

---

## Security

- Nonce is single-use and expires after 5 minutes (Redis TTL)
- Signature is verified server-side with `ethers.verifyMessage` — the browser cannot forge it
- API key is stored in WP options and only used in server-side PHP requests — never sent to the browser
- All AJAX endpoints are protected with `check_ajax_referer`

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- An active [NFT SaaS backend](https://github.com/spntnhub/nft-saas) (self-hosted or Railway)
- An NFT SaaS API key

---

## File Structure

```
wallet-login/
├── wallet-login.php          # Plugin bootstrap, hooks, AJAX handlers
├── includes/
│   ├── class-auth.php        # Backend nonce + verify calls
│   ├── class-user.php        # Find or create WP user by wallet address
│   └── class-admin.php       # Settings → Wallet Login admin page
├── assets/
│   ├── wallet-login.js       # ethers.js connect + personal_sign flow
│   └── wallet-login.css      # Button and status styles
└── readme.txt                # WordPress Plugin Directory readme
```

---

## Backend Endpoints Used

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v2/wallet-login/nonce` | Issue a single-use nonce for an address |
| POST | `/api/v2/wallet-login/verify` | Verify signature, return `{ verified: true }` |

Both endpoints require `x-api-key` header authentication.

---

## Future Extensions

- Token-gated roles (NFT holder → editor/author)
- SIWE (Sign-In with Ethereum) standard
- WalletConnect v2 modal
- Gutenberg block

---

## License

GPL-2.0-or-later — see [LICENSE](LICENSE)

Backend & infrastructure: Proprietary ([github.com/spntnhub/nft-saas](https://github.com/spntnhub/nft-saas))
