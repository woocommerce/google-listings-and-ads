/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ConnectAds from './';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import { useAppDispatch } from '.~/data';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import expectComponentToRecordEventWithFilteredProperties from '.~/tests/expectComponentToRecordEventWithFilteredProperties';

jest.mock( '.~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '.~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

jest.mock( '.~/data', () => ( {
	...jest.requireActual( '.~/data' ),
	useAppDispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );

describe( 'ConnectAds', () => {
	const accounts = [
		{ id: '1', name: 'Account A' },
		{ id: '2', name: 'Account B' },
	];

	let fetchGoogleAdsAccountStatus;

	function getConnectButton() {
		return screen.getByRole( 'button', { name: 'Connect' } );
	}

	function getCreateAccountButton() {
		return screen.getByRole( 'button', {
			name: 'Or, create a new Google Ads account',
		} );
	}

	beforeEach( () => {
		useApiFetchCallback.mockReturnValue( [
			jest.fn().mockName( 'fetchConnectAdsAccount' ),
		] );

		useGoogleAdsAccount.mockReturnValue( {
			refetchGoogleAdsAccount: jest
				.fn()
				.mockName( 'refetchGoogleAdsAccount' ),
		} );

		fetchGoogleAdsAccountStatus = jest
			.fn()
			.mockName( 'fetchGoogleAdsAccountStatus' );
		useAppDispatch.mockReturnValue( { fetchGoogleAdsAccountStatus } );
	} );

	afterEach( () => {
		recordEvent.mockClear();
	} );

	it( 'should render the given accounts in a selection', () => {
		render( <ConnectAds accounts={ accounts } /> );

		expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Select one' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Account A (1)' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Account B (2)' } )
		).toBeInTheDocument();
	} );

	it( 'should render a message when there are multiple existing accounts', () => {
		const message =
			'If you manage multiple sub-accounts in Google Ads, please connect the relevant sub-account, not a manager account.';
		const { rerender } = render(
			<ConnectAds accounts={ [ accounts[ 0 ] ] } />
		);

		expect( screen.queryByText( message ) ).not.toBeInTheDocument();

		rerender( <ConnectAds accounts={ accounts } /> );

		expect( screen.getByText( message ) ).toBeInTheDocument();
	} );

	it( 'should call back to onCreateNew when clicking the button to switch to account creation', async () => {
		const user = userEvent.setup();
		const onCreateNew = jest.fn().mockName( 'onCreateNew' );
		render( <ConnectAds accounts={ [] } onCreateNew={ onCreateNew } /> );

		expect( onCreateNew ).toHaveBeenCalledTimes( 0 );

		await user.click( getCreateAccountButton() );

		expect( onCreateNew ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should disable the "Connect" button when no account is selected', async () => {
		const user = userEvent.setup();

		render( <ConnectAds accounts={ accounts } /> );
		const combobox = screen.getByRole( 'combobox' );
		const button = getConnectButton();

		expect( button ).toBeDisabled();

		await user.selectOptions( combobox, '1' );

		expect( button ).toBeEnabled();

		await user.selectOptions( combobox, '' );

		expect( button ).toBeDisabled();
	} );

	it( 'should render a connecting state and disable the button to switch to account creation after clicking the "Connect" button', async () => {
		const user = userEvent.setup();
		let resolve;

		fetchGoogleAdsAccountStatus.mockReturnValue(
			new Promise( ( _resolve ) => {
				resolve = _resolve;
			} )
		);

		render( <ConnectAds accounts={ accounts } /> );

		expect( getCreateAccountButton() ).toBeEnabled();

		await user.selectOptions( screen.getByRole( 'combobox' ), '1' );
		await user.click( getConnectButton() );

		expect( getCreateAccountButton() ).toBeDisabled();
		expect( screen.getByText( 'Connecting…' ) ).toBeInTheDocument();

		await act( async () => resolve() );

		expect( getCreateAccountButton() ).toBeDisabled();
		expect( screen.getByText( 'Connecting…' ) ).toBeInTheDocument();
	} );

	it( 'should resume to a state where it can retry the connection after a failed connection', async () => {
		const user = userEvent.setup();
		let reject;

		fetchGoogleAdsAccountStatus.mockReturnValue(
			new Promise( ( _, _reject ) => {
				reject = _reject;
			} )
		);

		render( <ConnectAds accounts={ accounts } /> );

		await user.selectOptions( screen.getByRole( 'combobox' ), '1' );
		await user.click( getConnectButton() );

		expect( screen.getByText( 'Connecting…' ) ).toBeInTheDocument();
		expect( getCreateAccountButton() ).toBeDisabled();

		await act( async () => reject() );

		expect( getConnectButton() ).toBeEnabled();
		expect( getCreateAccountButton() ).toBeEnabled();
	} );

	it( 'should record click events and the `id` event property for the "Connect" button', async () => {
		const user = userEvent.setup();

		// Prevent the component from locking in the connecting state
		fetchGoogleAdsAccountStatus.mockRejectedValue();
		render( <ConnectAds accounts={ accounts } /> );
		const combobox = screen.getByRole( 'combobox' );

		expect( recordEvent ).toHaveBeenCalledTimes( 0 );

		await user.selectOptions( combobox, '2' );
		await user.click( getConnectButton() );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'gla_ads_account_connect_button_click',
			{ id: 2 }
		);

		await user.selectOptions( combobox, '1' );
		await user.click( getConnectButton() );

		expect( recordEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'gla_ads_account_connect_button_click',
			{ id: 1 }
		);
	} );

	it( 'should record click events for the "Connect" button and be aware of extra event properties from filters', async () => {
		const user = userEvent.setup();

		// Prevent the component from locking in the connecting state
		fetchGoogleAdsAccountStatus.mockRejectedValue();

		await expectComponentToRecordEventWithFilteredProperties(
			() => <ConnectAds accounts={ accounts } />,
			FILTER_ONBOARDING,
			async () => {
				const combobox = screen.getByRole( 'combobox' );
				await user.selectOptions( combobox, '1' );
				await user.click( getConnectButton() );
			},
			'gla_ads_account_connect_button_click',
			[
				{ context: 'setup-mc', step: '1' },
				{ context: 'setup-ads', step: '2' },
			]
		);
	} );
} );
