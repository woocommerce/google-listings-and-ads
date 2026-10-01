/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConflictNotice from './conflict-notice';

jest.mock( '~/components/app-notice', () => ( { children, onRemove } ) => (
	<div>
		{ children }
		<button onClick={ onRemove }>Dismiss</button>
	</div>
) );

const CONTAINER_PUBLIC_ID = 'GTM-ABC1234';

describe( 'ConflictNotice', () => {
	it( 'warns about the conflict, naming the connected container', () => {
		render( <ConflictNotice containerPublicId={ CONTAINER_PUBLIC_ID } /> );

		expect(
			screen.getByText(
				'The connected Google Tag Manager container (GTM-ABC1234) contains a Google Ads Conversion script. Google Ads events are already captured natively by the plugin. Enabling this tag can lead to duplicate events registration.'
			)
		).toBeInTheDocument();
	} );

	it( 'hides when dismissed', () => {
		const { container } = render(
			<ConflictNotice containerPublicId={ CONTAINER_PUBLIC_ID } />
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows again on the next visit after being dismissed', () => {
		const { unmount } = render(
			<ConflictNotice containerPublicId={ CONTAINER_PUBLIC_ID } />
		);
		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );
		unmount();

		render( <ConflictNotice containerPublicId={ CONTAINER_PUBLIC_ID } /> );

		expect(
			screen.getByText( /contains a Google Ads Conversion script/ )
		).toBeInTheDocument();
	} );
} );
