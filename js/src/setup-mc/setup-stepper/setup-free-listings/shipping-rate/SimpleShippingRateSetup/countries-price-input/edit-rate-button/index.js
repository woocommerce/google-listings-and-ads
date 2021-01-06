/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import EditRateModal from './edit-rate-modal';
import './index.scss';

const EditRateButton = ( props ) => {
	const { rate } = props;
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
				className="gla-edit-rate-button"
				isTertiary
				onClick={ handleClick }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<EditRateModal
					rate={ rate }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default EditRateButton;
