/**
 * External dependencies
 */
import { forwardRef } from '@wordpress/element';
import GridiconLocation from 'gridicons/dist/location';

/**
 * Internal dependencies
 */
import SearchBar from './components/search-bar';
import ProductBanner from './components/product-banner';
import mapBackgroundURL from '~/images/campaign-preview/map-background.png';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Map.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
function MockupMap( { product }, ref ) {
	return (
		<div
			ref={ ref }
			className="gla-ads-mockup gla-ads-mockup-map"
			style={ { backgroundImage: `url(${ mapBackgroundURL })` } }
		>
			<SearchBar />
			<GridiconLocation size={ 45 } />
			<ProductBanner product={ product } />
		</div>
	);
}

MockupMap.displayName = 'MockupMap';

export default forwardRef( MockupMap );
