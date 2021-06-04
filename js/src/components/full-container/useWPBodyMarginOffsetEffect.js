/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Timeout in milliseconds to check the #wpbody margin-top.
 *
 * In https://github.com/woocommerce/woocommerce-admin/blob/95c487247416ab34eb8e492b984e2b068618e0d3/client/header/index.js#L92-L118, the timeout used is `200`.
 * We use `210` here to run after the WC-Admin code, to be a little bit safer.
 */
const timeoutInMS = 210;

/**
 * Offset the #wpbody margin-top by applying negative margin-top to its child #wpbody-content.
 *
 * #wpbody margin-top is set after a 200ms timeout in WC Admin. To counter that, we take a similar timeout approach here too.
 *
 * The code here is based on the code in https://github.com/woocommerce/woocommerce-admin/blob/95c487247416ab34eb8e492b984e2b068618e0d3/client/header/index.js#L92-L118.
 */
const useWPBodyMarginOffsetEffect = () => {
	useEffect( () => {
		const wpBodyContent = document.querySelector( '#wpbody-content' );
		if ( ! wpBodyContent ) {
			return;
		}

		const timeoutId = setTimeout( () => {
			const wpBody = document.querySelector( '#wpbody' );
			const marginTop =
				wpBody && wpBody.style.marginTop
					? `-${ wpBody.style.marginTop }`
					: null;

			wpBodyContent.style.marginTop = marginTop;
		}, timeoutInMS );

		return () => {
			clearTimeout( timeoutId );
			if ( wpBodyContent ) {
				wpBodyContent.style.marginTop = null;
			}
		};
	}, [] );
};

export default useWPBodyMarginOffsetEffect;
