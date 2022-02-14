/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ReviewRequestModal from '.~/product-feed/review-request/review-request-modal';

describe( 'Request Review Modal', () => {
	it( 'Renders when is active and there are issues', () => {
		const { queryByRole } = render(
			<ReviewRequestModal issues={ [ '#1', '#2' ] } isActive={ true } />
		);

		expect( queryByRole( 'dialog' ) ).toBeTruthy();
	} );

	it( "Doesn't render if its not active", () => {
		const { queryByRole } = render(
			<ReviewRequestModal issues={ [ '#1', '#2' ] } isActive={ false } />
		);

		expect( queryByRole( 'dialog' ) ).toBeFalsy();
	} );

	it( "Doesn't render when no issues", () => {
		const { queryByRole } = render(
			<ReviewRequestModal issues={ [] } isActive={ true } />
		);

		expect( queryByRole( 'dialog' ) ).toBeFalsy();
	} );

	it( 'Shows maximum 5 issues and can expand the list of issues', () => {
		const { queryByText } = render(
			<ReviewRequestModal
				issues={ [ '#1', '#2', '#3', '#4', '#5', '#6' ] }
				isActive={ true }
			/>
		);

		expect( queryByText( '#1' ) ).toBeTruthy();
		expect( queryByText( '#2' ) ).toBeTruthy();
		expect( queryByText( '#3' ) ).toBeTruthy();
		expect( queryByText( '#4' ) ).toBeTruthy();
		expect( queryByText( '#5' ) ).toBeTruthy();
		expect( queryByText( '#6' ) ).toBeFalsy();

		const button = queryByText( '+ 1 more issue(s)' );
		expect( queryByText( 'Show less' ) ).toBeFalsy();
		expect( button ).toBeTruthy();
		fireEvent.click( button );
		expect( queryByText( '#6' ) ).toBeTruthy();
		expect( queryByText( 'Show less' ) ).toBeTruthy();
	} );

	it( 'Cancel button closes the modal', () => {
		const onClose = jest.fn().mockName( 'onClose' );
		const { queryByText } = render(
			<ReviewRequestModal
				issues={ [ '#1', '#2', '#3', '#4', '#5', '#6' ] }
				isActive={ true }
				onClose={ onClose }
			/>
		);

		const button = queryByText( 'Cancel' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );
		expect( onClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Request review button is active after checking the checkbox and it calls onSendRequest on click', () => {
		const onSendRequest = jest.fn().mockName( 'onSendRequest' );
		const { queryByRole } = render(
			<ReviewRequestModal
				issues={ [ '#1', '#2', '#3', '#4', '#5', '#6' ] }
				isActive={ true }
				onSendRequest={ onSendRequest }
			/>
		);

		const button = queryByRole( 'button', {
			name: 'Request account review',
		} );
		const checkbox = queryByRole( 'checkbox' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );
		expect( onSendRequest ).not.toHaveBeenCalled();

		expect( checkbox ).toBeTruthy();
		expect( checkbox.checked ).toEqual( false );
		fireEvent.click( checkbox );
		expect( checkbox.checked ).toEqual( true );
		fireEvent.click( button );
		expect( onSendRequest ).toHaveBeenCalledTimes( 1 );
	} );
} );
