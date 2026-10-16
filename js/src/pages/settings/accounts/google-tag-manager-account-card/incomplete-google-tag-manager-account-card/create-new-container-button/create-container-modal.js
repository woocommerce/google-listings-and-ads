/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import { CREATED_NOT_CONNECTED_MESSAGE } from '../../constants';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { resolveErrorMessage } from '~/utils/handleError';
import AppButton from '~/components/app-button';
import AppInputControl from '~/components/app-input-control';
import AppModal from '~/components/app-modal';
import NoticeDetail from '../../components/notice-detail';
import useRefreshGoogleTagManagerConnection from '../../hooks/useRefreshGoogleTagManagerConnection';

const NON_RETRYABLE_REASONS = [ 'insufficient_scope', 'permission_denied' ];

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
 * with the entered name kept, so the merchant can retry without retyping it. A retry can't create a
 * second container: the backend connects one that was created but not connected yet, and answers
 * that a container is already connected once it is.
 *
 * Once the container is created, the connection is refreshed. The card then switches to its
 * connected state, or to the notice for a container that couldn't be connected, which removes this
 * modal. A refresh that reports neither is shown as a failure, so the merchant can retry.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onRequestClose Called when the merchant dismisses the modal.
 *
 * @return {JSX.Element} The modal.
 */
export default function CreateContainerModal( { onRequestClose } ) {
	const [ name, setName ] = useState( () => {
		return new URL( getSetting( 'homeUrl' ) ).host;
	} );
	const [ error, setError ] = useState( null );
	const { fetchGoogleTagManagerAccount } = useAppDispatch();
	const { refreshConnection, loading: isRefreshing } =
		useRefreshGoogleTagManagerConnection();
	const [ fetchCreateContainer, { loading: isCreating } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/tag-manager/containers`,
			method: 'POST',
			data: { name },
		} );
	const isBusy = isCreating || isRefreshing;

	const handleCreate = async () => {
		setError( null );
		try {
			await fetchCreateContainer().catch( ( createError ) => {
				// A container is already connected, so only the card has to catch up with it.
				if ( createError?.data?.reason !== 'already_connected' ) {
					throw createError;
				}
			} );

			const refreshedAccount = await refreshConnection();

			// The container is already created, so a refresh that reports neither a connected nor a
			// pending container is shown as a failure the merchant can retry.
			if (
				refreshedAccount?.status !==
					GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED &&
				! refreshedAccount?.pendingContainerId
			) {
				throw new Error( CREATED_NOT_CONNECTED_MESSAGE );
			}
		} catch ( creationError ) {
			setError( creationError );

			// The backend remembers a refusal, so the card catches up with it behind the modal.
			if ( creationError?.data?.reason === 'permission_denied' ) {
				fetchGoogleTagManagerAccount();
			}
		}
	};

	const handleRequestClose = () => {
		if ( ! isBusy ) {
			onRequestClose();
		}
	};

	// Neither a missing scope nor a refusal of permission can be fixed by trying again.
	const canRetry = ! NON_RETRYABLE_REASONS.includes( error?.data?.reason );
	const tryAgainButton = (
		<AppButton
			key="try-again"
			disabled={ isBusy }
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
					disabled={ isBusy }
					onClick={ onRequestClose }
					isTertiary
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="create-container"
					loading={ isBusy }
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
					disabled={ isBusy }
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
