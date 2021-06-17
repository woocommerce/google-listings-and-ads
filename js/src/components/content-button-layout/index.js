/**
 * Internal dependencies
 */
import useShadowStyles from '.~/hooks/useShadowStyles';

const styles = /* css */ `:host {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: calc(var(--main-gap) / 2);
}`;

const ContentButtonLayout = ( props ) => (
	<div ref={ useShadowStyles( styles ) } { ...props } />
);

export default ContentButtonLayout;
