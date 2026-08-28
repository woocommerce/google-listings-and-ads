/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import GridiconExternal from 'gridicons/dist/external';

/**
 * Internal dependencies
 */
import Placeholder from './components/placeholder';
import ScaledText from './components/scaled-text';
import ProductCover from './components/product-cover';
import youTubeLogoURL from '~/images/campaign-preview/youtube-logo.svg';

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for YouTube.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
function MockupYouTube( { product }, ref ) {
	return (
		<div className="gla-ads-mockup" ref={ ref }>
			<div className="gla-ads-mockup__youtube-header">
				<img
					alt={ __( 'YouTube Logo', 'google-listings-and-ads' ) }
					height="16"
					src={ youTubeLogoURL }
				/>
			</div>
			<div className="gla-ads-mockup__youtube-product">
				<ProductCover product={ product } />
				<div className="gla-ads-mockup__youtube-learn-more-row">
					<div>
						<ScaledText color="blue" size="smaller">
							{ __( 'LEARN MORE', 'google-listings-and-ads' ) }
						</ScaledText>
					</div>
					<GridiconExternal size={ 10 } />
				</div>
				<div className="gla-ads-mockup__youtube-product-info">
					<ScaledText color="gray-800" size="larger">
						{ product.title }
					</ScaledText>
					<Placeholder />
					<Placeholder width="135" />
					<ScaledText size="smaller" adBadge>
						{ product.shopName }
					</ScaledText>
				</div>
			</div>
		</div>
	);
}

MockupYouTube.displayName = 'MockupYouTube';

export default forwardRef( MockupYouTube );
