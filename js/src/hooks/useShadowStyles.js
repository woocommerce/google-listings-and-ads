/**
 * External dependencies
 */
import { useEffect } from 'react';

/**
 * Adds encapsulated styles to a given element.
 * Adds given inline CSS or array of constructed stylesheets to element's shadow root.
 *
 * @param {HTMLElement} shadowHost Reference to HTMLElement to be styled.
 * @param {string} inlineStyles Styles to be addes inline.
 * @param {Array<CSSStyleSheet> | null} styleSheets Array of (constructed) style sheets to be adopted.
 */
export default function useShadowStyles(
	shadowHost,
	inlineStyles = '',
	styleSheets = null
) {
	useEffect( () => {
		if ( shadowHost.current ) {
			if ( ! shadowHost.current.shadowRoot ) {
				const shadowRoot = shadowHost.current.attachShadow( {
					mode: 'open',
				} );
				shadowRoot.innerHTML = `<style>${ inlineStyles }</style><slot></slot>`;
				if ( styleSheets && styleSheets.length > 0 ) {
					shadowRoot.adoptedStyleSheets = styleSheets;
				}
			}
		}
	}, [ shadowHost, inlineStyles, styleSheets ] );
}
