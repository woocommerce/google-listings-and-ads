/**
 * Utility to define scoped styles using a custom element with a shadow root.
 */
/* global CSSStyleSheet, HTMLElement */
/**
 * A template literal tag to create stylesheet objects.
 *
 * It's overly simplify, in future we may introduce more features, like:
 * - Safari support
 * - lazy constructing - not to create object untill the first instance is used
 * - safe rules nesting
 * - etc.
 *
 * @param {TemplateStringsArray} strings
 * @param  {Array} values
 * @return {CSSStyleSheet} Constructed stylesheet.
 */
export const css = ( strings, ...values ) => {
	const cssText =
		strings.length === 1
			? strings[ 0 ]
			: values.reduce(
					( accumulator, value, index ) =>
						accumulator + value + strings[ index + 1 ],
					strings[ 0 ]
			  );
	const sheet = new CSSStyleSheet();
	sheet.replaceSync( cssText );
	return sheet;
};

/**
 * Creates a custom element that will act as the host for given styles.
 *
 * @param {string} name Valid custom element name.
 * @param {Array<CSSStyleSheet>} styles Style sheets to be adoptes by the host.
 */
export const defineShadowStylesHost = ( name, styles ) => {
	window.customElements.define(
		name,
		class extends HTMLElement {
			constructor() {
				super();
				const shadowRoot = this.attachShadow( { mode: 'open' } );
				shadowRoot.innerHTML = `<slot></slot>`;
				shadowRoot.adoptedStyleSheets = styles;
			}
		}
	);
};
