/**
 * External dependencies
 */
import classnames from 'classnames';
import { Popover } from 'extracted/@wordpress/components';
import { useState } from '@wordpress/element';
import GridiconHelpOutline from 'gridicons/dist/help-outline';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Viewing tooltip
 *
 * @event gla_tooltip_viewed
 * @property {string} id Tooltip identifier.
 */

/**
 * @param {Object} props React props
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {string} [props.id] The Popover’s ID for event tracking.
 * @param {number} [props.iconSize=16] Size of the help icon.
 * @param {Array<JSX.Element>} props.children The Popover’s content
 * @fires gla_tooltip_viewed with the given `id`.
 */
const HelpPopover = ( {
	className,
	id,
	iconSize = 16,
	children,
	...props
} ) => {
	const [ showPopover, setShowPopover ] = useState( false );

	const handleButtonClick = () => {
		setShowPopover( true );

		if ( id ) {
			recordEvent( 'gla_tooltip_viewed', { id } );
		}
	};

	const handlePopoverClose = () => {
		setShowPopover( false );
	};

	return (
		<span className={ classnames( 'help-popover', className ) }>
			<button onClick={ handleButtonClick }>
				<GridiconHelpOutline size={ iconSize }></GridiconHelpOutline>
			</button>
			{ showPopover && (
				<Popover
					focusOnMount="container"
					onClose={ handlePopoverClose }
					{ ...props }
				>
					{ children }
				</Popover>
			) }
		</span>
	);
};

export default HelpPopover;
