/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useTour from '~/hooks/useTour';
import { getSettingsUrl } from '~/utils/urls';
import { recordGlaEvent } from '~/utils/tracks';
import './youtube-shopping-tour.scss';

const TOUR_ID = 'youtube-shopping-tour';
const CONTEXT = 'youtube_shopping_tour';

/**
 * When the tour is shown.
 *
 * @event gla_youtube_shopping_tour_shown
 * @property {string} context The tour context, e.g. "youtube_shopping_tour"
 */

/**
 * When the tour is closed.
 *
 * @event gla_youtube_shopping_tour_close_button_click
 * @property {string} context The tour context, e.g. "youtube_shopping_tour"
 * @property {string} source The source of the close event, e.g. "done-btn" | "close-btn" | "skip-btn"
 */

/**
 * Renders the tour for notifying about the new YouTube Shopping feature.
 * @fires gla_youtube_shopping_tour_shown with `{ context: "youtube_shopping_tour" }`
 * @fires gla_youtube_shopping_tour_close_button_click with `{ context: "youtube_shopping_tour", source: "done-btn" | "close-btn" | "skip-btn" }`
 */
export default function YouTubeShoppingTour() {
	const { tourChecked, setTourChecked } = useTour( TOUR_ID );

	if ( tourChecked ) {
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
						'List your products on YouTube',
						'google-listings-and-ads'
					),
					descriptions: {
						desktop: __(
							'Link your channel to your Woo store to list products on YouTube and track sales from your videos.',
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
			classNames: 'gla-admin-page,gla-youtube-shopping-tour',
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

			recordGlaEvent( 'gla_youtube_shopping_tour_close_button_click', {
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
