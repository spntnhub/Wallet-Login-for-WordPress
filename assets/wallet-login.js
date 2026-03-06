/* Wallet Login for WordPress — wallet-login.js
 * Requires: ethers.js (UMD, loaded by the plugin)
 * License:  GPL-2.0-or-later
 */
(function () {
  'use strict';

  // ── DOM ready ───────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    const btn    = document.getElementById('wl-connect-btn');
    const status = document.getElementById('wl-status');
    if (!btn) return;

    btn.addEventListener('click', handleLogin);
  });

  // ── Main flow ────────────────────────────────────────────────────────────────
  async function handleLogin() {
    const btn    = document.getElementById('wl-connect-btn');
    const status = document.getElementById('wl-status');

    try {
      setStatus(status, '');
      btn.disabled = true;
      btn.textContent = 'Connecting…';

      // 1. Connect wallet
      if (!window.ethereum) {
        throw new Error('No wallet detected. Please install MetaMask.');
      }

      const provider = new ethers.BrowserProvider(window.ethereum);
      await provider.send('eth_requestAccounts', []);
      const signer  = await provider.getSigner();
      const address = await signer.getAddress();

      setStatus(status, 'Wallet connected. Requesting nonce…');

      // 2. Get nonce from backend (via WP AJAX)
      const nonceData = await ajaxPost('wl_nonce', { address });
      if (!nonceData.success) {
        throw new Error(nonceData.data?.message || 'Failed to get nonce.');
      }
      const nonce = nonceData.data.nonce;

      setStatus(status, 'Please sign the message in your wallet…');
      btn.textContent = 'Sign in wallet…';

      // 3. Sign the message
      const message = buildMessage(nonce);
      const signature = await signer.signMessage(message);

      setStatus(status, 'Verifying…');

      // 4. Verify via backend (via WP AJAX)
      const verifyData = await ajaxPost('wl_verify', {
        address,
        signature,
        wl_nonce: nonce,
      });

      if (!verifyData.success) {
        throw new Error(verifyData.data?.message || 'Verification failed.');
      }

      setStatus(status, '✓ Signed in! Redirecting…', 'success');
      setTimeout(() => {
        window.location.href = WL.redirect || window.location.href;
      }, 800);

    } catch (err) {
      const msg = err?.message || 'Unknown error.';
      setStatus(status, '✗ ' + msg, 'error');
      const btn2 = document.getElementById('wl-connect-btn');
      if (btn2) {
        btn2.disabled    = false;
        btn2.textContent = WL.label || 'Login with Wallet';
      }
    }
  }

  // ── Helpers ──────────────────────────────────────────────────────────────────

  function buildMessage(nonce) {
    return 'Sign in to WordPress\n\nNonce: ' + nonce + '\n\nThis request will not trigger a blockchain transaction or cost any gas.';
  }

  async function ajaxPost(action, params) {
    const form = new FormData();
    form.append('action', action);
    form.append('nonce',  WL.nonce);
    for (const [k, v] of Object.entries(params)) {
      form.append(k, v);
    }
    const resp = await fetch(WL.ajaxUrl, { method: 'POST', body: form });
    if (!resp.ok) throw new Error('Network error (' + resp.status + ')');
    return resp.json();
  }

  function setStatus(el, msg, type) {
    if (!el) return;
    el.textContent  = msg;
    el.className    = 'wl-status' + (type ? ' wl-status--' + type : '');
  }
})();
