/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import PauseProgramModal from './pause-program-modal';
import './index.scss';

const ProgramToggle = ( props ) => {
	const { program } = props;
	const [ checked, setChecked ] = useState( program.active );
	const [ showModal, setShowModal ] = useState( false );

	const handleChange = async ( v ) => {
		if ( v === false ) {
			setShowModal( true );
			return;
		}

		setChecked( v );
		await apiFetch( {
			path: `/wc/gla/ads/campaigns/${ program.id }`,
			method: 'POST',
			data: {
				status: 'enabled',
			},
		} );
	};

	const handleModalRequestClose = () => {
		setShowModal( false );
	};

	const handlePauseCampaign = async () => {
		setShowModal( false );
		setChecked( false );
		await apiFetch( {
			path: `/wc/gla/ads/campaigns/${ program.id }`,
			method: 'POST',
			data: {
				status: 'paused',
			},
		} );
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
