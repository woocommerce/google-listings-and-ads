/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppStandaloneToggleControl from '.~/components/app-standalone-toggle-control';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';

const TOGGLE_LABEL_MAP = {
	[ ENHANCED_ADS_CONVERSION_STATUS.DISABLED ]: __(
		'Enable',
		'google-listings-and-ads'
	),
	[ ENHANCED_ADS_CONVERSION_STATUS.ENABLED ]: __(
		'Disable',
		'google-listings-and-ads'
	),
};

const Toggle = () => {
	const { updateEnhancedAdsConversionStatus, invalidateResolution } =
		useAppDispatch();
	const { allowEnhancedConversions, hasFinishedResolution } =
		useAllowEnhancedConversions();
	const {
		acceptedCustomerDataTerms,
		hasFinishedResolution: hasResolvedAcceptedCustomerDataTerms,
	} = useAcceptedCustomerDataTerms();

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

	if ( ! hasFinishedResolution ) {
		return <Spinner />;
	}

	return (
		<AppStandaloneToggleControl
			checked={
				allowEnhancedConversions ===
				ENHANCED_ADS_CONVERSION_STATUS.ENABLED
			}
			disabled={
				! hasResolvedAcceptedCustomerDataTerms ||
				! acceptedCustomerDataTerms
			}
			onChange={ handleOnChange }
			label={
				TOGGLE_LABEL_MAP[ allowEnhancedConversions ] ||
				__( 'Enable', 'google-listings-and-ads' )
			}
		/>
	);
};

export default Toggle;
