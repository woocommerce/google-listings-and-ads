/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

/**
 * Sets the browser tab title of a main page, in the format the wc-admin header uses,
 * e.g. "Dashboard ‹ Google for WooCommerce ‹ Marketing ‹ WooCommerce ‹ My Store — WooCommerce".
 *
 * The wc-admin header sets the title on the other wc-admin pages. The main pages turn
 * that header off, so they set it here.
 *
 * @param {string} [sectionTitle] Title of the current section. Nothing is set without it.
 */
const useDocumentTitle = ( sectionTitle ) => {
	useEffect( () => {
		if ( ! sectionTitle ) {
			return;
		}

		const woocommerceTranslation =
			getSetting( 'admin' )?.woocommerceTranslation ||
			__( 'WooCommerce', 'google-listings-and-ads' );
		const sections = [
			sectionTitle,
			__( 'Google for WooCommerce', 'google-listings-and-ads' ),
			__( 'Marketing', 'google-listings-and-ads' ),
			woocommerceTranslation,
		];

		document.title = decodeEntities(
			sprintf(
				// translators: 1: the current page, from the most specific to the least, separated by "‹". 2: the site title.
				__( '%1$s ‹ %2$s — WooCommerce', 'google-listings-and-ads' ),
				sections.join( ' ‹ ' ),
				getSetting( 'siteTitle', '' )
			)
		);
	}, [ sectionTitle ] );
};

export default useDocumentTitle;
