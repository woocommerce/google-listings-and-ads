/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { TourKit, Pill } from '@woocommerce/components';
import GridiconTrending from 'gridicons/dist/trending';

/**
 * Internal dependencies
 */
import { CAMPAIGN_TYPE_PMAX } from '~/constants';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useTour from '~/hooks/useTour';
import './campaign-assets-tour.scss';

const TOUR_ID = 'dashboard-feature--campaign-assets';
const DESKTOP_CSS_SELECTOR =
	'.gla-all-programs-table-card .gla-campaign-edit-button';

/**
 * Renders the tour for notifying the new feature of campaign assets
 * if its flag is not yet set to hidden.
 */
export default function CampaignAssetsTour() {
	const { data: adsCampaignsData } = useAdsCampaigns();
	const pmaxCampaigns = adsCampaignsData.filter(
		( { type } ) => type === CAMPAIGN_TYPE_PMAX
	);
	const { tourChecked, setTourChecked } = useTour( TOUR_ID );

	if ( tourChecked || ! pmaxCampaigns.length ) {
		return null;
	}

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: DESKTOP_CSS_SELECTOR,
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
