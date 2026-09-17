/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { useDispatch } from '@wordpress/data';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import usePreference from '~/hooks/usePreference';
import ConnectedSuccessNotice from './connected-success-notice';

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children, onDismiss } ) => (
		<div>
			{ children }
			<button onClick={ onDismiss }>Close</button>
		</div>
	),
} ) );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

const NOTICE_DISMISSED_KEY =
	'search-console-connected-success-notice-dismissed';

describe( 'ConnectedSuccessNotice', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( { set: jest.fn() } );
		usePreference.mockReturnValue( false );
	} );

	it( 'renders the success message', () => {
		render( <ConnectedSuccessNotice /> );

		expect(
			screen.getByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.',
				{ selector: 'p' }
			)
		).toBeInTheDocument();
	} );

	it( 'renders nothing once dismissed (persisted preference is true)', () => {
		usePreference.mockReturnValue( true );

		const { container } = render( <ConnectedSuccessNotice /> );

		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders a dismiss button', () => {
		render( <ConnectedSuccessNotice /> );

		expect(
			screen.getByRole( 'button', { name: 'Close' } )
		).toBeInTheDocument();
	} );

	it( 'persists the dismissal via the preferences store when dismissed', () => {
		const setMock = jest.fn();
		useDispatch.mockReturnValue( { set: setMock } );

		render( <ConnectedSuccessNotice /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Close' } ) );

		expect( setMock ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			NOTICE_DISMISSED_KEY,
			true
		);
	} );
} );
