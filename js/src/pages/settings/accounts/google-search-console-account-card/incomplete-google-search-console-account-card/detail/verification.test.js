/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Verification from './verification';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useDispatchCoreNotices' );
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'Verification', () => {
	let fetchVerify;
	let createNotice;
	let invalidateResolution;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchVerify = jest.fn().mockName( 'fetchVerify' );
		useApiFetchCallback.mockReturnValue( [
			fetchVerify,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );
	} );

	it( 'POSTs to the verify endpoint and refreshes the account when clicked', async () => {
		const user = userEvent.setup();
		fetchVerify.mockResolvedValue( {} );

		render( <Verification /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/verify',
			method: 'POST',
		} );
		expect( fetchVerify ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
		expect( createNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows an error notice and points to requesting access when the verify request fails', async () => {
		const user = userEvent.setup();
		fetchVerify.mockRejectedValue( new Error( 'failed' ) );

		render( <Verification /> );

		expect(
			screen.queryByRole( 'link', {
				name: /Request access from the property owner/,
			} )
		).not.toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( invalidateResolution ).not.toHaveBeenCalled();
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to verify your site' )
		);
		expect(
			screen.getByRole( 'link', {
				name: /Request access from the property owner/,
			} )
		).toHaveAttribute(
			'href',
			'https://support.google.com/webmasters/answer/7687615'
		);
	} );
} );
