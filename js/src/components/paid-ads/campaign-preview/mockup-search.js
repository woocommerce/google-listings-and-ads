/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ScaledText from './components/scaled-text';
import SearchBar from './components/search-bar';
import ShopLogo from './components/shop-logo';
import googleLogoURL from '.~/images/google-logo.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Search.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function MockupSearch( { product } ) {
	return (
		<div className="gla-ads-mockup gla-ads-mockup-search">
			<div className="gla-ads-mockup__search-header">
				<img
					height="22"
					src={ googleLogoURL }
					alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
				/>
			</div>
			<SearchBar hideMenu />
			<div className="gla-ads-mockup__search-keywords">
				<Placeholder width="30" thicker color="gray-500" />
				<Placeholder width="42" thicker />
				<Placeholder width="32" thicker />
				<Placeholder width="45" thicker />
				<Placeholder width="30" thinner color="gray-500" />
			</div>
			<div className="gla-ads-mockup__search-card">
				<div className="gla-ads-mockup__search-card-header">
					<ScaledText smaller adBadge>
						{ product.shopUrl }
					</ScaledText>
					<Placeholder thinner width="79" color="blue" />
				</div>
				<Flex justfy="space-between" align="stretch">
					<div className="gla-ads-mockup__search-card-placeholders">
						<Placeholder width="100" />
						<Placeholder width="97" />
						<Placeholder width="95" />
						<Placeholder width="99" />
						<Placeholder width="90" />
						<Placeholder width="78" />
					</div>
					<ShopLogo product={ product } />
				</Flex>
			</div>
			<div className="gla-ads-mockup__search-card">
				<div className="gla-ads-mockup__search-card-placeholders">
					<Placeholder thinner width="79" color="gray-400" />
					<Placeholder thinner color="gray-300" />
					<Placeholder width="122" />
					<Placeholder width="108" />
					<Placeholder width="100" />
					<Placeholder width="55" />
				</div>
			</div>
			<div className="gla-ads-mockup__search-card">
				<div className="gla-ads-mockup__search-card-placeholders">
					<Placeholder thinner width="79" color="gray-400" />
				</div>
			</div>
		</div>
	);
}
