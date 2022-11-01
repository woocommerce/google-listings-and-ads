/**
 * External dependencies
 */
import GridiconLocation from 'gridicons/dist/location';

/**
 * Internal dependencies
 */
import SearchBar from './components/search-bar';
import ProductBanner from './components/product-banner';
import mapBackgroundURL from './images/map-background.png';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Map.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function MockupMap( { product } ) {
	return (
		<div
			className="gla-ads-mockup gla-ads-mockup-map"
			style={ { backgroundImage: `url(${ mapBackgroundURL })` } }
		>
			<SearchBar />
			<GridiconLocation size={ 45 } />
			<ProductBanner product={ product } />
		</div>
	);
}
