/**
 * External dependencies
 */
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
 * @param {string} props.id The Popover’s ID
 * @param {Array<JSX.Element>} props.children The Popover’s content
 * @fires gla_tooltip_viewed with the given `id`.
 */
const HelpPopover = ( { id, children } ) => {
	const [ showPopover, setShowPopover ] = useState( false );

	const handleButtonClick = () => {
		setShowPopover( true );
		recordEvent( 'gla_tooltip_viewed', {
			id,
		} );
	};

	const handlePopoverClose = () => {
		setShowPopover( false );
	};

	return (
		<span className="help-popover">
			<button onClick={ handleButtonClick }>
				<GridiconHelpOutline size={ 16 }></GridiconHelpOutline>
			</button>
			{ showPopover && (
				<Popover
					focusOnMount="container"
					onClose={ handlePopoverClose }
				>
					{ children }
				</Popover>
			) }
		</span>
	);
};

export default HelpPopover;
