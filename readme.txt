=== Wallet Login ===
Contributors:       spntn
Tags:               wallet, web3, crypto, login, metamask
Requires at least:  6.0
Tested up to:       6.9
Requires PHP:       7.4
Stable tag:         1.3.0
License:            GPL-2.0-or-later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Let users sign in to WordPress with their crypto wallet (MetaMask / WalletConnect). No password needed. 100% free — no subscription, no per-login fee.

== Description ==

Wallet Login replaces the traditional username/password login with a crypto wallet signature flow.

**This plugin is completely free.** No subscription plans, no per-login charges, no usage limits. The hosted backend service is provided free of charge by spntn.

**How it works:**

1. User clicks **Login with Wallet**
2. Wallet connects (MetaMask or any EVM-compatible wallet)
3. User signs a nonce message — no gas, no transaction
4. Signature is verified by the NFT SaaS backend
5. WordPress user session is created (user is auto-created on first login if enabled)

**Features:**

* One-click wallet connect via MetaMask (or any `window.ethereum` provider)
* Single-use, time-limited nonce — replay attack protection
* Auto-creates WordPress users for new wallets (optional)
* Configurable default role for new users
* Injects button into the WordPress login page automatically
* `[wallet_login]` shortcode for embedding on any page
* Simple admin settings page (Settings → Wallet Login)
* Configurable redirect after login

**Requirements:**

* An active NFT SaaS backend (self-hosted or Railway)
* An NFT SaaS API key (Dashboard → API Keys)

== External services ==

The Wallet Login plugin interacts with several external services:

- **NFT SaaS Backend API**
  - URL: https://nft-saas-production.up.railway.app
  - Purpose: Handles wallet authentication, nonce generation, signature verification, and user mapping. All plugin features rely on this API for core operations.

- **Polygon Mainnet**
  - Purpose: Wallet verification and NFT ownership checks are performed on Polygon Mainnet via smart contracts. The plugin interacts with Polygon using ethers.js.

- **Explorer Links**
  - Purpose: Plugin provides links to Polygon block explorers (e.g., Polygonscan) for transaction and wallet verification.

API keys and sensitive credentials are stored server-side and never exposed to frontend users.

== Installation ==

1. Upload the `wallet-login` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **Settings → Wallet Login** and enter:
   - Your backend URL (e.g. `https://nft-saas-production.up.railway.app`)
   - Your API key
4. Enable the plugin and save

== Frequently Asked Questions ==

= Does signing cost gas? =
No. `personal_sign` is an off-chain message signature. It costs nothing.

= What wallet does it support? =
Any EVM-compatible wallet that injects `window.ethereum` (MetaMask, Coinbase Wallet, Rabby, etc.). WalletConnect support can be added by configuring a WalletConnect provider before calling `eth_requestAccounts`.

= What happens if a wallet has never logged in before? =
A new WordPress user is created automatically (if auto-create is enabled in settings). The wallet address is stored as user meta.

= Can I disable auto user creation? =
Yes — uncheck "Auto-create users" in Settings → Wallet Login. Only wallets already linked to a WP account will be able to log in.

== Changelog ==

= 1.0.2 =
* Improved API key activation modal with clearer messaging.
* "100% free" indicator added to settings page.

= 1.0.0 =
* Initial release
* Nonce + signature flow via NFT SaaS backend
* Auto user creation with configurable role
* Shortcode `[wallet_login]`
* Admin settings page

== Screenshots ==

1. Login button injected into the WordPress login page.
2. MetaMask signature prompt during wallet authentication.
3. Plugin settings page (Settings → Wallet Login).

== Upgrade Notice ==

= 1.0.2 =
Minor UX improvements to the settings page.

= 1.0.0 =
Initial release.
