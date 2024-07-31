/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { TourKit, Pill } from '@woocommerce/components';
import GridiconTrending from 'gridicons/dist/trending';

/**
 * Internal dependencies
 */
import useTour from '.~/hooks/useTour';
import './campaign-assets-tour.scss';

const TOUR_ID = 'dashboard-feature--campaign-assets';

/**
 * Renders the tour for notifying the new feature of campaign assets
 * if its flag is not yet set to hidden.
 *
 * @param {Object} props React props
 * @param {string} props.referenceElementCssSelector The CSS selector to find the first DOM to render this tour nearby.
 */
export default function CampaignAssetsTour( { referenceElementCssSelector } ) {
	const { tourChecked, setTourChecked } = useTour( TOUR_ID );

	if ( tourChecked ) {
		return null;
	}

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: referenceElementCssSelector,
				},
				meta: {
					heading: (
						<div className="gla-campaign-assets-tour__heading">
							<GridiconTrending />
							{ __(
								'Optimize your campaign',
								'google-listings-and-ads'
							) }
							<Pill>
								{ _x(
									'New',
									'A highlighting label behind the heading of the new feature',
									'google-listings-and-ads'
								) }
							</Pill>
						</div>
					),
					descriptions: {
						desktop: (
							<>
								{ __(
									'Add images, headlines, and descriptions to drive better engagement and more sales.',
									'google-listings-and-ads'
								) }
								<br />
								<br />
								{ __(
									'Edit your campaign to explore this new feature.',
									'google-listings-and-ads'
								) }
							</>
						),
					},
				},
			},
		],
		options: {
			classNames: 'gla-admin-page,gla-campaign-assets-tour',
			effects: { overlay: false },
		},
		placement: 'top',
		closeHandler: () => setTourChecked( true ),
	};

	return <TourKit config={ config } />;
}
