/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';

const Confirm = ( { onConfirm = noop } ) => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();

	const handleConfirm = () => {
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		);

		onConfirm();
	};

	return (
		<AppButton isPrimary onClick={ handleConfirm }>
			{ __( 'Confirm', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default Confirm;
