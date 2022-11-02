/**
 * @typedef { import("../index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders a product cover for the ad preview mockups.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function ProductCover( { product } ) {
	const style = { backgroundImage: `url(${ product.coverUrl })` };
	return <div className="gla-ads-mockup__product-cover" style={ style } />;
}
