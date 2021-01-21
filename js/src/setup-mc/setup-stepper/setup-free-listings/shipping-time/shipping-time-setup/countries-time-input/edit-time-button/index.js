/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import EditTimeModal from './edit-time-modal';
import './index.scss';

const EditTimeButton = ( props ) => {
	const { time } = props;
	const [ isOpen, setOpen ] = useState( false );

	const handleClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<Button
				className="gla-edit-time-button"
				isTertiary
				onClick={ handleClick }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<EditTimeModal
					time={ time }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default EditTimeButton;
