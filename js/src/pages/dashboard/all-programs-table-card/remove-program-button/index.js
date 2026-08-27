/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import RemoveProgramModal from './remove-program-modal';

const RemoveProgramButton = ( props ) => {
	const { programId } = props;
	const [ isOpen, setOpen ] = useState( false );

	const handleClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<>
			<AppButton onClick={ handleClick } isDestructive isLink>
				{ __( 'Remove', 'google-listings-and-ads' ) }
			</AppButton>
			{ isOpen && (
				<RemoveProgramModal
					onRequestClose={ handleModalRequestClose }
					programId={ programId }
				/>
			) }
		</>
	);
};

export default RemoveProgramButton;
