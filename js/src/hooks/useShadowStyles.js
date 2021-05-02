/**
 * External dependencies
 */
import { useEffect, useRef } from 'react';

/**
 * Adds encapsulated styles to a given element.
 * Adds given inline CSS or array of constructed stylesheets to element's shadow root.
 *
 * @param {string} inlineStyles Styles to be addes inline.
 * @param {Array<CSSStyleSheet> | null} styleSheets Array of (constructed) style sheets to be adopted.
 * @return {Object} React ref for a HTMLElement to be styled.
 */
export default function useShadowStyles(
	inlineStyles = '',
	styleSheets = null
) {
	const shadowHost = useRef( null );
	useEffect( () => {
		if ( shadowHost.current ) {
			let shadowRoot = shadowHost.current.shadowRoot;
			if ( ! shadowRoot ) {
				shadowRoot = shadowHost.current.attachShadow( {
					mode: 'open',
				} );
			}
			shadowRoot.innerHTML = inlineStyles
				? `<style>${ inlineStyles }</style><slot></slot>`
				: `<slot><slot>`;
			if ( styleSheets && styleSheets.length > 0 ) {
				shadowRoot.adoptedStyleSheets = styleSheets;
			}
		}
	}, [ shadowHost, inlineStyles, styleSheets ] );

	return shadowHost;
}
