/**
 * Internal dependencies
 */
import Placeholder from './placeholder';
import ScaledText from './scaled-text';
import ShopLogo from './shop-logo';

/**
 * @typedef { import("../index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders a product banner for the ad preview mockups.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function ProductBanner( { product } ) {
	return (
		<div className="gla-ads-mockup__product-banner">
			<div className="gla-ads-mockup__product-banner-info">
				<ScaledText smaller adBadge>
					{ product.shopName }
				</ScaledText>
				<Placeholder stroke="thinner" width="85" color="gray-300" />
				<Placeholder stroke="thinner" width="65" color="gray-300" />
				<Placeholder stroke="thinner" width="27" color="blue" />
			</div>
			<ShopLogo product={ product } />
		</div>
	);
}
