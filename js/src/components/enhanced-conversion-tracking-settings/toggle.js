/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppStandaloneToggleControl from '.~/components/app-standalone-toggle-control';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';

const Toggle = () => {
	const { updateEnhancedAdsConversionStatus, invalidateResolution } =
		useAppDispatch();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();

	useEffect( () => {
		if (
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING
		) {
			invalidateResolution( 'getAcceptedCustomerDataTerms', [] );
		}
	}, [ allowEnhancedConversions, invalidateResolution ] );

	const handleOnChange = useCallback(
		( value ) => {
			if ( ! acceptedCustomerDataTerms ) {
				return;
			}

			updateEnhancedAdsConversionStatus(
				value
					? ENHANCED_ADS_CONVERSION_STATUS.ENABLED
					: ENHANCED_ADS_CONVERSION_STATUS.DISABLED
			);
		},
		[ updateEnhancedAdsConversionStatus, acceptedCustomerDataTerms ]
	);

	return (
		<AppStandaloneToggleControl
			checked={
				allowEnhancedConversions ===
				ENHANCED_ADS_CONVERSION_STATUS.ENABLED
			}
			disabled={ ! hasFinishedResolution || ! acceptedCustomerDataTerms }
			onChange={ handleOnChange }
		/>
	);
};

export default Toggle;
