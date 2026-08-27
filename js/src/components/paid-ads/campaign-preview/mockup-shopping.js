/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ScaledText from './components/scaled-text';
import ProductCover from './components/product-cover';
import googleShoppingLogoURL from '~/images/campaign-preview/google-shopping-logo.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Google Shopping.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
function MockupShopping( { product }, ref ) {
	return (
		<div className="gla-ads-mockup" ref={ ref }>
			<div className="gla-ads-mockup__tab-list">
				<Placeholder stroke="thicker" />
				<Placeholder stroke="thicker" />
				<div className="gla-ads-mockup__tab-item-with-logo">
					<img
						alt={ __(
							'Google Shopping Logo',
							'google-listings-and-ads'
						) }
						height="30"
						src={ googleShoppingLogoURL }
					/>
					<Placeholder color="gray-500" stroke="thinner" />
				</div>
				<Placeholder stroke="thicker" />
			</div>
			<div className="gla-ads-mockup__shopping-product">
				<ProductCover product={ product } />
				<div className="gla-ads-mockup__shopping-product-info">
					<ScaledText color="gray-800" size="larger">
						{ product.title }
					</ScaledText>
					<ScaledText color="gray-800">{ product.price }</ScaledText>
					<ScaledText size="smaller">{ product.shopName }</ScaledText>
				</div>
			</div>
		</div>
	);
}

MockupShopping.displayName = 'MockupShopping';

export default forwardRef( MockupShopping );
