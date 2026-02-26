/**
 * Format a number for display:
 * - Max 2 decimal places, removes trailing zeros (2.50 → "2.5", 2.10 → "2.1")
 * - Whole numbers display without decimals (2.00 → "2")
 * - Adds thousand separators for large numbers
 * @param {number|string} value
 * @returns {string}
 */
export function formatNumber(value) {
    if (value === null || value === undefined || value === '') {
        return '0';
    }

    const num = parseFloat(value);
    if (isNaN(num)) {
        return '0';
    }

    // Round to 2 decimal places then remove trailing zeros
    const rounded = Math.round(num * 100) / 100;

    // Check if it's a whole number
    if (rounded % 1 === 0) {
        return Math.round(rounded).toLocaleString();
    }

    // For decimals, format with max 2 decimals (trailing zeros removed automatically)
    return rounded.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

/**
 * Format a number for input field display (with thousand separators)
 * Returns empty string for null/undefined/empty values
 * @param {number|string} value
 * @returns {string}
 */
export function formatForInput(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const num = parseFloat(String(value).replace(/,/g, ''));
    if (isNaN(num)) {
        return '';
    }

    // Round to 2 decimal places
    const rounded = Math.round(num * 100) / 100;

    // Check if it's a whole number
    if (rounded % 1 === 0) {
        return Math.round(rounded).toLocaleString();
    }

    // For decimals, format with max 2 decimals
    return rounded.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

/**
 * Get today's date as YYYY-MM-DD string in LOCAL timezone (tránh UTC shift)
 * @returns {string} e.g. "2026-02-27"
 */
export function todayLocalStr() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

/**
 * Format a date string for display, dùng LOCAL timezone để tránh UTC shift.
 * Hỗ trợ cả date-only "2026-02-27" và datetime "2026-02-27T15:00:00Z".
 * @param {string} date
 * @param {string} locale
 * @param {object} options - override toLocaleDateString options
 * @returns {string}
 */
export function formatLocalDate(date, locale = 'ja-JP', options = {}) {
    if (!date) return '';
    // Lấy phần date-only để tránh timezone shift khi parse
    const datePart = date.split('T')[0].split(' ')[0];
    const [year, month, day] = datePart.split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString(locale, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...options,
    });
}

/**
 * Parse a formatted number string back to a number
 * Removes thousand separators and returns raw number
 * @param {string} value
 * @returns {number|string}
 */
export function parseFromInput(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    // Remove thousand separators (both comma and dot depending on locale)
    const cleaned = String(value).replace(/,/g, '');
    const num = parseFloat(cleaned);

    if (isNaN(num)) {
        return '';
    }

    return num;
}
