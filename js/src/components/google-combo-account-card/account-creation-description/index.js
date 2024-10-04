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
import AccountInfo from './account-info';

/**
 * Renders the description for the account creation card.
 *
 * @param {Object} props Props.
 * @param {Object} props.accountsCreated Whether accounts have been created.
 * @param {boolean} props.isCreatingBothAccounts Whether both, MC and Ads accounts are being created.
 * @param {boolean} props.isCreatingMCAccount Whether Merchant Center account is being created.
 * @param {boolean} props.isCreatingAdsAccount Whether Google Ads account is being created.
 */
const AccountCreationDescription = ( {
	accountsCreated,
	isCreatingBothAccounts,
	isCreatingMCAccount,
	isCreatingAdsAccount,
} ) => {
	const { google } = useGoogleAccount();
	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const isLoadingData =
		accountsCreated &&
		( ! hasFinishedResolutionForCurrentMCAccount ||
			! hasFinishedResolutionForCurrentAdsAccount );

	const getDescription = () => {
		if (
			isLoadingData ||
			isCreatingBothAccounts ||
			isCreatingMCAccount ||
			isCreatingAdsAccount
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
			} else if ( isCreatingAdsAccount ) {
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
			} else if ( isCreatingMCAccount ) {
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
				<AccountInfo
					account={ googleMCAccount }
					text={ sprintf(
						// Translators: %s is the Merchant Center ID
						__(
							'Merchant Center ID: %s',
							'google-listings-and-ads'
						),
						googleMCAccount?.id
					) }
				/>
				<AccountInfo
					account={ googleAdsAccount }
					text={ sprintf(
						// Translators: %s is the Google Ads ID
						__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
						googleAdsAccount?.id
					) }
				/>
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
