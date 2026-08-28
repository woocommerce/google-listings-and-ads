/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ScaledText from './components/scaled-text';
import SearchBar from './components/search-bar';
import ShopLogo from './components/shop-logo';
import googleLogoURL from '~/images/logo/google-logo.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Search.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
function MockupSearch( { product }, ref ) {
	return (
		<div className="gla-ads-mockup gla-ads-mockup-search" ref={ ref }>
			<div className="gla-ads-mockup__search-header">
				<img
					alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
					height="22"
					src={ googleLogoURL }
				/>
			</div>
			<SearchBar hideMenu />
			<div className="gla-ads-mockup__search-keywords">
				<Placeholder color="gray-500" stroke="thicker" width="30" />
				<Placeholder stroke="thicker" width="42" />
				<Placeholder stroke="thicker" width="32" />
				<Placeholder stroke="thicker" width="45" />
				<Placeholder color="gray-500" stroke="thinner" width="30" />
			</div>
			<div className="gla-ads-mockup__search-card">
				<div className="gla-ads-mockup__search-card-header">
					<ScaledText size="smaller" adBadge>
						{ product.shopUrl }
					</ScaledText>
					<Placeholder color="blue" stroke="thinner" width="79" />
				</div>
				<Flex align="stretch">
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
					<Placeholder color="gray-400" stroke="thinner" width="79" />
					<Placeholder color="gray-300" stroke="thinner" />
					<Placeholder width="122" />
					<Placeholder width="108" />
					<Placeholder width="100" />
					<Placeholder width="55" />
				</div>
			</div>
			<div className="gla-ads-mockup__search-card">
				<div className="gla-ads-mockup__search-card-placeholders">
					<Placeholder color="gray-400" stroke="thinner" width="79" />
				</div>
			</div>
		</div>
	);
}

MockupSearch.displayName = 'MockupSearch';

export default forwardRef( MockupSearch );
