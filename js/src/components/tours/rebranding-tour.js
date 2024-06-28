/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { TourKit } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useTour from '.~/hooks/useTour';
import './rebrading-tour.scss';

const TOUR_ID = 'rebranding-tour';

/**
 * Renders the tour for notifying about the new extension rebranding
 */
export default function RebrandingTour() {
	const { tourChecked, setTourChecked } = useTour( TOUR_ID );

	if ( tourChecked ) {
		return null;
	}

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.toplevel_page_woocommerce-marketing .current',
				},
				meta: {
					heading: (
						<div className="gla-rebranding-tour__heading">
							{ __(
								'New name, same great solution',
								'google-listings-and-ads'
							) }
						</div>
					),
					descriptions: {
						desktop: (
							<>
								{ createInterpolateElement(
									__(
										'Google Listings & Ads is now <strong>Google for WooCommerce</strong>.',
										'google-listings-and-ads'
									),
									{ strong: <strong /> }
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
