/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { getHistory } from '@woocommerce/navigation';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getEditCampaignUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import AppButton from '~/components/app-button';
import useRecommendedPMaxCampaign from '~/hooks/useRecommendedPMaxCampaign';
import './index.scss';

const PMAX_ASSETS_IMPROVEMENTS_BANNER_CONTEXT =
	'pmax_assets_improvements_banner';

/**
 * When the banner is shown.
 *
 * @event gla_pmax_assets_improvements_banner_shown
 * @property {string} context The context in which the banner is shown. Set to 'pmax_assets_improvements_banner'.
 */

/**
 * When the "Improve Assets" button is clicked.
 *
 * @event gla_pmax_assets_improvements_improve_assets_clicked
 * @property {string} context The context in which the banner is shown. Set to 'pmax_assets_improvements_banner'.
 * @property {number} campaign_id The ID of the PMAX campaign for which assets are being improved.
 */

/**
 * When the banner is dismissed by clicking the "Dismiss" button or the close icon.
 *
 * @event gla_pmax_assets_improvements_dismiss_clicked
 * @property {string} context The context in which the banner was dismissed. Set to 'pmax_assets_improvements_banner'.
 * @property {number} campaign_id The ID of the PMAX campaign for which the banner was dismissed.
 */

/**
 * Displays a dismissible banner prompting users to improve assets for their highest-spending enabled Performance Max (PMAX) campaign.
 *
 * The banner is shown only if:
 * - There are enabled PMAX campaigns.
 * - There are relevant asset improvement recommendations.
 *
 * When dismissed, the banner will not reappear until the expiry time elapses.
 * Clicking "Improve Assets" navigates to the asset group edit page for the highest-spending PMAX campaign.
 *
 * @fires gla_pmax_assets_improvements_banner_shown when the banner is displayed.
 * @fires gla_pmax_assets_improvements_improve_assets_clicked when the "Improve Assets" button is clicked.
 * @fires gla_pmax_assets_improvements_dismiss_clicked when the banner is dismissed.
 *
 * @param {Object} props Component properties.
 * @param {Function} props.onBannerDismissed Callback function to call when the banner is dismissed.
 * @return {JSX.Element|null} The banner component, or null if not applicable.
 */
const Banner = ( { onBannerDismissed } ) => {
	const { campaign, hasFinishedResolution } = useRecommendedPMaxCampaign();

	useEffect( () => {
		if ( campaign && hasFinishedResolution ) {
			recordGlaEvent( 'gla_pmax_assets_improvements_banner_shown', {
				context: PMAX_ASSETS_IMPROVEMENTS_BANNER_CONTEXT,
			} );
		}
	}, [ campaign, hasFinishedResolution ] );

	if ( ! campaign || ! hasFinishedResolution ) {
		return null;
	}

	const { campaign_id, campaign_name } = campaign;

	const handleOnImproveAssets = () => {
		onBannerDismissed();

		recordGlaEvent( 'gla_pmax_assets_improvements_improve_assets_clicked', {
			context: PMAX_ASSETS_IMPROVEMENTS_BANNER_CONTEXT,
			campaign_id,
		} );

		// Navigate to the edit campaign page for the PMAX campaign with the highest spending.
		const editCampaignUrl = getEditCampaignUrl(
			campaign_id,
			'asset-group'
		);
		getHistory().push( editCampaignUrl );
	};

	const handleDismiss = () => {
		onBannerDismissed();

		recordGlaEvent( 'gla_pmax_assets_improvements_dismiss_clicked', {
			context: PMAX_ASSETS_IMPROVEMENTS_BANNER_CONTEXT,
			campaign_id,
		} );
	};

	return (
		<Notice
			className="gla-pmax-improve-assets-banner"
			isDismissible={ true }
			onRemove={ handleDismiss }
			status="info"
		>
			<p className="gla-pmax-improve-assets-banner__text">
				{ sprintf(
					// translators: %s: The PMAX campaign name with the highest spending.
					__(
						'Unlock more sales for your campaign, %s, by focusing on improving your campaign assets. Better assets directly increase your ad strength, allowing for a wider variety of ad combinations to be shown across Google.',
						'google-listings-and-ads'
					),
					campaign_name
				) }
			</p>

			<div className="gla-pmax-improve-assets-banner__actions">
				<AppButton onClick={ handleOnImproveAssets } isSecondary>
					{ __( 'Improve Assets', 'google-listings-and-ads' ) }
				</AppButton>

				<AppButton onClick={ handleDismiss } isTertiary>
					{ __( 'Dismiss', 'google-listings-and-ads' ) }
				</AppButton>
			</div>
		</Notice>
	);
};

export default Banner;
