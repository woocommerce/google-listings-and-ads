/**
 * External dependencies
 */
import GridiconSearch from 'gridicons/dist/search';

/**
 * Internal dependencies
 */
import Placeholder from './placeholder';

/**
 * @typedef { import("../index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders a search bar for the ad preview mockups.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.hideMenu=false] Whether to hide the menu icon.
 */
export default function SearchBar( { hideMenu = false } ) {
	return (
		<div className="gla-ads-mockup__search-bar">
			<GridiconSearch size={ 13 } />
			<div
				className="gla-ads-mockup__search-bar-menu"
				hidden={ hideMenu }
			>
				<Placeholder stroke="thinnest" color="gray-400" />
				<Placeholder stroke="thinnest" color="gray-400" />
				<Placeholder stroke="thinnest" color="gray-400" />
			</div>
		</div>
	);
}
