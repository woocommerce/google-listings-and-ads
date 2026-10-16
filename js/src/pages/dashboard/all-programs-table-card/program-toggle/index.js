/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
/**
 * Internal dependencies
 */
import PauseProgramModal from './pause-program-modal';
import { useAppDispatch } from '~/data';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import AppStandaloneToggleControl from '~/components/app-standalone-toggle-control';

const ProgramToggle = ( props ) => {
	const { program } = props;
	const [ checked, setChecked ] = useState( program.active );
	const [ showModal, setShowModal ] = useState( false );
	const { updateAdsCampaign } = useAppDispatch();
	const { handleError: handleEuPoliticalDeclarationError } =
		useEuPoliticalDeclarationContext();

	const updateCampaignStatus = async ( status ) => {
		try {
			await updateAdsCampaign( program.id, { status } );
		} catch ( error ) {
			// If the campaign fails to update, revert the toggle to its previous state.
			setChecked( ( prevChecked ) => ! prevChecked );

			handleEuPoliticalDeclarationError( error );
		}
	};

	const handleChange = ( v ) => {
		if ( v === false ) {
			setShowModal( true );
			return;
		}

		setChecked( v );
		updateCampaignStatus( 'enabled' );
	};

	const handleModalRequestClose = () => {
		setShowModal( false );
	};

	const handlePauseCampaign = () => {
		setShowModal( false );
		setChecked( false );
		updateCampaignStatus( 'paused' );
	};

	return (
		<>
			<AppStandaloneToggleControl
				checked={ checked }
				onChange={ handleChange }
			/>
			{ showModal && (
				<PauseProgramModal
					programId={ program.id }
					onPauseCampaign={ handlePauseCampaign }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default ProgramToggle;
