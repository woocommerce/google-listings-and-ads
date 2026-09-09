/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ConnectedSuccessNotice from './connected-success-notice';
import { recordGlaEvent } from '~/utils/tracks';
import { SEARCH_CONSOLE_EVENT_CONTEXT } from './constants';

jest.mock( '@woocommerce/navigation', () => ( {
	getHistory: jest.fn(),
} ) );

jest.mock( '~/utils/urls', () => ( {
	getReportsUrl: jest.fn( () => '/reports-url' ),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'ConnectedSuccessNotice', () => {
	let push;

	beforeEach( () => {
		jest.clearAllMocks();

		push = jest.fn().mockName( 'push' );
		getHistory.mockReturnValue( { push } );
	} );

	it( 'renders the success message and a button to reports', () => {
		render( <ConnectedSuccessNotice /> );

		expect(
			screen.getByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'View reports' } )
		).toBeInTheDocument();
	} );

	it( 'navigates to the reports page via the SPA router when clicked', async () => {
		const user = userEvent.setup();

		render( <ConnectedSuccessNotice /> );

		await user.click(
			screen.getByRole( 'button', { name: 'View reports' } )
		);

		expect( push ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_search_console_success_notice_view_reports_button_click',
			{ context: SEARCH_CONSOLE_EVENT_CONTEXT }
		);
	} );

	it( 'hides itself once dismissed', async () => {
		const user = userEvent.setup();

		render( <ConnectedSuccessNotice /> );

		await user.click( screen.getByRole( 'button', { name: 'Close' } ) );

		expect(
			screen.queryByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.'
			)
		).not.toBeInTheDocument();
	} );
} );
