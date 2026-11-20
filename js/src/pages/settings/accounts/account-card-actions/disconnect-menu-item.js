/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';

/**
 * Renders the disconnect menu item.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onClose Closes the dropdown menu.
 * @param {() => void} props.onDisconnect Callback to invoke when the "Disconnect" menu item is clicked.
 * @return {JSX.Element} The disconnect menu item.
 */
const DisconnectMenuItem = ( { onClose, onDisconnect } ) => {
	const handleClick = () => {
		onClose();
		onDisconnect();
	};

	return (
		<MenuItem onClick={ handleClick } isDestructive>
			{ __( 'Disconnect', 'google-listings-and-ads' ) }
		</MenuItem>
	);
};

export default DisconnectMenuItem;
