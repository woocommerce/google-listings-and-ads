/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import DismissibleNotice from '.~/components/dismissible-notice';
import localStorage from '.~/utils/localStorage';

jest.mock( '.~/utils/localStorage', () => {
	return {
		get: jest.fn(),
		set: jest.fn(),
	};
} );

describe( 'Dismissible notice', () => {
	const onRemove = jest.fn().mockName( 'On remove callback' );
	const localStorageKey = 'myDismissLocalStorageKey';

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'Rendering Dismissible Notice', () => {
		//using spokenMessage='' to avoid accessibility message.
		//See https://make.wordpress.org/accessibility/handbook/markup/wp-a11y-speak/#the-generated-output
		const { getByText, getByRole, container } = render(
			<DismissibleNotice spokenMessage="" className="myClassName">
				<p>My dismissible notice</p>
			</DismissibleNotice>
		);

		expect( container.firstChild.classList.contains( 'myClassName' ) ).toBe(
			true
		);
		const noticeContent = getByText( 'My dismissible notice' );
		const removeButton = getByRole( 'button' );

		expect( noticeContent ).toBeTruthy();
		expect( removeButton ).toBeTruthy();
	} );

	it( 'Rendering Dismissible Notice with onRemove callback', () => {
		const getLocalStorage = localStorage.get.mockImplementation( () => {
			return null;
		} );

		const { getByText, getByRole, queryByText } = render(
			<DismissibleNotice onRemove={ onRemove } spokenMessage="">
				<p>My dismissible notice</p>
			</DismissibleNotice>
		);

		expect( getByText( 'My dismissible notice' ) ).toBeTruthy();

		const removeButton = getByRole( 'button' );
		expect( removeButton ).toBeTruthy();

		fireEvent.click( removeButton );

		expect( onRemove ).toHaveBeenCalledTimes( 1 );
		expect( getLocalStorage ).toHaveBeenCalledTimes( 0 );

		expect( queryByText( 'My dismissible notice' ) ).toBeFalsy();
	} );

	it( 'Rendering Dismissible Notice with localStorageKey', () => {
		const getLocalStorage = localStorage.get.mockImplementation( () => {
			return null;
		} );
		const setLocalStorage = localStorage.set.mockImplementation( () => {
			return null;
		} );

		const { getByRole, queryByText } = render(
			<DismissibleNotice
				onRemove={ onRemove }
				localStorageKey={ localStorageKey }
				spokenMessage=""
			>
				<p>My dismissible notice</p>
			</DismissibleNotice>
		);

		expect( getLocalStorage ).toHaveBeenCalledTimes( 1 );
		expect( getLocalStorage ).toBeCalledWith( localStorageKey );

		const removeButton = getByRole( 'button' );
		expect( removeButton ).toBeTruthy();

		fireEvent.click( removeButton );

		expect( onRemove ).toHaveBeenCalledTimes( 1 );
		expect( setLocalStorage ).toHaveBeenCalledTimes( 1 );
		expect( setLocalStorage ).toBeCalledWith( localStorageKey, true );

		expect( queryByText( 'My dismissible notice' ) ).toBeFalsy();
	} );

	it( 'Should not render Dismissible Notice if the localStorageKey is set to true', () => {
		const getLocalStorage = localStorage.get.mockImplementation( () => {
			return 'true';
		} );

		const { queryByText } = render(
			<DismissibleNotice
				onRemove={ onRemove }
				localStorageKey={ localStorageKey }
				spokenMessage=""
			>
				<p>My dismissible notice</p>
			</DismissibleNotice>
		);

		expect( queryByText( 'My dismissible notice' ) ).toBeFalsy();
		expect( getLocalStorage ).toHaveBeenCalledTimes( 1 );
		expect( onRemove ).toHaveBeenCalledTimes( 0 );
	} );
} );
