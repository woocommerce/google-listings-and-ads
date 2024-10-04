/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AccountCard from '.~/components/account-card';
import ContentButtonLayout from '.~/components/content-button-layout';
import LoadingLabel from '.~/components/loading-label';
import Section from '.~/wcdl/section';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';
import AdsAccountSelectControl from '.~/components/google-ads-account-card/connect-ads/ads-account-select-control';
import { useAppDispatch } from '.~/data';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';

/**
 * Clicking on the button to connect an existing Google Ads account.
 *
 * @event gla_ads_account_connect_button_click
 * @property {number} id The account ID to be connected.
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * @fires gla_ads_account_connect_button_click when "Connect" button is clicked.
 * @fires gla_documentation_link_click with `{ context: 'setup-ads-connect-account', link_id: 'connect-sub-account', href: 'https://support.google.com/google-ads/answer/6139186' }`
 * @return {JSX.Element} {@link AccountCard} filled with content.
 */
const ConnectAds = () => {
	const {
		existingAccounts: accounts,
		hasFinishedResolution: hasFinishedResolutionForExistingAdsAccount,
	} = useExistingGoogleAdsAccounts();

	const onCreateNew = () => {};
	const [ value, setValue ] = useState();
	const [ isLoading, setLoading ] = useState( false );
	const [ fetchConnectAdsAccount ] = useApiFetchCallback( {
		path: `/wc/gla/ads/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { refetchGoogleAdsAccount } = useGoogleAdsAccount();
	const getEventProps = useEventPropertiesFilter( FILTER_ONBOARDING );
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

	if ( ! hasFinishedResolutionForExistingAdsAccount ) {
		return null;
	}

	return (
		<AccountCard
			className="gla-google-combo-service-account-card"
			alignIcon="top"
			title={ __(
				'Connect to existing Google Ads account',
				'google-listings-and-ads'
			) }
			helper={ __(
				'Required to set up conversion measurement for your store.',
				'google-listings-and-ads'
			) }
		>
			<Section.Card.Body className="gla-google-combo-service-account-card__body">
				<ContentButtonLayout>
					<AdsAccountSelectControl
						accounts={ accounts }
						value={ value }
						onChange={ setValue }
					/>
					{ isLoading ? (
						<LoadingLabel
							text={ __(
								'Connecting…',
								'google-listings-and-ads'
							) }
						/>
					) : (
						<AppButton
							isSecondary
							disabled={ ! value }
							eventName="gla_ads_account_connect_button_click"
							eventProps={ getEventProps( {
								id: Number( value ),
							} ) }
							onClick={ handleConnectClick }
						>
							{ __( 'Connect', 'google-listings-and-ads' ) }
						</AppButton>
					) }
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer className="gla-google-combo-service-account-card__footer">
				<AppButton
					isTertiary
					disabled={ isLoading }
					onClick={ onCreateNew }
				>
					{ __(
						'Or, create a new Google Ads account',
						'google-listings-and-ads'
					) }
				</AppButton>
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectAds;
