/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

const AccountDetails = () => {
	const { google } = useGoogleAccount();
	const { googleMCAccount } = useGoogleMCAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();

	return (
		<div className="gla-account-card__account_details">
			<span>{ google?.email }</span>
			{ googleMCAccount.id > 0 && (
				<span>
					{ sprintf(
						// Translators: %s is the Merchant Center ID
						__(
							'Merchant Center ID: %s',
							'google-listings-and-ads'
						),
						googleMCAccount.id
					) }
				</span>
			) }
			{ googleAdsAccount.id > 0 && (
				<span>
					{ sprintf(
						// Translators: %s is the Google Ads ID
						__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
						googleAdsAccount.id
					) }
				</span>
			) }
		</div>
	);
};

export default AccountDetails;
