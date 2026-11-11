/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { getHistory, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useIncentiveAppliedNoticeEffect from './useIncentiveAppliedNoticeEffect';
import useDispatchCoreNotices from './useDispatchCoreNotices';

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn(),
	getHistory: jest.fn(),
	getNewPath: jest.fn( ( query ) => JSON.stringify( query ) ),
} ) );

jest.mock( './useDispatchCoreNotices' );

const NOTICE =
	'The selected Ads credit offer was successfully applied to your account.';

describe( 'useIncentiveAppliedNoticeEffect', () => {
	let createNotice;
	let replace;

	beforeEach( () => {
		jest.clearAllMocks();

		createNotice = jest.fn();
		replace = jest.fn();

		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		getHistory.mockReturnValue( { replace } );
	} );

	it( 'shows the notice and removes the query arg when it is present', () => {
		getQuery.mockReturnValue( {
			path: '/google/dashboard',
			incentive: 'applied',
		} );

		renderHook( () => useIncentiveAppliedNoticeEffect() );

		expect( createNotice ).toHaveBeenCalledWith( 'success', NOTICE );
		expect( replace ).toHaveBeenCalledWith(
			JSON.stringify( { path: '/google/dashboard' } )
		);
	} );

	it( 'does nothing when the query arg is absent', () => {
		getQuery.mockReturnValue( { path: '/google/dashboard' } );

		renderHook( () => useIncentiveAppliedNoticeEffect() );

		expect( createNotice ).not.toHaveBeenCalled();
		expect( replace ).not.toHaveBeenCalled();
	} );

	it( 'waits until the guide modal is closed', () => {
		getQuery.mockReturnValue( {
			path: '/google/dashboard',
			guide: 'campaign-creation-success',
			incentive: 'applied',
		} );

		const { rerender } = renderHook( () =>
			useIncentiveAppliedNoticeEffect()
		);

		expect( createNotice ).not.toHaveBeenCalled();

		getQuery.mockReturnValue( {
			path: '/google/dashboard',
			incentive: 'applied',
		} );
		rerender();

		expect( createNotice ).toHaveBeenCalledWith( 'success', NOTICE );
	} );
} );
