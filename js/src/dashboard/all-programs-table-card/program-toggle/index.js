/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PauseProgramModal from './pause-program-modal';
import './index.scss';

const ProgramToggle = ( props ) => {
	const { program } = props;
	const [ checked, setChecked ] = useState( program.active );
	const [ showModal, setShowModal ] = useState( false );

	const handleChange = ( v ) => {
		if ( v === false ) {
			setShowModal( true );
			return;
		}

		setChecked( v );
		// TODO: fire API request to enable campaign.
		// eslint-disable-next-line no-console
		console.warn(
			'The actual resume campaign action is not implemented/integrated yet.'
		);
	};

	const handleModalRequestClose = () => {
		setShowModal( false );
	};

	const handlePauseCampaign = () => {
		setShowModal( false );
		setChecked( false );
		// TODO: fire api request to pause campaign.
		// eslint-disable-next-line no-console
		console.warn(
			'The actual pause campaign action is not implemented/integrated yet.'
		);
	};

	return (
		<div className="gla-program-toggle">
			<ToggleControl checked={ checked } onChange={ handleChange } />
			{ showModal && (
				<PauseProgramModal
					programId={ program.id }
					onPauseCampaign={ handlePauseCampaign }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</div>
	);
};

export default ProgramToggle;
