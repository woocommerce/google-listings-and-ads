/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Notice } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import {
	CAMPAIGN_TYPE_PMAX,
	PREFERENCES_STORE_NAMESPACE,
	PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH,
} from '~/constants';
import { getEditCampaignUrl } from '~/utils/urls';
import AppButton from '~/components/app-button';
import usePreference from '~/hooks/usePreference';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useAdsRecommendations from '~/hooks/useAdsRecommendations';
import './index.scss';

const PREFERENCE_BANNER_KEY = 'pmax-improve-assets-banner';

/**
 * Displays a dismissible banner prompting users to improve assets for their highest-spending enabled Performance Max (PMAX) campaign.
 *
 * The banner is shown only if:
 * - The preference expiry is undefined or expired.
 * - There are enabled PMAX campaigns.
 * - There are relevant asset improvement recommendations.
 *
 * When dismissed, the banner will not reappear until the expiry time elapses.
 * Clicking "Improve Assets" navigates to the asset group edit page for the highest-spending PMAX campaign.
 *
 * @return {JSX.Element|null} The banner component, or null if not applicable.
 */
const PMaxImproveAssetsBanner = () => {
	const { set } = useDispatch( preferencesStore );
	const { expiry } = usePreference( PREFERENCE_BANNER_KEY ) || {};
	const { data: adsCampaignsData } = useAdsCampaigns();
	const { recommendations } = useAdsRecommendations(
		PMAX_IMPROVE_PERFORMANCE_MAX_AD_STRENGTH
	);

	if ( expiry !== undefined ) {
		if ( Date.now() < expiry ) {
			// Do not render the banner if not expired
			return null;
		}

		// If expired, reset the preference
		set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, {
			expiry: undefined, // Reset to undefined to show the banner again
		} );
	}

	if ( ! adsCampaignsData || ! recommendations?.length ) {
		return null;
	}

	const pmaxCampaigns = adsCampaignsData.filter(
		( { type, status } ) =>
			type === CAMPAIGN_TYPE_PMAX && status === 'enabled'
	);

	if ( ! pmaxCampaigns.length ) {
		return null;
	}

	const highestAmountCampaign = pmaxCampaigns.reduce(
		( max, campaign ) => ( campaign.amount > max.amount ? campaign : max ),
		pmaxCampaigns[ 0 ]
	);
	const { id, name } = highestAmountCampaign;

	const hasHighestSpendingCampaignRecommendation = recommendations.some(
		( recommendation ) => recommendation.campaign_id === id
	);

	if ( ! hasHighestSpendingCampaignRecommendation ) {
		return null;
	}

	const dismissBanner = () => {
		set( PREFERENCES_STORE_NAMESPACE, PREFERENCE_BANNER_KEY, {
			expiry: Date.now() + 30 * 24 * 60 * 60 * 1000, // 30 days
		} );
	};

	const handleOnImproveAssets = () => {
		dismissBanner();

		// Navigate to the edit campaign page for the PMAX campaign with the highest spending.
		const editCampaignUrl = getEditCampaignUrl( id, 'asset-group' );
		getHistory().push( editCampaignUrl );
	};

	return (
		<Notice
			className="gla-pmax-improve-assets-banner"
			status="info"
			isDismissible={ true }
			onRemove={ dismissBanner }
		>
			<p className="gla-pmax-improve-assets-banner__text">
				{ sprintf(
					// translators: %s: The PMAX campaign name with the highest spending.
					__(
						'Unlock more sales for your campaign, %s, by focusing on improving your campaign assets.Better assets directly increase your ad strength, allowing for a wider variety of ad combinations to be shown across Google.',
						'google-listings-and-ads'
					),
					name
				) }
			</p>

			<div className="gla-pmax-improve-assets-banner__actions">
				<AppButton onClick={ handleOnImproveAssets } isSecondary>
					{ __( 'Improve Assets', 'google-listings-and-ads' ) }
				</AppButton>

				<AppButton isTertiary onClick={ dismissBanner }>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</div>
		</Notice>
	);
};

export default PMaxImproveAssetsBanner;
