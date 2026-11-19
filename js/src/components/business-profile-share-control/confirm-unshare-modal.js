/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';

/**
 * Modal confirming that turning off sharing removes the post from the merchant's Business
 * Profile. The post itself stays published on the store either way.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.onConfirm Called when the merchant confirms turning sharing off.
 * @param {() => void} props.onRequestClose Called when the merchant cancels or closes the modal.
 * @return {JSX.Element} The confirmation modal.
 */
export default function ConfirmUnshareModal( { onConfirm, onRequestClose } ) {
	return (
		<AppModal
			title={ __( 'Turn off sharing?', 'google-listings-and-ads' ) }
			onRequestClose={ onRequestClose }
			buttons={ [
				<Button
					key="cancel"
					variant="tertiary"
					onClick={ onRequestClose }
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</Button>,
				<Button key="confirm" variant="primary" onClick={ onConfirm }>
					{ __( 'Turn off sharing', 'google-listings-and-ads' ) }
				</Button>,
			] }
		>
			<p>
				{ __(
					'This removes the update from your Business Profile. The post stays published on your store.',
					'google-listings-and-ads'
				) }
			</p>
		</AppModal>
	);
}
