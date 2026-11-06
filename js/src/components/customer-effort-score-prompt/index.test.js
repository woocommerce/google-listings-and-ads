/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '~/constants';
import localStorage from '~/utils/localStorage';
import { recordGlaEvent } from '~/utils/tracks';
import CustomerEffortScorePrompt from '.';
import CustomerEffortUnmountableNotice from './notice';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

jest.mock( '~/utils/localStorage', () => ( {
	remove: jest.fn().mockName( 'localStorage.remove' ),
} ) );

jest.mock( './notice', () =>
	jest.fn( () => null ).mockName( 'CustomerEffortUnmountableNotice' )
);

describe( 'CustomerEffortScorePrompt', () => {
	const eventContext = 'campaign-creation-success';

	const renderPrompt = () => {
		render(
			<CustomerEffortScorePrompt
				eventContext={ eventContext }
				label="First label"
				secondLabel="Second label"
			/>
		);
		return CustomerEffortUnmountableNotice.mock.calls[ 0 ][ 0 ];
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'passes the labels to the notice and modal', () => {
		const props = renderPrompt();

		expect( props ).toEqual(
			expect.objectContaining( {
				title: 'First label',
				firstQuestion: 'First label',
				secondQuestion: 'Second label',
			} )
		);
	} );

	it( 'fires gla_ces_snackbar_open once and clears the local storage flag when the notice is shown', () => {
		renderPrompt().onNoticeShownCallback();

		expect( localStorage.remove ).toHaveBeenCalledWith(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
		);
		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_ces_snackbar_open',
			{ context: eventContext }
		);
	} );

	it( 'fires gla_ces_snackbar_closed once when the notice is dismissed', () => {
		renderPrompt().onNoticeDismissedCallback();

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_ces_snackbar_closed',
			{ context: eventContext }
		);
	} );

	it( 'fires gla_ces_modal_open once when the modal is shown', () => {
		renderPrompt().onModalShownCallback();

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith( 'gla_ces_modal_open', {
			context: eventContext,
		} );
	} );

	it( 'fires gla_ces_feedback once with the score and comments when feedback is recorded', () => {
		renderPrompt().recordScoreCallback( 4, 5, 'Nice' );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith( 'gla_ces_feedback', {
			context: eventContext,
			score: 4,
			comments: 'Nice',
		} );
	} );

	it( 'fires gla_ces_feedback with empty comments when none are given', () => {
		renderPrompt().recordScoreCallback( 3, 3 );

		expect( recordGlaEvent ).toHaveBeenCalledWith( 'gla_ces_feedback', {
			context: eventContext,
			score: 3,
			comments: '',
		} );
	} );
} );
