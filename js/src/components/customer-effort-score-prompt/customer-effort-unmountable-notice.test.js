/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import useCESNotice from '.~/hooks/useCESNotice';
import CustomerEffortScoreUnmountableNotice from '.~/components/customer-effort-score-prompt/customer-effort-unmountable-notice';

const mockCreateCESNoticeShowModal = jest
	.fn()
	.mockImplementation( ( onClickCallBack ) => onClickCallBack() );

const mockCreateCESNotice = jest.fn();

jest.mock( '.~/hooks/useCESNotice', () => {
	return jest.fn();
} );

describe( 'Customer Effort Notice component', () => {
	let defaultProps;

	beforeEach( () => {
		defaultProps = {
			label: 'label test',
			icon: <span>my icon</span>,
			recordScoreCallback: jest.fn(),
			onNoticeDismissedCallback: jest.fn(),
			onNoticeShownCallback: jest.fn(),
			onModalShownCallback: jest.fn(),
		};
	} );

	test( 'Rendering the CES Notice without the CES modal', async () => {
		useCESNotice.mockImplementation( () => mockCreateCESNotice );

		const { queryByText } = render(
			<CustomerEffortScoreUnmountableNotice { ...defaultProps } />
		);

		//Creates the notice
		expect( mockCreateCESNotice ).toHaveBeenCalledTimes( 1 );
		expect( defaultProps.onNoticeShownCallback ).toHaveBeenCalledTimes( 1 );
		expect( defaultProps.onModalShownCallback ).toHaveBeenCalledTimes( 0 );

		expect( useCESNotice ).toHaveBeenLastCalledWith(
			defaultProps.label,
			defaultProps.icon,
			expect.any( Function ),
			defaultProps.onNoticeDismissedCallback
		);

		//The modal should not be displayed
		expect(
			queryByText( 'Please share your feedback' )
		).not.toBeInTheDocument();

		expect( defaultProps.recordScoreCallback ).toHaveBeenCalledTimes( 0 );
	} );

	test( 'Rendering the CES Notice with the CES modal', async () => {
		useCESNotice.mockImplementation(
			( label, icon, onClickCallBack ) => () =>
				mockCreateCESNoticeShowModal( onClickCallBack )
		);

		const { getByText } = render(
			<CustomerEffortScoreUnmountableNotice { ...defaultProps } />
		);

		//Creates the notice
		expect( mockCreateCESNotice ).toHaveBeenCalledTimes( 1 );
		expect( defaultProps.onModalShownCallback ).toHaveBeenCalledTimes( 1 );
		expect( useCESNotice ).toHaveBeenLastCalledWith(
			defaultProps.label,
			defaultProps.icon,
			expect.any( Function ),
			defaultProps.onNoticeDismissedCallback
		);

		//The modal should be displayed
		expect( getByText( 'Please share your feedback' ) ).toBeInTheDocument();

		//record score
		const score = getByText( 'Very easy' );
		const send = getByText( 'Send' );

		expect( score ).toBeInTheDocument();
		expect( send ).toBeInTheDocument();

		userEvent.click( score );
		userEvent.click( send );
		expect( defaultProps.recordScoreCallback ).toHaveBeenCalledTimes( 1 );
	} );
} );
