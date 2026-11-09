/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CustomerEffortUnmountableNotice from './notice';

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn().mockName( 'useDispatch' ),
} ) );

jest.mock( '@woocommerce/customer-effort-score', () => ( {
	CustomerFeedbackModal: ( {
		title,
		firstQuestion,
		secondQuestion,
		recordScoreCallback,
		onCloseModal,
	} ) => (
		<div role="dialog" aria-label={ title }>
			<p>{ firstQuestion }</p>
			<p>{ secondQuestion }</p>
			<button onClick={ () => recordScoreCallback( 4, 5, 'Nice' ) }>
				Send
			</button>
			<button onClick={ onCloseModal }>Close</button>
		</div>
	),
} ) );

describe( 'CustomerEffortUnmountableNotice', () => {
	let createNotice;
	let removeNotice;
	let props;

	const getNotice = ( callIndex = 0 ) => {
		const [ status, content, options ] =
			createNotice.mock.calls[ callIndex ];
		return { status, content, options };
	};

	const clickGiveFeedback = () => {
		const { options } = getNotice();
		act( () => options.actions[ 0 ].onClick() );
	};

	beforeEach( () => {
		createNotice = jest.fn().mockName( 'createNotice' );
		removeNotice = jest.fn().mockName( 'removeNotice' );
		useDispatch.mockReturnValue( { createNotice, removeNotice } );

		props = {
			title: 'How easy was it?',
			firstQuestion: 'First question',
			secondQuestion: 'Second question',
			icon: <span>icon</span>,
			recordScoreCallback: jest.fn(),
			onNoticeShownCallback: jest.fn(),
			onNoticeDismissedCallback: jest.fn(),
			onModalShownCallback: jest.fn(),
			onModalDismissedCallback: jest.fn(),
		};
	} );

	it( 'creates one notice with the "Give feedback" action on mount', () => {
		render( <CustomerEffortUnmountableNotice { ...props } /> );

		expect( useDispatch ).toHaveBeenCalledWith( 'core/notices2' );
		expect( createNotice ).toHaveBeenCalledTimes( 1 );

		const { status, content, options } = getNotice();
		expect( status ).toBe( 'success' );
		expect( content ).toBe( props.title );
		expect( options ).toEqual(
			expect.objectContaining( {
				id: expect.stringMatching( /^gla-ces-prompt-/ ),
				icon: props.icon,
				explicitDismiss: true,
			} )
		);
		expect( options.actions ).toEqual( [
			{ label: 'Give feedback', onClick: expect.any( Function ) },
		] );

		expect( props.onNoticeShownCallback ).toHaveBeenCalledTimes( 1 );
		expect( props.onModalShownCallback ).not.toHaveBeenCalled();
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'does not create a second notice when it re-renders', () => {
		const { rerender } = render(
			<CustomerEffortUnmountableNotice { ...props } />
		);

		rerender(
			<CustomerEffortUnmountableNotice
				{ ...props }
				onNoticeShownCallback={ jest.fn() }
				onModalShownCallback={ jest.fn() }
			/>
		);

		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( props.onNoticeShownCallback ).toHaveBeenCalledTimes( 1 );
		expect( removeNotice ).not.toHaveBeenCalled();
	} );

	it( 'removes the notice by its id on unmount', () => {
		const { unmount } = render(
			<CustomerEffortUnmountableNotice { ...props } />
		);
		const { options } = getNotice();

		unmount();

		expect( removeNotice ).toHaveBeenCalledTimes( 1 );
		expect( removeNotice ).toHaveBeenCalledWith( options.id );
	} );

	it( 'uses a different notice id for each instance', () => {
		render( <CustomerEffortUnmountableNotice { ...props } /> );
		render( <CustomerEffortUnmountableNotice { ...props } /> );

		expect( getNotice( 0 ).options.id ).not.toBe(
			getNotice( 1 ).options.id
		);
	} );

	it( 'calls the latest dismiss callback when the notice is dismissed', () => {
		const onNoticeDismissedCallback = jest.fn();
		const { rerender } = render(
			<CustomerEffortUnmountableNotice { ...props } />
		);
		rerender(
			<CustomerEffortUnmountableNotice
				{ ...props }
				onNoticeDismissedCallback={ onNoticeDismissedCallback }
			/>
		);

		getNotice().options.onDismiss();

		expect( onNoticeDismissedCallback ).toHaveBeenCalledTimes( 1 );
		expect( props.onNoticeDismissedCallback ).not.toHaveBeenCalled();
	} );

	it( 'opens the feedback modal from the "Give feedback" action', () => {
		render( <CustomerEffortUnmountableNotice { ...props } /> );

		clickGiveFeedback();

		expect( props.onModalShownCallback ).toHaveBeenCalledTimes( 1 );

		const modal = screen.getByRole( 'dialog', { name: props.title } );
		expect( modal ).toHaveTextContent( props.firstQuestion );
		expect( modal ).toHaveTextContent( props.secondQuestion );
	} );

	it( 'records the score and closes the modal when the feedback is sent', async () => {
		render( <CustomerEffortUnmountableNotice { ...props } /> );
		clickGiveFeedback();

		await userEvent.click( screen.getByRole( 'button', { name: 'Send' } ) );

		expect( props.recordScoreCallback ).toHaveBeenCalledTimes( 1 );
		expect( props.recordScoreCallback ).toHaveBeenCalledWith(
			4,
			5,
			'Nice'
		);
		expect( props.onModalDismissedCallback ).not.toHaveBeenCalled();
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'closes the modal without recording a score when it is dismissed', async () => {
		render( <CustomerEffortUnmountableNotice { ...props } /> );
		clickGiveFeedback();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Close' } )
		);

		expect( props.onModalDismissedCallback ).toHaveBeenCalledTimes( 1 );
		expect( props.recordScoreCallback ).not.toHaveBeenCalled();
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );
} );
