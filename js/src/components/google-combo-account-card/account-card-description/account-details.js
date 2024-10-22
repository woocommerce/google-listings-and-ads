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
import './account-details.scss';

const AccountDetails = () => {
	const { google } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { googleMCAccount } = useGoogleMCAccount();

	return (
		<div className="gla-account-card__account_details">
			{ google?.email && <p>{ google.email }</p> }

			{ googleMCAccount?.id > 0 && (
				<p>
					{ sprintf(
						// Translators: %s is the Google Merchant Center ID
						__(
							'Merchant Center ID: %s',
							'google-listings-and-ads'
						),
						googleMCAccount.id
					) }
				</p>
			) }

			{ googleAdsAccount?.id > 0 && (
				<p>
					{ sprintf(
						// Translators: %s is the Google Ads ID
						__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
						googleAdsAccount.id
					) }
				</p>
			) }
		</div>
	);
};

export default AccountDetails;
