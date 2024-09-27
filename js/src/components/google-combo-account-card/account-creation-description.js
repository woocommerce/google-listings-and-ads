/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

const AccountCreationDescription = ( {
	isCreatingAccounts,
	isCreatingMCAccount,
	isCreatingAdsAccount,
	googleAccount = {},
	googleMCAccount = {},
	googleAdsAccount = {},
} ) => {
	if ( isCreatingAccounts ) {
		let description;

		if ( isCreatingMCAccount && isCreatingAdsAccount ) {
			description = (
				<p>
					{ __(
						'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
						'google-listings-and-ads'
					) }
				</p>
			);
		} else if ( isCreatingAdsAccount ) {
			description = (
				<>
					<p>
						{ __(
							'You don’t have Google Ads account, so we’re creating one for you.',
							'google-listings-and-ads'
						) }
					</p>
					<em>
						{ __(
							'Required to set up conversion measurement for your store.',
							'google-listings-and-ads'
						) }
					</em>
				</>
			);
		} else if ( isCreatingMCAccount ) {
			description = (
				<>
					<p>
						{ __(
							'You don’t have Merchant Center account, so we’re creating one for you.',
							'google-listings-and-ads'
						) }
					</p>
					<em>
						{ __(
							'Required to sync products so they show on Google.',
							'google-listings-and-ads'
						) }
					</em>
				</>
			);
		}

		return description;
	}

	return (
		<div className="gla-connected-google-combo-account-card__description">
			<p>{ googleAccount?.email }</p>
			<p>
				{ sprintf(
					// Translators: %s is the Merchant Center ID
					__( 'Merchant Center ID: %s', 'google-listings-and-ads' ),
					googleMCAccount.id ?? 0
				) }
			</p>
			<p>
				{ sprintf(
					// Translators: %s is the Google Ads ID
					__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
					googleAdsAccount.id ?? 0
				) }
			</p>
		</div>
	);
};

export default AccountCreationDescription;
