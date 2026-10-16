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
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import AppInputControl from '~/components/app-input-control';
import AppModal from '~/components/app-modal';
import NoticeDetail from '../../components/notice-detail';
import useCreateGoogleTagManagerContainer from '../../hooks/useCreateGoogleTagManagerContainer';

/**
 * Renders the modal for creating a Google Tag Manager container in the connected account. The
 * container type is always Web, so there is no type to pick. A failure is shown inside the modal
 * with the entered name kept, so the merchant can retry without retyping it. A retry can't create a
 * second container: the backend connects one that was created but not connected yet, and answers
 * that a container is already connected once it is.
 *
 * Once the container is created and the connection refreshed, the card switches to its connected
 * state, which removes this modal.
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
	const { fetchGoogleTagManagerAccount } = useAppDispatch();
	const [ createContainer, { loading: isBusy, error } ] =
		useCreateGoogleTagManagerContainer();

	const handleCreateContainerClick = () => {
		return createContainer( name );
	};

	const canRetry = error?.canRetry ?? true;

	const handleCancelClick = () => {
		if ( isBusy ) {
			return;
		}

		// The backend remembers a refusal, so the card catches up with it once the merchant has read
		// the cause. Refreshing earlier would remove this modal along with the create button.
		if ( ! canRetry ) {
			fetchGoogleTagManagerAccount();
		}

		onRequestClose();
	};

	// After a failure the primary action retries, unless retrying can't help.
	const hasRetryableError = Boolean( error ) && canRetry;
	const hasName = name.trim().length > 0;

	return (
		<AppModal
			title={ __(
				'Create a Google Tag Manager container',
				'google-listings-and-ads'
			) }
			onRequestClose={ handleCancelClick }
			buttons={ [
				<AppButton
					key="cancel"
					disabled={ isBusy }
					onClick={ handleCancelClick }
					isTertiary
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="create-container"
					loading={ isBusy }
					disabled={ ! hasName || ! canRetry }
					onClick={ handleCreateContainerClick }
					isPrimary
				>
					{ hasRetryableError
						? __( 'Try again', 'google-listings-and-ads' )
						: __( 'Create container', 'google-listings-and-ads' ) }
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
						body={ <p>{ error.message }</p> }
					/>
				) }
			</Flex>
		</AppModal>
	);
}
