/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import AppSpinner from '../app-spinner';
import useCreateAccounts from './useCreateAccounts';

/**
 * Clicking on the "connect to a different Google account" button.
 *
 * @event gla_google_account_connect_different_account_button_click
 */

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 *
 * @param {Object} props React props.
 * @param {{ googleAccount: object }} props.googleAccount The Google account.
 *
 * @fires gla_google_account_connect_different_account_button_click
 */
const ConnectedGoogleComboAccountCard = ( { googleAccount } ) => {
	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const { accountCreationResolved, isCreatingAccounts } = useCreateAccounts();

	if (
		! hasFinishedResolutionForCurrentMCAccount ||
		! hasFinishedResolutionForCurrentAdsAccount ||
		! accountCreationResolved
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const Description = () => {
		if ( isCreatingAccounts ) {
			return createInterpolateElement(
				__(
					'<p>You don’t have Merchant Center nor Google Ads accounts, so we’re creating them for you.</p>',
					'google-listings-and-ads'
				),
				{
					p: <p></p>,
				}
			);
		}

		return (
			<>
				<p>{ googleAccount?.email }</p>
				<p>
					{ sprintf(
						// Translators: %s is the Merchant Center ID
						__(
							'Merchant Center ID: %s',
							'google-listings-and-ads'
						),
						googleMCAccount?.id
					) }
				</p>
				<p>
					{ sprintf(
						// Translators: %s is the Google Ads ID
						__( 'Google Ads ID: %s', 'google-listings-and-ads' ),
						googleAdsAccount?.id
					) }
				</p>
			</>
		);
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			className="gla-connect-google-combo-account-card gla-connected-google-combo-account-card"
			description={ <Description /> }
			helper={ createInterpolateElement(
				__(
					'<p>Merchant Center is required to sync products so they show on Google. Google Ads is required to set up conversion measurement for your store.</p>',
					'google-listings-and-ads'
				),
				{
					p: <p></p>,
				}
			) }
			indicator={ isCreatingAccounts ? 'Creating...' : null }
		/>
	);
};

export default ConnectedGoogleComboAccountCard;
