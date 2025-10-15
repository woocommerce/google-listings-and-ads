/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { TourKit } from '@woocommerce/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useTour from '~/hooks/useTour';
import { getSettingsUrl } from '~/utils/urls';
import { TOUR_ID as CAMPAIGN_ASSETS_TOUR_ID } from './campaign-assets-tour';
import { TOUR_ID as REBRANDING_TOUR_ID } from './rebranding-tour';
import { recordGlaEvent } from '~/utils/tracks';
import './google-tag-gateway-tour.scss';

const TOUR_ID = 'google-tag-gateway-tour';
const CONTEXT = 'google_tag_gateway_tour';

/**
 * When the tour is shown.
 *
 * @event gla_google_tag_gateway_tour_shown
 * @property {string} context The tour context, e.g. "google_tag_gateway_tour"
 */

/**
 * When the tour is closed.
 *
 * @event gla_google_tag_gateway_tour_close_button_click
 * @property {string} context The tour context, e.g. "google_tag_gateway_tour"
 * @property {string} source The source of the close event, e.g. "done-btn" | "close-btn" | "skip-btn"
 */

/**
 * Renders the tour for notifying about the new Google Tag Gateway feature.
 * @fires gla_google_tag_gateway_tour_shown with `{ context: "google_tag_gateway_tour" }`
 * @fires gla_google_tag_gateway_tour_close_button_click with `{ context: "google_tag_gateway_tour", source: "done-btn" | "close-btn" | "skip-btn" }`
 */
export default function GoogleTagGatewayTour() {
	const {
		tourChecked: campaignAssetsTourChecked,
		hasFinishedResolution: hasResolvedCampaignAssetsTour,
	} = useTour( CAMPAIGN_ASSETS_TOUR_ID );
	const {
		tourChecked: rebrandingTourChecked,
		hasFinishedResolution: hasResolvedRebrandingTour,
	} = useTour( REBRANDING_TOUR_ID );
	const { tourChecked, setTourChecked } = useTour( TOUR_ID );

	const tourCheckedRefs = useRef();

	if (
		! tourCheckedRefs.current &&
		hasResolvedRebrandingTour &&
		hasResolvedCampaignAssetsTour
	) {
		tourCheckedRefs.current = {
			campaignAssets: campaignAssetsTourChecked,
			rebranding: rebrandingTourChecked,
		};
	}

	if (
		tourChecked ||
		! tourCheckedRefs.current.campaignAssets ||
		! tourCheckedRefs.current.rebranding
	) {
		return null;
	}

	if (
		tourChecked ||
		! campaignAssetsTourChecked ||
		! rebrandingTourChecked
	) {
		return null;
	}

	const config = {
		steps: [
			{
				referenceElements: {
					desktop:
						'.app-tab-nav__tabs-item[aria-controls="settings-view"]',
				},
				meta: {
					heading: __(
						'Improve your measurement accuracy',
						'google-listings-and-ads'
					),
					descriptions: {
						desktop: __(
							'Enable Google Tag Gateway in settings to recover more signals and boost your campaign performance.',
							'google-listings-and-ads'
						),
					},
					primaryButton: {
						text: __( 'Go to settings', 'google-listings-and-ads' ),
					},
					skipButton: {
						text: __( 'Cancel', 'google-listings-and-ads' ),
						isVisible: true,
					},
				},
			},
		],
		options: {
			classNames: 'gla-admin-page,gla-google-tag-gateway-tour',
			effects: { overlay: false },
			callbacks: {
				onStepView: () => {
					recordGlaEvent( 'gla_google_tag_gateway_tour_shown', {
						context: CONTEXT,
					} );
				},
			},
		},
		placement: 'bottom-end',
		closeHandler: ( steps, currentStepIndex, source ) => {
			setTourChecked( true );

			recordGlaEvent( 'gla_google_tag_gateway_tour_close_button_click', {
				context: CONTEXT,
				source,
			} );

			if ( source === 'done-btn' ) {
				getHistory().push( getSettingsUrl() );
			}
		},
	};

	return <TourKit config={ config } />;
}
