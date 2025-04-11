/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';

/**
 * @typedef {import('~/data/types.js').GoogleMCAccount} GoogleMCAccount
 */

/**
 * Renders a Google Merchant Center account card UI with connected account information.
 *
 * @param {Object} props React props.
 * @param {GoogleMCAccount} props.googleMCAccount A data payload object of Google Merchant Center account.
 */
const MerchantCenterAccountInfoCard = ( { googleMCAccount } ) => {
	const domain = new URL( getSetting( 'homeUrl' ) ).host;

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ sprintf(
				// translators: 1: account domain, 2: account ID.
				__( '%1$s (%2$s)', 'google-listings-and-ads' ),
				domain,
				googleMCAccount.id
			) }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default MerchantCenterAccountInfoCard;
