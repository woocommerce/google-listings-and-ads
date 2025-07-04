/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getEditCampaignUrl } from '~/utils/urls';
import AppButton from '~/components/app-button';
import usePmaxAssetOptimizationRecommendedCampaign from '~/hooks/usePmaxAssetOptimizationRecommendedCampaign';
import './index.scss';

/**
 * Displays a dismissible banner prompting users to improve assets for their highest-spending enabled Performance Max (PMAX) campaign.
 *
 * The banner is shown only if:
 * - There are enabled PMAX campaigns.
 * - There are relevant asset improvement recommendations.
 *
 * When dismissed, the banner will not reappear until the expiry time elapses.
 * Clicking "Improve Assets" navigates to the asset group edit page for the highest-spending PMAX campaign.
 * @param {Object} props Component properties.
 * @param {Function} props.onBannerDismissed Callback function to call when the banner is dismissed.
 *
 * @return {JSX.Element|null} The banner component, or null if not applicable.
 */
const Banner = ( { onBannerDismissed } ) => {
	const { campaign, hasFinishedResolution } =
		usePmaxAssetOptimizationRecommendedCampaign();

	if ( ! campaign || ! hasFinishedResolution ) {
		return null;
	}

	const { id, name } = campaign;

	const handleOnImproveAssets = () => {
		onBannerDismissed();

		// Navigate to the edit campaign page for the PMAX campaign with the highest spending.
		const editCampaignUrl = getEditCampaignUrl( id, 'asset-group' );
		getHistory().push( editCampaignUrl );
	};

	return (
		<Notice
			className="gla-pmax-improve-assets-banner"
			status="info"
			isDismissible={ true }
			onRemove={ onBannerDismissed }
		>
			<p className="gla-pmax-improve-assets-banner__text">
				{ sprintf(
					// translators: %s: The PMAX campaign name with the highest spending.
					__(
						'Unlock more sales for your campaign, %s, by focusing on improving your campaign assets. Better assets directly increase your ad strength, allowing for a wider variety of ad combinations to be shown across Google.',
						'google-listings-and-ads'
					),
					name
				) }
			</p>

			<div className="gla-pmax-improve-assets-banner__actions">
				<AppButton onClick={ handleOnImproveAssets } isSecondary>
					{ __( 'Improve Assets', 'google-listings-and-ads' ) }
				</AppButton>

				<AppButton isTertiary onClick={ onBannerDismissed }>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</div>
		</Notice>
	);
};

export default Banner;
