/**
 * Format a number for display:
 * - Removes trailing zeros (2.0 → "2", 2.50 → "2.5")
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

    // Check if it's a whole number
    if (Number.isInteger(num)) {
        return num.toLocaleString();
    }

    // For decimals, remove trailing zeros and format
    // parseFloat removes trailing zeros automatically
    const formatted = parseFloat(num.toFixed(2));
    return formatted.toLocaleString();
}
