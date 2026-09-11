/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getCreateCampaignUrl, getSetupAdsUrl } from '~/utils/urls';

const SETUP_ADS_URL = getSetupAdsUrl();
const CREATE_CAMPAIGN_URL = getCreateCampaignUrl();

/**
 * Renders the promo's CTA and Dismiss buttons for a given Google Ads readiness state.
 *
 * @param {Object}   props
 * @param {boolean}  props.isGoogleAdsReady Whether the merchant's Google Ads account is connected, claimed, and granted access.
 * @param {Function} props.onDismiss        Called when the Dismiss button is clicked.
 * @return {JSX.Element} The CTA and Dismiss buttons.
 */
const PromoActions = ( { isGoogleAdsReady, onDismiss } ) => (
	<Flex gap={ 2 } wrap>
		<FlexItem>
			<AppButton
				variant="primary"
				href={ isGoogleAdsReady ? CREATE_CAMPAIGN_URL : SETUP_ADS_URL }
			>
				{ isGoogleAdsReady
					? __( 'Launch a campaign', 'google-listings-and-ads' )
					: __( 'Get started', 'google-listings-and-ads' ) }
			</AppButton>
		</FlexItem>
		<FlexItem>
			<AppButton variant="secondary" onClick={ onDismiss }>
				{ __( 'Dismiss', 'google-listings-and-ads' ) }
			</AppButton>
		</FlexItem>
	</Flex>
);

export default PromoActions;
