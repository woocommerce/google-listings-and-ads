/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAcceptedCustomerDataTerms from './useAcceptedCustomerDataTerms';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

jest.mock( '.~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' ).mockReturnValue( {} )
);

jest.mock( '.~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' ).mockReturnValue( {} )
);

const CONNECTED_GOOGLE_ADS_ACCOUNT = {
	id: 777777,
	currency: 'PLN',
	symbol: 'zł',
	status: 'connected',
};

describe( 'useAcceptedCustomerDataTerms', () => {
	it( 'Returns the correct status when set to true', () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			isResolving: false,
			scope: {
				adsRequired: true,
			},
			google: true,
		} );

		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: CONNECTED_GOOGLE_ADS_ACCOUNT,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => {
			const { receiveAcceptedTerms } = useAppDispatch();

			receiveAcceptedTerms( { status: true } );
			return useAcceptedCustomerDataTerms();
		} );

		expect( result.current.acceptedCustomerDataTerms ).toBe( true );
	} );
} );
