/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import Text from '~/components/app-text';
import MultiLingualPluginPrompt from './multilingual-plugin-prompt';
import './index.scss';

/**
 * Placeholder for the Add Market modal.
 *
 * The follow-up task will replace this with a real form (country selector,
 * shipping configuration, validation, and a save handler that triggers a
 * markets refetch). For now, the modal renders a short placeholder body and a
 * Close button so the open / close wiring from `AddMarket` can be reviewed
 * end-to-end.
 *
 * @param {Object} props
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const AddMarketModal = ( { onRequestClose } ) => {
	return (
		<AppModal
			title={ __( 'Add market', 'google-listings-and-ads' ) }
			onRequestClose={ onRequestClose }
			buttons={ [
				<AppButton
					key="close"
					variant="tertiary"
					onClick={ onRequestClose }
				>
					{ __( 'Close', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			<Text
				as="h2"
				variant="subtitle-small"
				className="gla-add-market-modal__title"
			>
				{ __(
					'Install a multilingual plugin to add markets',
					'google-listings-and-ads'
				) }
			</Text>
			<Text variant="body" className="gla-add-market-modal__description">
				{ __(
					'To create market feeds with different languages and currencies, you need a compatible multilingual plugin installed on your store.',
					'google-listings-and-ads'
				) }
			</Text>
			<MultiLingualPluginPrompt />
		</AppModal>
	);
};

export default AddMarketModal;
