(function () {
    'use strict';

    function initCarousel() {
        const gallery = document.querySelector('[data-gallery="carousel"]');
        if (!gallery) {
            return;
        }

        const track = gallery.querySelector('[data-gallery-track]');
        const items = track ? Array.from(track.querySelectorAll('.vehicle-gallery__item')) : [];
        if (!items.length) {
            return;
        }

        let current = 0;

        function updateActive() {
            items.forEach(function (item, index) {
                item.classList.toggle('is-active', index === current);
            });
        }

        function showNext(step) {
            current = (current + step + items.length) % items.length;
            updateActive();
        }

        const controls = document.createElement('div');
        controls.className = 'vehicle-gallery__controls';
        controls.innerHTML = '' +
            '<button type="button" class="vehicle-gallery__btn vehicle-gallery__btn--prev">&#10094;</button>' +
            '<button type="button" class="vehicle-gallery__btn vehicle-gallery__btn--next">&#10095;</button>';
        gallery.appendChild(controls);

        controls.querySelector('.vehicle-gallery__btn--prev').addEventListener('click', function () {
            showNext(-1);
        });

        controls.querySelector('.vehicle-gallery__btn--next').addEventListener('click', function () {
            showNext(1);
        });

        updateActive();
    }

    function initLightbox() {
        const lightbox = document.querySelector('[data-lightbox]');
        if (!lightbox) {
            return;
        }

        const imageTarget = lightbox.querySelector('[data-lightbox-img]');
        const closeElements = lightbox.querySelectorAll('[data-lightbox-close]');
        const body = document.body;

        function openLightbox(src, alt) {
            if (!src) {
                return;
            }

            imageTarget.src = src;
            imageTarget.alt = alt || 'Vista ampliada';
            lightbox.hidden = false;
            body.classList.add('scroll-locked');
        }

        function closeLightbox() {
            lightbox.hidden = true;
            imageTarget.src = '';
            body.classList.remove('scroll-locked');
        }

        document.querySelectorAll('.vehicle-gallery__item img').forEach(function (img) {
            img.addEventListener('click', function () {
                openLightbox(img.src, img.alt);
            });
        });

        closeElements.forEach(function (element) {
            element.addEventListener('click', closeLightbox);
        });

        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox) {
                closeLightbox();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !lightbox.hidden) {
                closeLightbox();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCarousel();
        initLightbox();
    });
})();
