/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '../app-modal';

const EditMinimumOrderModal = ( props ) => {
	const { onRequestClose } = props;

	return (
		<AppModal
			title={ __(
				'Minimum order to qualify for free shipping',
				'google-listings-and-ads'
			) }
			onRequestClose={ onRequestClose }
		></AppModal>
	);
};

export default EditMinimumOrderModal;
