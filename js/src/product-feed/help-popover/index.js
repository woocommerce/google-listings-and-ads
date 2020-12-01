/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import { useState } from '@wordpress/element';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import './index.scss';

const HelpPopover = ( props ) => {
	const { children } = props;
	const [ showPopover, setShowPopover ] = useState( false );

	const handleButtonClick = () => {
		setShowPopover( true );
	};

	const handlePopoverClose = () => {
		setShowPopover( false );
	};

	return (
		<div className="help-popover">
			<button onClick={ handleButtonClick }>
				<GridiconHelpOutline size={ 16 }></GridiconHelpOutline>
			</button>
			{ showPopover && (
				<Popover onClose={ handlePopoverClose }>{ children }</Popover>
			) }
		</div>
	);
};

export default HelpPopover;
