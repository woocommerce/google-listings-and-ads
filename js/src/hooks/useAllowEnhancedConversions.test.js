/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import useAllowEnhancedConversions from './useAllowEnhancedConversions';

describe( 'useAllowEnhancedConversions', () => {
	it( 'Returns the correct status when enabled', () => {
		const { result } = renderHook( () => {
			const { receiveAllowEnhancedConversions } = useAppDispatch();
			receiveAllowEnhancedConversions( {
				status: ENHANCED_ADS_CONVERSION_STATUS.ENABLED,
			} );

			return useAllowEnhancedConversions();
		} );

		expect( result.current.allowEnhancedConversions ).toBe(
			ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		);
	} );

	it( 'Returns the correct status when disabled', () => {
		const { result } = renderHook( () => {
			const { receiveAllowEnhancedConversions } = useAppDispatch();
			receiveAllowEnhancedConversions( {
				status: ENHANCED_ADS_CONVERSION_STATUS.DISABLED,
			} );

			return useAllowEnhancedConversions();
		} );

		expect( result.current.allowEnhancedConversions ).toBe(
			ENHANCED_ADS_CONVERSION_STATUS.DISABLED
		);
	} );
} );
