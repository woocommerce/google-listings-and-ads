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
import {
	CREATING_ADS_ACCOUNT,
	CREATING_BOTH_ACCOUNTS,
	CREATING_MC_ACCOUNT,
} from '../constants';

/**
 * Renders the description for the account creation card.
 *
 * @param {Object} props Props.
 * @param {string} props.isCreatingWhichAccount The type of account that is being created. Possible values are 'ads', 'mc', or 'both'.
 */
const AccountCreationDescription = ( { isCreatingWhichAccount } ) => {
	const { google, hasFinishedResolution } = useGoogleAccount();
	const { googleMCAccount } = useGoogleMCAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const getDescription = () => {
		if ( isCreatingWhichAccount ) {
			switch ( isCreatingWhichAccount ) {
				case CREATING_BOTH_ACCOUNTS:
					return (
						<p>
							{ __(
								'You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.',
								'google-listings-and-ads'
							) }
						</p>
					);

				case CREATING_ADS_ACCOUNT:
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

				case CREATING_MC_ACCOUNT:
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

		const isCreatingAccounts = !! isCreatingWhichAccount;
		const isGoogleAdsReady =
			! isCreatingAccounts && googleAdsAccount.id > 0;
		const isGoogleMCReady = ! isCreatingAccounts && googleMCAccount.id > 0;

		return (
			<>
				<p>{ google?.email }</p>
				{ isGoogleMCReady && (
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
				{ isGoogleAdsReady && (
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
