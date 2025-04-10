/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useTour from '~/hooks/useTour';
import PriceBenchmarkGraphic from '~/images/price-banchmark-tour.svg';

const TOUR_ID = 'price-benchmark-tour';

/**
 * Renders the tour for notifying the new feature of price benchmark & suggestions.
 */
export default function PriceBenchmarkTour() {
	const { tourChecked, setTourChecked } = useTour( TOUR_ID );

	if ( tourChecked ) {
		return null;
	}

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.gla-price-benchmark__card a[id="suggestions"]',
				},
				meta: {
					heading: (
						<div className="gla-price-benchmark-tour__heading">
							<img
								className="gla-price-benchmark-tour__graphic"
								src={ PriceBenchmarkGraphic }
								alt={ __(
									'Graphic showing the price benchmark and suggestions feature',
									'google-listings-and-ads'
								) }
							/>
							{ __(
								'Price Benchmark & Suggestions',
								'google-listings-and-ads'
							) }
						</div>
					),
					descriptions: {
						desktop: (
							<>
								{ __(
									'This report includes a competitive pricing analysis, price recommendations, and insights to help you compare against competitors and accelerate your sales growth.',
									'google-listings-and-ads'
								) }
								<br />
								<br />
								{ __(
									"The Effectiveness grade reveals which products will benefit most from price adjustments. 'High' Effectiveness ratings signify the suggested sale prices are predicted to drive the greatest performance increases.",
									'google-listings-and-ads'
								) }
							</>
						),
					},
					primaryButton: {
						isHidden: true,
					},
				},
			},
		],
		options: {
			classNames: 'gla-admin-page,gla-price-benchmark-tour',
			effects: { overlay: false },
		},
		placement: 'bottom-start',
		closeHandler: () => setTourChecked( true ),
	};

	return <TourKit config={ config } />;
}
