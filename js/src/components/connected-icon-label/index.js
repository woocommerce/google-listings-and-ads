/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconCheckmarkCircle from 'gridicons/dist/checkmark-circle';

/**
 * Internal dependencies
 */
import { css, defineShadowStylesHost } from '.~/utils/defineShadowStylesHost';

export const ConnectedIconLabelStyle = css`
	:host {
		display: inline-flex;
		fill: currentcolor;
		color: #23a713;
		gap: var( --wp-grid-gap, 4px );
		align-items: center;

		direction: row;
		justify-content: space-between;
	}
	::slotted( svg ) {
		display: block;
	}
`;

defineShadowStylesHost( 'gla-connected-icon-label', [
	ConnectedIconLabelStyle,
] );

const ConnectedIconLabel = ( { className, ...props } ) => (
	<gla-connected-icon-label class={ className } { ...props }>
		<GridiconCheckmarkCircle />
		{ __( 'Connected', 'google-listings-and-ads' ) }
	</gla-connected-icon-label>
);

export default ConnectedIconLabel;
