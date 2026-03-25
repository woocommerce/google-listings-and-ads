/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Modal, { CONTEXT } from './modal';
import useAdsCampaignsMissingEuDeclaration from '~/hooks/useAdsCampaignsMissingEuDeclaration';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * Component that checks for campaigns missing the EU political declaration and displays a modal to allow users to declare which campaigns contain political ads. The component is only rendered if there are campaigns missing the declaration and the user has not dismissed the modal.
 */
const EuPoliticalDeclaration = () => {
	const { data: campaignsMissingEuDeclaration, loaded } =
		useAdsCampaignsMissingEuDeclaration();
	const [ isDismissed, setIsDismissed ] = useState( false );

	const handleCloseModal = () => {
		setIsDismissed( true );
		recordGlaEvent( 'gla_eu_political_declaration_modal_closed', {
			context: CONTEXT,
		} );
	};

	if ( ! loaded || isDismissed || ! campaignsMissingEuDeclaration?.length ) {
		return null;
	}

	return (
		<Modal
			campaigns={ campaignsMissingEuDeclaration }
			onRequestClose={ handleCloseModal }
		/>
	);
};

export default EuPoliticalDeclaration;
