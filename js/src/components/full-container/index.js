/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Make the wrapped component display in full container.
 * It workarounds WooCommerce-admin's navigation Container component, to display the child components on full pane, without Header and StoreAlerts.
 * Actually it does not wrap children elements, but forcefully change WooCommerce-admin layout, to make `.woocommerce-layout__main` occupy the full pane.
 *
 * ## Usage
 *
 * ```jsx
 * <FullContainer>
 *     <MyComponent>
 * </FullContainer>
 * ```
 *
 * @see FullScreen
 *
 * @param {Object} props
 * @param {Array<JSX.Element>} props.children Array of react elements to be rendered.
 */
export default function FullContainer( props ) {
	const { children } = props;

	useEffect( () => {
		document.body.classList.add( 'gla-full-container' );

		return () => {
			document.body.classList.remove( 'gla-full-container' );
		};
	}, [] );

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
		}, 210 );

		return () => {
			clearTimeout( timeoutId );
			if ( wpBodyContent ) {
				wpBodyContent.style.marginTop = null;
			}
		};
	}, [] );

	return children;
}
