/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Account details.
 *
 * @param {Object} props Component props.
 * @param {string} props.email Google account email address.
 * @param {number} props.googleAdsID Google Ads account ID.
 * @param {number} props.googleMerchantCenterID Google Merchant Center account ID.
 * @return {JSX.Element} JSX markup.
 */
const AccountDetails = ( { email, googleAdsID, googleMerchantCenterID } ) => {
	return (
		<div className="gla-account-card__account_details">
			<span>{ email }</span>
			<span>
				{ sprintf(
					// Translators: %s is the Merchant Center ID
					__( 'Merchant Center ID: %s', 'google-listings-and-ads' ),
					googleMerchantCenterID
				) }
			</span>
			<span>
				{ sprintf(
					// Translators: %s is the Google Ads ID
					__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
					googleAdsID
				) }
			</span>
		</div>
	);
};

export default AccountDetails;
