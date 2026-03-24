/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Modal from './modal';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import { recordGlaEvent } from '~/utils/tracks';

const EuPoliticalDeclaration = () => {
	const { data: allCampaigns, loaded } = useAdsCampaigns();
	const [ isDismissed, setIsDismissed ] = useState( false );
	const filteredCampaigns = allCampaigns?.filter(
		( campaign ) => campaign?.missing_eu_political_declaration === true
	);

	const handleCloseModal = () => {
		setIsDismissed( true );
		recordGlaEvent( 'gla_eu_political_declaration_modal_closed', {
			context: 'eu_political_declaration_modal',
		} );
	};

	if ( ! loaded || isDismissed || ! filteredCampaigns?.length ) {
		return null;
	}

	return (
		<Modal
			campaigns={ filteredCampaigns }
			onRequestClose={ handleCloseModal }
		/>
	);
};

export default EuPoliticalDeclaration;
