// /js/lightbox.js
//
// Gallery-aware lightbox for SonaCMS.
//
// Handles two cases with one script:
//   1. Standalone lightbox images (class "cms-lightbox", no data-gallery):
//      open alone, no navigation.
//   2. Gallery images (class "cms-lightbox" + data-gallery="id" + data-index):
//      open with prev/next navigation cycling through that gallery's images.
//
// Expects the overlay markup (#cms-lightbox) present in the page. No library.

(function () {
    var overlay = document.getElementById('cms-lightbox');
    if (!overlay) return;

    var overlayImg = overlay.querySelector('.cms-lightbox-overlay__img');
    var caption    = overlay.querySelector('.cms-lightbox-overlay__caption');
    var prevBtn    = overlay.querySelector('.cms-lightbox-overlay__prev');
    var nextBtn    = overlay.querySelector('.cms-lightbox-overlay__next');

    var currentSet = [];   // array of {url, caption} for the active gallery
    var currentIndex = 0;

    // Build the ordered set of items for a given gallery id
    function buildSet(galleryId) {
        var links = Array.prototype.slice.call(
            document.querySelectorAll('a.cms-lightbox[data-gallery="' + galleryId + '"]')
        );
        // Sort by data-index so navigation follows the displayed order
        links.sort(function (a, b) {
            return parseInt(a.getAttribute('data-index'), 10) - parseInt(b.getAttribute('data-index'), 10);
        });
        return links.map(function (a) {
            return { url: a.getAttribute('href'), caption: a.getAttribute('data-caption') || '' };
        });
    }

    function show(index) {
        if (index < 0) index = currentSet.length - 1;      // wrap to end
        if (index >= currentSet.length) index = 0;         // wrap to start
        currentIndex = index;
        var item = currentSet[index];
        overlayImg.src = item.url;
        caption.textContent = item.caption || '';
        caption.style.display = item.caption ? 'block' : 'none';

        // Show nav only when there's more than one image
        var multi = currentSet.length > 1;
        prevBtn.style.display = multi ? 'flex' : 'none';
        nextBtn.style.display = multi ? 'flex' : 'none';
    }

    function open() {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function close() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        overlayImg.src = '';
        currentSet = [];
    }

    // Click handler for any lightbox-flagged image
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a.cms-lightbox');
        if (!link) return;
        e.preventDefault();

        var galleryId = link.getAttribute('data-gallery');
        if (galleryId) {
            currentSet = buildSet(galleryId);
            var idx = parseInt(link.getAttribute('data-index'), 10) || 0;
            show(idx);
        } else {
            // Standalone image — a set of one
            currentSet = [{ url: link.getAttribute('href'), caption: link.getAttribute('data-caption') || '' }];
            show(0);
        }
        open();
    });

    // Prev / Next
    prevBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        show(currentIndex - 1);
    });
    nextBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        show(currentIndex + 1);
    });

    // Close when clicking the backdrop (but not the image or nav buttons)
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay || e.target.classList.contains('cms-lightbox-overlay__figure')) {
            close();
        }
    });
    overlay.querySelector('.cms-lightbox-overlay__close').addEventListener('click', close);

    // Keyboard: Esc closes, arrows navigate
    document.addEventListener('keydown', function (e) {
        if (!overlay.classList.contains('is-open')) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowLeft') show(currentIndex - 1);
        else if (e.key === 'ArrowRight') show(currentIndex + 1);
    });
})();