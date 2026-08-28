/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import GridiconChevronRight from 'gridicons/dist/chevron-right';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ProductCover from './components/product-cover';
import adCornerButtonsImageURL from '~/images/campaign-preview/ad-corner-buttons-image.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Display Network.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
function MockupDisplay( { product }, ref ) {
	return (
		<div className="gla-ads-mockup gla-ads-mockup-display" ref={ ref }>
			<div className="gla-ads-mockup__display-placeholders">
				<Placeholder color="gray-300" stroke="thinner" />
				<Placeholder color="gray-300" stroke="thinner" width="146" />
				<Placeholder color="gray-300" stroke="thinner" width="149" />
				<Placeholder color="gray-300" stroke="thinner" width="135" />
			</div>
			<div className="gla-ads-mockup__display-product">
				<div className="gla-ads-mockup__display-product-locator">
					<ProductCover product={ product } />
					<img
						alt={ __(
							'Simulated the info and close buttons at the corner of a Google ad',
							'google-listings-and-ads'
						) }
						className="gla-ads-mockup__display-corner-buttons"
						src={ adCornerButtonsImageURL }
					/>
					<div className="gla-ads-mockup__display-chevron-button">
						<GridiconChevronRight size={ 16 } />
					</div>
				</div>
				<Placeholder color="gray-500" stroke="thinner" />
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

MockupDisplay.displayName = 'MockupDisplay';

export default forwardRef( MockupDisplay );
