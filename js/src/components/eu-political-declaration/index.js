/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Modal, { CONTEXT } from './modal';
import useAdsCampaignsMissingEuDeclaration from '~/hooks/useAdsCampaignsMissingEuDeclaration';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * @event gla_eu_political_declaration_modal_closed
 * @property {string} context The context in which the modal was closed, set to 'eu-political-declaration-modal'.
 */

/**
 * Component that checks for campaigns missing the EU political declaration and displays a modal to allow users to declare which campaigns contain political ads. The component is only rendered if there are campaigns missing the declaration and the user has not dismissed the modal.
 *
 * @fires gla_eu_political_declaration_modal_closed when the modal is closed.
 *
 * @return {JSX.Element|null} The Modal component if there are campaigns missing the declaration and the modal has not been dismissed, otherwise null.
 */
const EuPoliticalDeclaration = () => {
	const { data: campaignsMissingEuDeclaration, loaded } =
		useAdsCampaignsMissingEuDeclaration();
	const [ isDismissed, setIsDismissed ] = useState( false );

	const handleCloseModal = useCallback( () => {
		setIsDismissed( true );
		recordGlaEvent( 'gla_eu_political_declaration_modal_closed', {
			context: CONTEXT,
		} );
	}, [] );

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
