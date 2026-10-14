/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { resolveErrorMessage } from '~/utils/handleError';
import { logError } from '~/utils/console';
import AppButton from '~/components/app-button';
import AppInputControl from '~/components/app-input-control';
import AppModal from '~/components/app-modal';
import NoticeDetail from '../notice-detail';

const NON_RETRYABLE_REASONS = [ 'insufficient_scope' ];

/**
 * Returns the message for a failed container creation, naming the cause when the backend reported
 * one. Falls back to the API's own message for any other failure.
 *
 * @param {Object} error The error thrown by the create request or the refresh that follows it.
 * @return {string} The message to show in the modal.
 */
function resolveCreateFailureMessage( error ) {
	const messageByReason = {
		insufficient_scope: __(
			"Your Google connection doesn't allow creating Google Tag Manager containers.",
			'google-listings-and-ads'
		),
		permission_denied: __(
			"Your Google account doesn't have permission to create containers in this Google Tag Manager account.",
			'google-listings-and-ads'
		),
		quota_exceeded: __(
			'Google Tag Manager has reached its request limit. Please try again later.',
			'google-listings-and-ads'
		),
	};

	return (
		messageByReason[ error?.data?.reason ] ??
		resolveErrorMessage(
			error,
			undefined,
			__(
				'Unable to create the Google Tag Manager container. Please try again.',
				'google-listings-and-ads'
			)
		)
	);
}

/**
 * Renders the modal for creating a Google Tag Manager container in the connected account. The
 * container type is always Web, so there is no type to pick. A failure is shown inside the modal
 * with the entered name kept, so the merchant can retry without retyping it. Once the container
 * has been created, a retry only repeats what follows the creation, so it can't create a second one.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onCreated Called, and awaited, once the container has been created and
 * connected. Rejecting shows the failure in the modal.
 * @param {Function} props.onRequestClose Called when the merchant dismisses the modal.
 *
 * @return {JSX.Element} The modal.
 */
export default function CreateContainerModal( { onCreated, onRequestClose } ) {
	const [ name, setName ] = useState( () => {
		return new URL( getSetting( 'homeUrl' ) ).host;
	} );
	const [ error, setError ] = useState( null );
	const [ isCreating, setIsCreating ] = useState( false );
	const hasCreatedContainer = useRef( false );
	const { fetchGoogleTagManagerAccount } = useAppDispatch();
	const [ fetchCreateContainer ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/containers`,
		method: 'POST',
		data: { name },
	} );

	const handleCreate = async () => {
		setError( null );
		setIsCreating( true );
		try {
			if ( ! hasCreatedContainer.current ) {
				try {
					await fetchCreateContainer();
				} catch ( createError ) {
					// A container is already connected, so only the card has to catch up with it.
					if ( createError?.data?.reason !== 'already_connected' ) {
						throw createError;
					}
				}
				hasCreatedContainer.current = true;
			}
			await onCreated();
		} catch ( creationError ) {
			setError( creationError );
			logError( creationError );
			setIsCreating( false );

			// The backend remembers a refusal, so the card catches up with it behind the modal.
			if ( creationError?.data?.reason === 'permission_denied' ) {
				fetchGoogleTagManagerAccount();
			}
		}
	};

	const handleRequestClose = () => {
		if ( ! isCreating ) {
			onRequestClose();
		}
	};

	// A missing scope can't be fixed by trying again.
	const canRetry = ! NON_RETRYABLE_REASONS.includes( error?.data?.reason );
	const tryAgainButton = (
		<AppButton
			key="try-again"
			disabled={ isCreating }
			onClick={ handleCreate }
			isLink
		>
			{ __( 'Try again', 'google-listings-and-ads' ) }
		</AppButton>
	);

	return (
		<AppModal
			title={ __(
				'Create a Google Tag Manager container',
				'google-listings-and-ads'
			) }
			onRequestClose={ handleRequestClose }
			buttons={ [
				<AppButton
					key="cancel"
					disabled={ isCreating }
					onClick={ onRequestClose }
					isTertiary
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="create-container"
					loading={ isCreating }
					disabled={ ! name.trim() }
					onClick={ handleCreate }
					isPrimary
				>
					{ __( 'Create container', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			<Flex direction="column" gap={ 4 }>
				<AppInputControl
					label={ __( 'Container name', 'google-listings-and-ads' ) }
					value={ name }
					onChange={ setName }
					disabled={ isCreating }
				/>
				{ error && (
					<NoticeDetail
						status="error"
						body={ <p>{ resolveCreateFailureMessage( error ) }</p> }
						actions={ canRetry ? [ tryAgainButton ] : [] }
					/>
				) }
			</Flex>
		</AppModal>
	);
}
