/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import AppButton from '~/components/app-button';
import { getCreateCampaignUrl, getSetupAdsUrl } from '~/utils/urls';
import { ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY } from './constants';

const SETUP_ADS_URL = getSetupAdsUrl();
const CREATE_CAMPAIGN_URL = getCreateCampaignUrl();

/**
 * Renders the promo's CTA and Dismiss buttons for a given Google Ads readiness state.
 *
 * @param {Object}  props
 * @param {boolean} props.isGoogleAdsReady Whether the merchant's Google Ads account is connected, claimed, and granted access.
 * @return {JSX.Element} The CTA and Dismiss buttons.
 */
const PromoActions = ( { isGoogleAdsReady } ) => {
	const { set } = useDispatch( preferencesStore );

	/**
	 * Handles the dismissal of the promo.
	 */
	const handleDismiss = () => {
		set(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY,
			true
		);
	};

	return (
		<Flex
			className="gla-analytics-overview-promo__actions"
			justify="flex-start"
			gap={ 2 }
			wrap
		>
			<FlexItem>
				<AppButton
					variant="primary"
					href={
						isGoogleAdsReady ? CREATE_CAMPAIGN_URL : SETUP_ADS_URL
					}
				>
					{ isGoogleAdsReady
						? __( 'Launch a campaign', 'google-listings-and-ads' )
						: __( 'Get started', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexItem>
			<FlexItem>
				<AppButton variant="secondary" onClick={ handleDismiss }>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexItem>
		</Flex>
	);
};

export default PromoActions;
