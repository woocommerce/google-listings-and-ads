/**
 * Shipping rate selection made by the merchant during onboarding.
 *
 * Mirrors the radio values rendered by
 * `~/components/shipping-rate-section/shipping-rate-section.js`.
 */
export const SHIPPING_RATE_OPTION = {
	AUTOMATIC: 'automatic',
	FLAT: 'flat',
	MANUAL: 'manual',
};

export const WC_SHIPPING_SETTINGS_URL =
	'/wp-admin/admin.php?page=wc-settings&tab=shipping';

export const GOOGLE_MERCHANT_CENTER_URL = 'https://merchants.google.com/';
