/**
 * External dependencies
 */
import { useRef } from 'react';
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

const ContentButtonLayout = ( props ) => {
	const shadowHost = useRef( null );
	useShadowStyles( shadowHost, styles );

	return (
		<div ref={ shadowHost } { ...props }>
			{ props.children }
		</div>
	);
};

export default ContentButtonLayout;
