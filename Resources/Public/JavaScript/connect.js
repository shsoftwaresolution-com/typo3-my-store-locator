/**
 * "Connect with My Store Locator" field control (TYPO3 backend, ES module).
 * Opens https://app.my-store-locator.com/connect?platform=typo3&return_url=…&state=… in a popup.
 * The return page (Resources/Public/Connect/return.html) posts {type:'msl:connect', token, map, state}
 * back to this window (same origin) – or, when a Cross-Origin-Opener-Policy cut window.opener, over the same-origin
 * BroadcastChannel 'msl-connect' (answered with an ack). The state is checked against sessionStorage, then the token field is filled.
 */
const TOKEN = /^[A-Za-z0-9_-]{6,64}$/;
const KEY = 'msl-connect-state';

function randomState() {
  const a = new Uint8Array(16);
  crypto.getRandomValues(a);
  return Array.from(a, (b) => b.toString(16).padStart(2, '0')).join('');
}

function fieldFor(button) {
  const name = button.dataset.mslField || '';
  const scope = button.closest('.form-wizards-wrap, .form-control-wrap, .formengine-field-item') || document;
  return scope.querySelector('[data-formengine-input-name="' + CSS.escape(name) + '"]')
    || document.querySelector('[data-formengine-input-name="' + CSS.escape(name) + '"]');
}

let pending = null;

function accept(data) {
  if (!data || data.type !== 'msl:connect' || !pending) return false;
  let expected = '';
  try { expected = sessionStorage.getItem(KEY) || ''; } catch (err) { expected = pending.state; }
  if (!expected || data.state !== expected || !TOKEN.test(String(data.token || ''))) return false;
  try { sessionStorage.removeItem(KEY); } catch (err) { /* ignore */ }
  const input = fieldFor(pending.button);
  pending = null;
  if (!input) return true;
  input.value = data.token;
  input.dispatchEvent(new Event('change', { bubbles: true }));
  input.dispatchEvent(new Event('input', { bubbles: true }));
  input.focus();
  return true;
}

window.addEventListener('message', (e) => {
  if (e.origin === window.location.origin) accept(e.data);
});

if ('BroadcastChannel' in window) {
  const channel = new BroadcastChannel('msl-connect');
  channel.onmessage = (e) => {
    const state = e.data && e.data.state;
    if (accept(e.data)) channel.postMessage({ type: 'msl:connect:ack', state });
  };
}

document.addEventListener('click', (e) => {
  const button = e.target.closest && e.target.closest('.msl-connect-button[data-msl-connect]');
  if (!button) return;
  e.preventDefault();
  const state = randomState();
  try { sessionStorage.setItem(KEY, state); } catch (err) { /* state is also kept in memory */ }
  pending = { button, state };
  const url = button.dataset.mslConnect + '&state=' + encodeURIComponent(state);
  const w = 520, h = 720;
  const left = Math.max(0, (window.screenX || 0) + (window.outerWidth - w) / 2);
  const top = Math.max(0, (window.screenY || 0) + (window.outerHeight - h) / 2);
  const popup = window.open(url, 'msl-connect', 'popup=yes,width=' + w + ',height=' + h + ',left=' + left + ',top=' + top);
  if (popup) popup.focus();
});
