(function () {
    'use strict';

    // Script que calcula automáticamente montos en el formulario de alquileres.

    function parseDate(value) {
        if (!value) {
            return null;
        }

        const parts = value.split('-');
        if (parts.length !== 3) {
            return null;
        }

        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);

        return new Date(year, month, day);
    }

    function formatAmount(value) {
        return value.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Configura listeners y pinta feedback contextual en el helper del formulario.
    function initRentalCalculator() {
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');
        const rateInput = document.getElementById('daily_rate');
        const totalInput = document.getElementById('total_amount');
        const summary = document.querySelector('[data-rental-summary]');

        if (!startInput || !endInput || !rateInput || !totalInput || !summary) {
            return;
        }

        const helperBaseClass = 'form-helper';

        function setSummary(message, variant) {
            summary.className = helperBaseClass + ' form-helper--' + variant;
            summary.textContent = message;
        }

        function recalculate() {
            const startDate = parseDate(startInput.value);
            const endDate = parseDate(endInput.value);
            const rate = parseFloat(rateInput.value || '0');

            if (!startDate || !endDate) {
                setSummary('Completá inicio y fin para calcular el total.', 'muted');
                return;
            }

            if (endDate < startDate) {
                totalInput.value = '0.00';
                setSummary('La fecha de fin debe ser posterior a la de inicio.', 'error');
                return;
            }

            const milliseconds = endDate.getTime() - startDate.getTime();
            const days = Math.max(1, Math.round(milliseconds / 86400000) + 1);
            const total = Math.max(0, rate) * days;

            totalInput.value = total.toFixed(2);
            setSummary(days + ' día' + (days > 1 ? 's' : '') + ' · $' + formatAmount(total), 'success');
        }

        ['change', 'input'].forEach(function (eventName) {
            startInput.addEventListener(eventName, recalculate);
            endInput.addEventListener(eventName, recalculate);
            rateInput.addEventListener(eventName, recalculate);
        });

        recalculate();
    }

    document.addEventListener('DOMContentLoaded', initRentalCalculator);
})();
