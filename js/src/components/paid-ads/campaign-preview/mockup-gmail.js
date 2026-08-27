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
			<Placeholder color="gray-200" stroke="thinner" width="65" />
			<Placeholder color="gray-200" stroke="thinner" />
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
		<div className="gla-ads-mockup gla-ads-mockup-gmail" ref={ ref }>
			<div className="gla-ads-mockup__gmail-header">
				<img
					alt={ __( 'Gmail Logo', 'google-listings-and-ads' ) }
					height="15"
					src={ gmailLogoURL }
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
