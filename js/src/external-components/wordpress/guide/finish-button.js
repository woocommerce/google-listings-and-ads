/**
 * This file was cloned from @wordpress/components 12.0.8
 * https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/guide/finish-button.js
 *
 * To meet the requirement of
 * https://github.com/woocommerce/google-listings-and-ads/issues/555
 */
/**
 * External dependencies
 */
import { useRef, useLayoutEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';

export default function FinishButton( props ) {
	const ref = useRef();

	// Focus the button on mount if nothing else is focused. This prevents a
	// focus loss when the 'Next' button is swapped out.
	useLayoutEffect( () => {
		const { ownerDocument } = ref.current;
		const { activeElement, body } = ownerDocument;

		if ( ! activeElement || activeElement === body ) {
			ref.current.focus();
		}
	}, [] );

	return <AppButton { ...props } ref={ ref } />;
}
