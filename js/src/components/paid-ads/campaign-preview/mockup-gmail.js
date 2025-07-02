/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SearchBar from './components/search-bar';
import ProductBanner from './components/product-banner';
import Placeholder from './components/placeholder';
import gmailLogoURL from '~/images/campaign-preview/gmail-logo.svg';

function MailItem() {
	return (
		<div className="gla-ads-mockup__mail-item">
			<Placeholder stroke="thinner" color="gray-200" width="65" />
			<Placeholder stroke="thinner" color="gray-200" />
			<Placeholder stroke="thinner" width="122" />
		</div>
	);
}

/**
 * @typedef { import("./index.js").AdPreviewData } AdPreviewData
 */

/**
 * Renders an ad preview mockup for Gmail.
 *
 * @param {Object} props React props.
 * @param {AdPreviewData} props.product Data for compositing ad preview mockups.
 */
function MockupGmail( { product }, ref ) {
	return (
		<div ref={ ref } className="gla-ads-mockup gla-ads-mockup-gmail">
			<div className="gla-ads-mockup__gmail-header">
				<img
					height="15"
					src={ gmailLogoURL }
					alt={ __( 'Gmail Logo', 'google-listings-and-ads' ) }
				/>
				<SearchBar hideMenu />
			</div>
			<ProductBanner product={ product } />
			<MailItem />
			<MailItem />
			<MailItem />
			<MailItem />
			<MailItem />
		</div>
	);
}

MockupGmail.displayName = 'MockupGmail';

export default forwardRef( MockupGmail );
