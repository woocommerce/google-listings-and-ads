/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';
import GridIconSpeaker from 'gridicons/dist/speaker';

/**
 * Internal dependencies
 */
import useTour from '../../hooks/useTour';
import './rebrading-tour.scss';

const TOUR_ID = 'rebranding-tour';

/**
 * Renders the tour for notifying about the new extension rebranding
 */
export default function RebrandingTour() {
	const { tourChecked, setTourChecked } = useTour( TOUR_ID );

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.toplevel_page_woocommerce-marketing .current',
				},
				meta: {
					heading: (
						<div className="gla-rebranding-tour__heading">
							<GridIconSpeaker />
							{ __( 'Heads up', 'google-listings-and-ads' ) }
						</div>
					),
					descriptions: {
						desktop: (
							<>
								{ __(
									'"Google Listings and Ads" is now "Google for WooCommerce".',
									'google-listings-and-ads'
								) }
							</>
						),
					},
				},
			},
		],
		options: {
			classNames: 'gla-admin-page,gla-rebranding-tour',
			effects: { overlay: false },
		},
		placement: 'top',
		closeHandler: () => setTourChecked( true ),
	};

	return <TourKit config={ config } />;
}
