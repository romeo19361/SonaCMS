// /js/lightbox.js
//
// Vanilla lightbox for image blocks set to "lightbox" mode.
// No library. Only does anything if a lightbox-flagged image is clicked.
//
// Expects the overlay markup (#cms-lightbox) to exist in the page, and
// image links to carry the class "cms-lightbox".

(function () {
    var overlay = document.getElementById('cms-lightbox');
    if (!overlay) return;
    var overlayImg = overlay.querySelector('.cms-lightbox-overlay__img');

    // Open when any lightbox-flagged image link is clicked
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a.cms-lightbox');
        if (!link) return;
        e.preventDefault();
        overlayImg.src = link.getAttribute('href');
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
    });

    function close() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        overlayImg.src = '';
    }

    // Close on overlay click or the close button
    overlay.addEventListener('click', close);
    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });
})();