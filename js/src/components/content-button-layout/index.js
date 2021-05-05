/**
 * Internal dependencies
 */
import useShadowStyles, { css } from '.~/hooks/useShadowStyles';

const sheet = css`
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: calc(var(--main-gap) / 2);
`;

const ContentButtonLayout = ( props ) => (
	<div ref={ useShadowStyles( null, [ sheet ] ) } { ...props } />
);

export default ContentButtonLayout;
