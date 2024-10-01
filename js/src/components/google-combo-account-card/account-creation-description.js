/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';

/**
 * Renders the description for the account creation card.
 *
 * @param {*} param0 Props.
 * @param {boolean} param0.isCreatingAccounts Whether accounts are being created.
 * @param {boolean} param0.isCreatingMCAccount Whether Merchant Center account is being created.
 * @param {boolean} param0.isCreatingAdsAccount Whether Google Ads account is being created.
 * @param {Object} param0.googleMCAccount Google Merchant Center account.
 * @param {Object} param0.googleAdsAccount Google Ads account.
 */
const AccountCreationDescription = ( {
	isCreatingAccounts,
	isCreatingMCAccount,
	isCreatingAdsAccount,
	googleMCAccount = {},
	googleAdsAccount = {},
} ) => {
	const { google } = useGoogleAccount();

	const getDescription = () => {
		if ( isCreatingAccounts ) {
			if ( isCreatingMCAccount && isCreatingAdsAccount ) {
				return (
					<p>
						{ __(
							'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
							'google-listings-and-ads'
						) }
					</p>
				);
			}

			if ( isCreatingAdsAccount ) {
				return (
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
			}

			if ( isCreatingMCAccount ) {
				return (
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
		}

		return (
			<>
				<p>{ google?.email }</p>

				{ googleMCAccount?.id !== 0 && (
					<p>
						{ sprintf(
							// Translators: %s is the Merchant Center ID
							__(
								'Merchant Center ID: %s',
								'google-listings-and-ads'
							),
							googleMCAccount.id
						) }
					</p>
				) }

				{ googleAdsAccount?.id && (
					<p>
						{ sprintf(
							// Translators: %s is the Google Ads ID
							__(
								'Google Ads ID: %s',
								'google-listings-and-ads'
							),
							googleAdsAccount.id
						) }
					</p>
				) }
			</>
		);
	};

	return (
		<div className="gla-connected-google-combo-account-card__description">
			{ getDescription() }
		</div>
	);
};

export default AccountCreationDescription;
