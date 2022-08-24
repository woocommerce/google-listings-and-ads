/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconExternal from 'gridicons/dist/external';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ScaledText from './components/scaled-text';
import ProductCover from './components/product-cover';
import youTubeLogoURL from './images/youtube-logo.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for YouTube.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
export default function MockupYouTube( { product } ) {
	return (
		<div className="gla-ads-mockup">
			<div className="gla-ads-mockup__youtube-header">
				<img
					height="16"
					src={ youTubeLogoURL }
					alt={ __( 'YouTube Logo', 'google-listings-and-ads' ) }
				/>
			</div>
			<div className="gla-ads-mockup__youtube-product">
				<ProductCover product={ product } />
				<div className="gla-ads-mockup__youtube-learn-more-row">
					<div>
						<ScaledText smaller color="blue">
							{ __( 'LEARN MORE', 'google-listings-and-ads' ) }
						</ScaledText>
					</div>
					<GridiconExternal size={ 10 } />
				</div>
				<div className="gla-ads-mockup__youtube-product-info">
					<ScaledText larger color="gray-800">
						{ product.title }
					</ScaledText>
					<Placeholder thicker />
					<Placeholder thicker width="135" />
					<ScaledText smaller adBadge>
						{ product.shopName }
					</ScaledText>
				</div>
			</div>
		</div>
	);
}
