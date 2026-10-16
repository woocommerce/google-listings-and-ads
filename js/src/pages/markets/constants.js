/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.

export const WC_SHIPPING_SETTINGS_URL =
	getSetting( 'adminUrl' ) + 'admin.php?page=wc-settings&tab=shipping';

export const GOOGLE_MERCHANT_CENTER_URL = 'https://merchants.google.com/';

/**
 * Identifier of the always-present "primary" market.
 *
 * Several actions (e.g. delete) are not applicable to the primary market and
 * use this constant to short-circuit their eligibility.
 */
export const PRIMARY_MARKET_ID = 'primary';
