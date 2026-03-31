/**
 * External dependencies
 */
import { createContext, useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Modal from './modal';
import { EU_POLITICAL_ADVERTISING_DECLARATION_REQUIRED_ERROR_CODE } from '~/constants';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * @event gla_eu_political_declaration_modal_opened
 * @property {string} context The context in which the modal is being used, for tracking purposes.
 */

/**
 * @event gla_eu_political_declaration_modal_closed
 * @property {string} context The context in which the modal is being used, for tracking purposes.
 */

/**
 * @typedef {Object} EuPoliticalDeclarationContextValue
 * @property {Function} showModal Function to show the EU political declaration modal.
 * @property {Function} hideModal Function to hide the EU political declaration modal.
 * @property {Function} handleError Function to handle errors related to campaign updates, which can trigger the modal if the error is due to a missing political declaration.
 */

export const EuPoliticalDeclarationContext = createContext( null );

/**
 * Provider component for the EU political declaration modal.
 * Wraps child components and provides a context with an error handler that can trigger the modal when a campaign update fails due to a missing political declaration.
 *
 * @fires gla_eu_political_declaration_modal_opened when the modal is opened.
 * @fires gla_eu_political_declaration_modal_closed when the modal is closed.
 *
 * @param {Object} props
 * @param {JSX.Element} props.children Child components.
 * @param {string} props.context The context in which the modal is being used, for tracking purposes.
 * @return {JSX.Element} The provider with the modal rendered inside.
 */
const EuPoliticalDeclarationProvider = ( { children, context } ) => {
	const [ isOpen, setIsOpen ] = useState( false );

	const showModal = useCallback( () => {
		setIsOpen( true );
		recordGlaEvent( 'gla_eu_political_declaration_modal_opened', {
			context,
		} );
	}, [ context ] );

	const hideModal = useCallback( () => {
		setIsOpen( false );
		recordGlaEvent( 'gla_eu_political_declaration_modal_closed', {
			context,
		} );
	}, [ context ] );

	const handleError = useCallback(
		( error ) => {
			if (
				error?.code ===
				EU_POLITICAL_ADVERTISING_DECLARATION_REQUIRED_ERROR_CODE
			) {
				showModal();
			}
		},
		[ showModal ]
	);

	return (
		<EuPoliticalDeclarationContext.Provider
			value={ { showModal, hideModal, handleError } }
		>
			{ children }

			{ isOpen && (
				<Modal onRequestClose={ hideModal } eventContext={ context } />
			) }
		</EuPoliticalDeclarationContext.Provider>
	);
};

export default EuPoliticalDeclarationProvider;
