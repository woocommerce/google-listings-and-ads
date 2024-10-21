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

/**
 * Renders the description for the account creation card.
 *
 * @param {Object} props Props.
 * @param {boolean} props.isCreatingBothAccounts Whether both, MC and Ads accounts are being created.
 * @param {boolean} props.isCreatingOnlyMCAccount Whether only the Merchant Center account is being created.
 * @param {boolean} props.isCreatingOnlyAdsAccount Whether only Google Ads account is being created.
 * @param {boolean} props.isGoogleMCAccountConnected Whether we have a connected MC account.
 * @param {boolean} props.isGoogleAdsAccountConnected Whether we have a connected Ads account.
 */
const AccountCreationDescription = ( {
	isCreatingBothAccounts,
	isCreatingOnlyMCAccount,
	isCreatingOnlyAdsAccount,
	isGoogleMCAccountConnected,
	isGoogleAdsAccountConnected,
} ) => {
	const { google } = useGoogleAccount();
	const { googleMCAccount } = useGoogleMCAccount();

	const { googleAdsAccount } = useGoogleAdsAccount();

	const getDescription = () => {
		if (
			isCreatingBothAccounts ||
			isCreatingOnlyMCAccount ||
			isCreatingOnlyAdsAccount
		) {
			if ( isCreatingBothAccounts ) {
				return (
					<p>
						{ __(
							'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
							'google-listings-and-ads'
						) }
					</p>
				);
			} else if ( isCreatingOnlyAdsAccount ) {
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
			} else if ( isCreatingOnlyMCAccount ) {
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

				{ isGoogleMCAccountConnected && (
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

				{ isGoogleAdsAccountConnected && (
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
