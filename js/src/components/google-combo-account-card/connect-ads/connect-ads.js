/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard from '.~/components/account-card';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import { useAppDispatch } from '.~/data';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import ConnectAccountCard from '../connect-account-card';
import ConnectAdsFooter from './connect-ads-footer';
import ConnectAdsBody from './connect-ads-body';

/**
 * Clicking on the button to connect an existing Google Ads account.
 *
 * @event gla_ads_account_connect_button_click
 * @property {number} id The account ID to be connected.
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * ConnectAds component renders an account card to connect to an existing Google Ads account.
 *
 * @fires gla_ads_account_connect_button_click when "Connect" button is clicked.
 * @fires gla_documentation_link_click with `{ context: 'setup-ads-connect-account', link_id: 'connect-sub-account', href: 'https://support.google.com/google-ads/answer/6139186' }`
 * @return {JSX.Element} {@link AccountCard} filled with content.
 */
const ConnectAds = () => {
	const {
		existingAccounts: accounts,
		hasFinishedResolution: hasFinishedResolutionForExistingAdsAccount,
	} = useExistingGoogleAdsAccounts();

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAccount,
	} = useGoogleAdsAccount();

	const isConnected =
		googleAdsAccount?.status === 'connected' ||
		( googleAdsAccount?.status === 'incomplete' &&
			googleAdsAccount?.step === 'link_merchant' );

	const [ value, setValue ] = useState();
	const [ isLoading, setLoading ] = useState( false );
	const [ fetchConnectAdsAccount ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { refetchGoogleAdsAccount } = useGoogleAdsAccount();
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleAdsAccountStatus } = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		setLoading( true );
		try {
			await fetchConnectAdsAccount();
			await fetchGoogleAdsAccountStatus();
			await refetchGoogleAdsAccount();
			setLoading( false );
		} catch ( error ) {
			setLoading( false );
			createNotice(
				'error',
				__(
					'Unable to connect your Google Ads account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	// If the accounts are still being fetched, we don't want to show the card.
	if (
		! hasFinishedResolutionForExistingAdsAccount ||
		! hasFinishedResolutionForCurrentAccount ||
		accounts.length === 0
	) {
		return null;
	}

	return (
		<ConnectAccountCard
			className="gla-google-combo-service-account-card--ads"
			title={ __(
				'Connect to existing Google Ads account',
				'google-listings-and-ads'
			) }
			helperText={ __(
				'Required to set up conversion measurement for your store.',
				'google-listings-and-ads'
			) }
			body={
				<ConnectAdsBody
					{ ...{
						accounts,
						googleAdsAccount,
						isConnected,
						handleConnectClick,
						isLoading,
						setValue,
						value,
					} }
				/>
			}
			footer={ <ConnectAdsFooter /> }
		/>
	);
};

export default ConnectAds;
