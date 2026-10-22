/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleAdsAccountReady from './useGoogleAdsAccountReady';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleAdsAccountStatus from './useGoogleAdsAccountStatus';

jest.mock( './useGoogleAdsAccount' );
jest.mock( './useGoogleAdsAccountStatus' );

describe( 'useGoogleAdsAccountReady', () => {
	beforeEach( () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
			hasFinishedResolution: true,
		} );
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: true,
			step: '',
			hasFinishedResolution: true,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns null for both values while resolvers are pending', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: null,
			step: null,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useGoogleAdsAccountReady() );

		expect( result.current.isGoogleAdsReady ).toBeNull();
		expect( result.current.isLinkedToMerchantCenter ).toBeNull();
	} );

	it( 'returns both as true when step is empty', () => {
		const { result } = renderHook( () => useGoogleAdsAccountReady() );

		expect( result.current.isGoogleAdsReady ).toBe( true );
		expect( result.current.isLinkedToMerchantCenter ).toBe( true );
	} );

	it( 'returns both as true when step is billing', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: true,
			step: 'billing',
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsAccountReady() );

		expect( result.current.isGoogleAdsReady ).toBe( true );
		expect( result.current.isLinkedToMerchantCenter ).toBe( true );
	} );

	it( 'returns isGoogleAdsReady as true and isLinkedToMerchantCenter as false when step is link_merchant', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: true,
			step: 'link_merchant',
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsAccountReady() );

		expect( result.current.isGoogleAdsReady ).toBe( true );
		expect( result.current.isLinkedToMerchantCenter ).toBe( false );
	} );

	it( 'returns isGoogleAdsReady as false when step is set_id even when the account has access', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: true,
			step: 'set_id',
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsAccountReady() );

		expect( result.current.isGoogleAdsReady ).toBe( false );
		expect( result.current.isLinkedToMerchantCenter ).toBe( false );
	} );

	it( 'returns isGoogleAdsReady as false when step is set_id but account does not have access', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: false,
			step: 'set_id',
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsAccountReady() );

		expect( result.current.isGoogleAdsReady ).toBe( false );
		expect( result.current.isLinkedToMerchantCenter ).toBe( false );
	} );
} );
