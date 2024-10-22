/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Account details.
 *
 * @param {Object} props Component props.
 * @param {string} props.email Account email.
 * @param {number} props.googleAdsID Google Ads ID.
 * @param {number} props.googleMerchantCenterID Google Merchant Center ID.
 * @return {JSX.Element} JSX markup.
 */
const AccountDetails = ( { email, googleAdsID, googleMerchantCenterID } ) => {
	return (
		<div className="gla-account-card__account_details">
			<span>{ email }</span>
			{ Number( googleMerchantCenterID ) > 0 && (
				<span>
					{ sprintf(
						// Translators: %s is the Merchant Center ID
						__(
							'Merchant Center ID: %s',
							'google-listings-and-ads'
						),
						googleMerchantCenterID
					) }
				</span>
			) }
			{ Number( googleAdsID ) > 0 && (
				<span>
					{ sprintf(
						// Translators: %s is the Google Ads ID
						__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
						googleAdsID
					) }
				</span>
			) }
		</div>
	);
};

export default AccountDetails;
