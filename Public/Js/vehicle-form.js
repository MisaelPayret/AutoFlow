(function () {
    'use strict';

    function initGalleryStateSync() {
        const form = document.querySelector('[data-gallery-form]');
        if (!form) {
            return;
        }

        const stateInput = form.querySelector('[data-gallery-state]');
        if (!stateInput) {
            return;
        }

        form.addEventListener('submit', function () {
            const items = [];
            const cards = form.querySelectorAll('.gallery-manager__item');

            cards.forEach(function (card) {
                const id = parseInt(card.getAttribute('data-image-id'), 10);
                if (!id) {
                    return;
                }

                const orderInput = card.querySelector('[data-gallery-order]');
                const deleteInput = card.querySelector('[data-gallery-delete]');
                const coverInput = card.querySelector('[data-gallery-cover]');

                items.push({
                    id: id,
                    order: orderInput ? parseInt(orderInput.value, 10) || 0 : 0,
                    delete: Boolean(deleteInput && deleteInput.checked),
                    cover: Boolean(coverInput && coverInput.checked)
                });
            });

            stateInput.value = JSON.stringify({ items: items });
        });
    }

    function initUploadHelper() {
        const fileInput = document.querySelector('[data-file-input]');
        const helper = document.querySelector('[data-upload-feedback]');
        if (!fileInput || !helper) {
            return;
        }

        const defaultMarkup = helper.innerHTML;
        const defaultClasses = helper.className;
        const maxFiles = 5;
        const maxSize = 2 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/png'];
        const escapeMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (character) {
                return escapeMap[character] || character;
            });
        }

        function resetHelper() {
            helper.className = defaultClasses;
            helper.innerHTML = defaultMarkup;
        }

        function render(messages, variant) {
            helper.className = 'form-helper form-helper--' + variant;
            if (!messages.length) {
                helper.innerHTML = defaultMarkup;
                return;
            }

            helper.innerHTML = '<ul>' + messages.map(function (message) {
                return '<li>' + message + '</li>';
            }).join('') + '</ul>';
        }

        fileInput.addEventListener('change', function () {
            if (!fileInput.files || fileInput.files.length === 0) {
                resetHelper();
                return;
            }

            const warnings = [];
            const confirmations = [];

            if (fileInput.files.length > maxFiles) {
                warnings.push('Seleccionaste ' + fileInput.files.length + ' archivos, solo se procesan ' + maxFiles + ' por envío.');
            }

            Array.prototype.forEach.call(fileInput.files, function (file) {
                const label = escapeHtml(file.name || 'archivo sin nombre');

                if (file.type && allowedTypes.indexOf(file.type) === -1) {
                    warnings.push('"' + label + '" debe estar en JPG o PNG.');
                    return;
                }

                if (file.size && file.size > maxSize) {
                    warnings.push('"' + label + '" supera los 2 MB permitidos.');
                    return;
                }

                confirmations.push('"' + label + '" listo para subir.');
            });

            if (warnings.length) {
                render(warnings, 'error');
                return;
            }

            if (confirmations.length) {
                render(confirmations.slice(0, 3), 'success');
                return;
            }

            render(['Seleccioná imágenes JPG o PNG válidas.'], 'info');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initGalleryStateSync();
        initUploadHelper();
    });
})();
