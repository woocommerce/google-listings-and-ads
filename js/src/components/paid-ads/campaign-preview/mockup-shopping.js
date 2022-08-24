/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ScaledText from './components/scaled-text';
import ProductCover from './components/product-cover';
import googleShoppingLogoURL from './images/google-shopping-logo.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Shopping.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function MockupShopping( { product } ) {
	return (
		<div className="gla-ads-mockup">
			<div className="gla-ads-mockup__tab-list">
				<Placeholder thicker />
				<Placeholder thicker />
				<div className="gla-ads-mockup__tab-item-with-logo">
					<img
						height="30"
						src={ googleShoppingLogoURL }
						alt={ __(
							'Google Shopping Logo',
							'google-listings-and-ads'
						) }
					/>
					<Placeholder thinner color="gray-500" />
				</div>
				<Placeholder thicker />
			</div>
			<div className="gla-ads-mockup__shopping-product">
				<ProductCover product={ product } />
				<div className="gla-ads-mockup__shopping-product-info">
					<ScaledText larger color="gray-800">
						{ product.title }
					</ScaledText>
					<ScaledText color="gray-800">{ product.price }</ScaledText>
					<ScaledText smaller>{ product.shopName }</ScaledText>
				</div>
			</div>
		</div>
	);
}
