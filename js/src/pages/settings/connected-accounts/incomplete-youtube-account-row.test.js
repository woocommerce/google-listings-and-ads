/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import IncompleteYouTubeAccountRow from './incomplete-youtube-account-row';
import useYouTubeSetupCompleteCallback from '~/hooks/useYouTubeSetupCompleteCallback';

jest.mock( '@woocommerce/navigation' );
jest.mock( '~/hooks/useYouTubeSetupCompleteCallback' );

describe( 'IncompleteYouTubeAccountRow', () => {
	let handleFinishSetup;
	let historyReplace;
	let scrollIntoViewSpy;

	const account = {
		id: 'youtube',
		title: 'YouTube',
		logo: 'youtube.svg',
	};

	beforeEach( () => {
		handleFinishSetup = jest.fn().mockResolvedValue( undefined );
		historyReplace = jest.fn().mockName( 'getHistory().replace' );
		scrollIntoViewSpy = jest.fn();

		getQuery.mockReturnValue( {} );
		getHistory.mockReturnValue( { replace: historyReplace } );
		getNewPath.mockReturnValue( '/google/settings?section=accounts' );
		useYouTubeSetupCompleteCallback.mockReturnValue( [
			handleFinishSetup,
			{ loading: false, error: undefined },
		] );

		Object.defineProperty( window.HTMLElement.prototype, 'scrollIntoView', {
			configurable: true,
			value: scrollIntoViewSpy,
		} );
	} );

	it( 'renders the incomplete setup message and CTA', () => {
		render(
			<IncompleteYouTubeAccountRow
				account={ account }
				actions={ <div>Account actions</div> }
			/>
		);

		expect(
			screen.getByText(
				'Your YouTube account is connected, but setup isn’t complete yet.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Complete setup' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Account actions' ) ).toBeInTheDocument();
	} );

	it( 'calls the completion callback when the CTA is clicked', async () => {
		const user = userEvent.setup();

		render(
			<IncompleteYouTubeAccountRow account={ account } actions={ null } />
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Complete setup' } )
		);

		expect( handleFinishSetup ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders the loading copy while setup completion is in progress', () => {
		useYouTubeSetupCompleteCallback.mockReturnValue( [
			handleFinishSetup,
			{ loading: true, error: undefined },
		] );

		render(
			<IncompleteYouTubeAccountRow account={ account } actions={ null } />
		);

		expect( screen.getByText( 'Please wait…' ) ).toBeInTheDocument();
	} );

	it( 'renders the completion error message when setup fails', () => {
		useYouTubeSetupCompleteCallback.mockReturnValue( [
			handleFinishSetup,
			{
				loading: false,
				error: {
					message:
						'The channel is not eligible for the linking program.',
				},
			},
		] );

		render(
			<IncompleteYouTubeAccountRow account={ account } actions={ null } />
		);

		expect(
			screen.getByText(
				'The channel is not eligible for the linking program.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'auto-completes setup after a YouTube OAuth return and stays on the Accounts tab', async () => {
		getQuery.mockReturnValue( { youtube: 'connected' } );

		render(
			<IncompleteYouTubeAccountRow account={ account } actions={ null } />
		);

		await waitFor( () => {
			expect( handleFinishSetup ).toHaveBeenCalledTimes( 1 );
		} );

		expect( scrollIntoViewSpy ).toHaveBeenCalledTimes( 1 );
		expect( historyReplace ).toHaveBeenCalledWith(
			'/google/settings?section=accounts'
		);
	} );

	it( 'does not redirect after unmounting before OAuth setup completes', async () => {
		getQuery.mockReturnValue( { youtube: 'connected' } );

		let resolveHandleFinishSetup;
		const finishSetupPromise = new Promise( ( resolve ) => {
			resolveHandleFinishSetup = resolve;
		} );

		handleFinishSetup.mockReturnValue( finishSetupPromise );

		const { unmount } = render(
			<IncompleteYouTubeAccountRow account={ account } actions={ null } />
		);

		await waitFor( () => {
			expect( handleFinishSetup ).toHaveBeenCalledTimes( 1 );
		} );

		unmount();

		await act( async () => {
			resolveHandleFinishSetup();
			await finishSetupPromise;
		} );

		expect( historyReplace ).not.toHaveBeenCalled();
	} );
} );
