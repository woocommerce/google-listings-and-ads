/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';

/**
 * Account details.
 * @return {JSX.Element} JSX markup.
 */
const AccountDetails = () => {
	const { google } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { googleMCAccount } = useGoogleMCAccount();

	return (
		<div className="gla-account-card__account_details">
			<span>{ google.email }</span>
			<span>
				{ sprintf(
					// Translators: %s is the Merchant Center ID
					__( 'Merchant Center ID: %s', 'google-listings-and-ads' ),
					googleMCAccount.id
				) }
			</span>
			<span>
				{ sprintf(
					// Translators: %s is the Google Ads ID
					__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
					googleAdsAccount.id
				) }
			</span>
		</div>
	);
};

export default AccountDetails;
