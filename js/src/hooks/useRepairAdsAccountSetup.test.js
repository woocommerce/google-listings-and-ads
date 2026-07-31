/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useRepairAdsAccountSetup from './useRepairAdsAccountSetup';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleAdsAccountStatus from './useGoogleAdsAccountStatus';
import useUpsertAdsAccount from './useUpsertAdsAccount';

jest.mock( './useGoogleAdsAccount' );
jest.mock( './useGoogleAdsAccountStatus' );
jest.mock( './useUpsertAdsAccount' );

describe( 'useRepairAdsAccountSetup', () => {
	let upsertAdsAccount;

	beforeEach( () => {
		upsertAdsAccount = jest.fn();

		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
			hasFinishedResolution: true,
		} );
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: true,
			step: 'set_id',
			hasFinishedResolution: true,
		} );
		useUpsertAdsAccount.mockReturnValue( [ upsertAdsAccount ] );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'calls upsertAdsAccount once when the account is connected with access and step is set_id', () => {
		renderHook( () => useRepairAdsAccountSetup() );

		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'calls upsertAdsAccount once when the account is connected with access and step is conversion_action', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: true,
			step: 'conversion_action',
			hasFinishedResolution: true,
		} );

		renderHook( () => useRepairAdsAccountSetup() );

		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not call upsertAdsAccount while resolvers are pending', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: null,
			step: null,
			hasFinishedResolution: false,
		} );

		renderHook( () => useRepairAdsAccountSetup() );

		expect( upsertAdsAccount ).not.toHaveBeenCalled();
	} );

	it( 'does not call upsertAdsAccount when there is no connection', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: false,
			hasFinishedResolution: true,
		} );

		renderHook( () => useRepairAdsAccountSetup() );

		expect( upsertAdsAccount ).not.toHaveBeenCalled();
	} );

	it( 'does not call upsertAdsAccount when the account does not have access', () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasAccess: false,
			step: 'set_id',
			hasFinishedResolution: true,
		} );

		renderHook( () => useRepairAdsAccountSetup() );

		expect( upsertAdsAccount ).not.toHaveBeenCalled();
	} );

	it.each( [ '', 'billing', 'link_merchant' ] )(
		'does not call upsertAdsAccount when step is %j',
		( step ) => {
			useGoogleAdsAccountStatus.mockReturnValue( {
				hasAccess: true,
				step,
				hasFinishedResolution: true,
			} );

			renderHook( () => useRepairAdsAccountSetup() );

			expect( upsertAdsAccount ).not.toHaveBeenCalled();
		}
	);

	it( 'does not call upsertAdsAccount a second time when the states refetch with the same step', () => {
		const { rerender } = renderHook( () => useRepairAdsAccountSetup() );

		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );

		rerender();

		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
	} );
} );
