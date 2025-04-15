/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useTour from '~/hooks/useTour';
import PriceBenchmarkGraphic from '~/images/price-benchmark/tour.svg';

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
						<>
							<img
								className="gla-price-benchmark-tour__graphic"
								src={ PriceBenchmarkGraphic }
								alt={ __(
									'Graphic showing the price benchmark and suggestions feature',
									'google-listings-and-ads'
								) }
							/>
							{ createInterpolateElement(
								__(
									'<span>Price Benchmark & Suggestions</span>',
									'google-listings-and-ads'
								),
								{
									span: (
										<span className="gla-tour-highlight" />
									),
								}
							) }
						</>
					),
					descriptions: {
						desktop: (
							<>
								{ createInterpolateElement(
									__(
										'<p>This report includes a competitive pricing analysis, price recommendations, and insights to help you compare against competitors and accelerate your sales growth.</p>',
										'google-listings-and-ads'
									),
									{
										p: <p></p>,
									}
								) }
								{ createInterpolateElement(
									__(
										"<p>The <strong>Effectiveness</strong> grade reveals which products will benefit most from price adjustments. 'High' Effectiveness ratings signify the suggested sale prices are predicted to drive the greatest performance increases.</p>",
										'google-listings-and-ads'
									),
									{
										p: <p></p>,
										strong: <strong />,
									}
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
			classNames: 'gla-price-benchmark-tour',
			effects: { overlay: true },
		},
		placement: 'bottom-start',
		closeHandler: () => setTourChecked( true ),
	};

	return <TourKit config={ config } />;
}
