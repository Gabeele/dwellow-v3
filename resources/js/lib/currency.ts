/**
 * dwellow is a Canadian product, so money is always rendered as CAD with the
 * `en-CA` locale. Whole-dollar amounts are the norm for rent, so fractional
 * digits are dropped by default; pass `fractionDigits` for the rare case where
 * cents matter.
 */
const cadFormatters = new Map<number, Intl.NumberFormat>();

function cadFormatter(fractionDigits: number): Intl.NumberFormat {
    let formatter = cadFormatters.get(fractionDigits);

    if (!formatter) {
        formatter = new Intl.NumberFormat('en-CA', {
            style: 'currency',
            currency: 'CAD',
            maximumFractionDigits: fractionDigits,
        });
        cadFormatters.set(fractionDigits, formatter);
    }

    return formatter;
}

/**
 * Format a number as Canadian currency, e.g. `1500` → `$1,500`. The single
 * source of truth for how rent and other money amounts are rendered.
 */
export function formatCurrency(value: number, fractionDigits = 0): string {
    return cadFormatter(fractionDigits).format(value);
}
