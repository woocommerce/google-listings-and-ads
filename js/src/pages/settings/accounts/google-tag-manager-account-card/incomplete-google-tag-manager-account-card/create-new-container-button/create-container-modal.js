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
 * Renders the form for creating a Google Tag Manager container, with the last error and a retry action.
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

	const handleClose = () => {
		if ( isBusy ) {
			return;
		}

		// A failed create can still change the connection: the backend remembers a refusal, and keeps
		// a container that was created but couldn't be connected. The card catches up once the
		// merchant has read the cause, since refreshing earlier could remove this modal.
		if ( error ) {
			fetchGoogleTagManagerAccount();
		}

		onRequestClose();
	};

	// After a failure the primary action retries, unless retrying can't help.
	const hasRetryableError = error?.canRetry === true;
	const hasName = name.trim().length > 0;

	return (
		<AppModal
			title={ __(
				'Create a Google Tag Manager container',
				'google-listings-and-ads'
			) }
			onRequestClose={ handleClose }
			buttons={ [
				<AppButton
					key="cancel"
					disabled={ isBusy }
					onClick={ handleClose }
					isTertiary
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="create-container"
					loading={ isBusy }
					disabled={ ! hasName || error?.canRetry === false }
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
