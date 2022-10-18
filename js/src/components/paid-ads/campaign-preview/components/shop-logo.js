/**
 * @typedef { import("../index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders a shop logo for the ad preview mockups.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function ShopLogo( { product } ) {
	const style = { backgroundImage: `url(${ product.shopLogoUrl })` };
	return <div className="gla-ads-mockup__shop-logo" style={ style } />;
}
