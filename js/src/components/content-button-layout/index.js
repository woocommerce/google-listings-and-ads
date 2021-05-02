/**
 * Internal dependencies
 */
import useShadowStyles from '.~/hooks/useShadowStyles';
/* global CSSStyleSheet */

const sheet = new CSSStyleSheet();
sheet.replaceSync( /* css */ `:host {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: calc(var(--main-gap) / 2);
}` );

const ContentButtonLayout = ( props ) => (
	<div ref={ useShadowStyles( null, [ sheet ] ) } { ...props } />
);

export default ContentButtonLayout;
