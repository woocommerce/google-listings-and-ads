/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconChevronRight from 'gridicons/dist/chevron-right';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ProductCover from './components/product-cover';
import adCornerButtonsImageURL from './images/ad-corner-buttons-image.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Display Network.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function MockupDisplay( { product } ) {
	return (
		<div className="gla-ads-mockup gla-ads-mockup-display">
			<div className="gla-ads-mockup__display-placeholders">
				<Placeholder thinner color="gray-300" />
				<Placeholder thinner color="gray-300" width="146" />
				<Placeholder thinner color="gray-300" width="149" />
				<Placeholder thinner color="gray-300" width="135" />
			</div>
			<div className="gla-ads-mockup__display-product">
				<div className="gla-ads-mockup__display-product-locator">
					<ProductCover product={ product } />
					<img
						className="gla-ads-mockup__display-corner-buttons"
						src={ adCornerButtonsImageURL }
						alt={ __(
							'Simulated the info and close buttons at the corner of a Google ad',
							'google-listings-and-ads'
						) }
					/>
					<div className="gla-ads-mockup__display-chevron-button">
						<GridiconChevronRight size={ 16 } />
					</div>
				</div>
				<Placeholder thinner color="gray-500" />
			</div>
			<div className="gla-ads-mockup__display-placeholders">
				<Placeholder />
				<Placeholder width="151" />
				<Placeholder width="135" />
				<Placeholder />
				<Placeholder />
				<Placeholder width="151" />
			</div>
		</div>
	);
}
