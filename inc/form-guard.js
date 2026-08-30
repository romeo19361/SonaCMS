// /inc/form-guard.js
//
// Portable, same-origin spam guard for SonaCMS forms.
//
// On load it finds every form that posts to the SonaCMS form handler and
// injects a freshly minted token (fetched from /inc/form-token.php) as a hidden
// field. No third-party services, no external accounts — same-origin only.
//
// WHY THIS WORKS: automated spam bots overwhelmingly do NOT execute JavaScript
// (which is exactly why direct-POST spam never appears in client-side analytics
// like StatCounter). A token minted by JS is therefore present for real
// browsers and absent for those bots — so the server drops them.
//
// Use this route only if your forms are served as static HTML. If they render
// through PHP, embed the token server-side with sona_form_token_field() and you
// don't need this file at all.
//
// TRADE-OFF: visitors with JavaScript disabled won't get a token and, once
// 'form_protection' is enabled, their submissions will be rejected. For most
// public sites under active spam this is an acceptable exchange; if you must
// support no-JS visitors, use the server-rendered token route instead.

(function () {
    'use strict';

    var HANDLER = '/inc/formHandler.php';
    var TOKEN_URL = '/inc/form-token.php';

    function handlerForms() {
        return Array.prototype.filter.call(
            document.querySelectorAll('form[action]'),
            function (f) {
                var action = f.getAttribute('action') || '';
                return action === HANDLER || action.indexOf(HANDLER) !== -1;
            }
        );
    }

    function init() {
        var targets = handlerForms();
        if (!targets.length) {
            return;
        }

        fetch(TOKEN_URL, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (res) {
                return res.ok ? res.text() : '';
            })
            .then(function (token) {
                token = (token || '').trim();
                if (!token) {
                    return;
                }
                targets.forEach(function (form) {
                    if (form.querySelector('input[name="form_token"]')) {
                        return;
                    }
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'form_token';
                    input.value = token;
                    form.appendChild(input);
                });
            })
            .catch(function () {
                // Fail quietly — the server still enforces its own defences.
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();