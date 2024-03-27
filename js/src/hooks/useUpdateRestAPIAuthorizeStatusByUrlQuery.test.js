/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useUpdateRestAPIAuthorizeStatusByUrlQuery from './useUpdateRestAPIAuthorizeStatusByUrlQuery';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

jest.mock( '@woocommerce/navigation' );
jest.mock( '.~/hooks/useApiFetchCallback' );

describe( 'useUpdateRestAPIAuthorizeStatusByUrlQuery', () => {
	let fetchUpdateRestAPIAuthorize;

	beforeEach( () => {
		fetchUpdateRestAPIAuthorize = jest.fn();
		useApiFetchCallback.mockImplementation( () => [
			fetchUpdateRestAPIAuthorize,
		] );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is approved', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'approved',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'approved',
			},
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is disapproved', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'disapproved',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'disapproved',
			},
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is error', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'error',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'error',
			},
		} );
	} );

	it( 'should not call fetchUpdateRestAPIAuthorize if query param is unknown', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'does-not-exist',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 0 );
	} );
} );
