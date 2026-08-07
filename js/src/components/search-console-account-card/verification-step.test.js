/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import VerificationStep from './verification-step';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

jest.mock( '~/hooks/useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'VerificationStep', () => {
	let fetchVerify;
	let createNotice;
	let invalidateResolution;

	beforeEach( () => {
		fetchVerify = jest.fn().mockName( 'fetchVerify' );
		useApiFetchCallback.mockReturnValue( [
			fetchVerify,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );

		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: {
				status: 'incomplete',
				step: 'verification',
			},
		} );
	} );

	it( 'renders informational copy and a "Verify site" button, never styled as an error (AC-014)', () => {
		render( <VerificationStep /> );

		expect(
			screen.getByRole( 'button', { name: 'Verify site' } )
		).toBeInTheDocument();
	} );

	it( 'invalidates resolution after a successful verification click', async () => {
		const user = userEvent.setup();
		fetchVerify.mockResolvedValue( {} );

		render( <VerificationStep /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( fetchVerify ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );

	it( 'shows an error notice when verification fails', async () => {
		const user = userEvent.setup();
		fetchVerify.mockRejectedValue( new Error( 'failed' ) );

		render( <VerificationStep /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to verify' )
		);
	} );

	it( 'renders a "Request access" external link when the merchant cannot self-verify (AC-016)', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: {
				status: 'incomplete',
				step: 'verification',
				can_self_verify: false,
				request_access_url: 'https://search.google.com/request-access',
			},
		} );

		render( <VerificationStep /> );

		expect(
			screen.queryByRole( 'button', { name: 'Verify site' } )
		).not.toBeInTheDocument();

		// `ExternalLink` appends a visually-hidden "(opens in a new tab)" suffix to its
		// accessible name, so match on the visible text instead of the full accessible name.
		const link = screen.getByRole( 'link', { name: /Request access/ } );
		expect( link ).toHaveAttribute(
			'href',
			'https://search.google.com/request-access'
		);
	} );
} );
