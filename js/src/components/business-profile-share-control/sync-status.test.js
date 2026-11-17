/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SyncStatus from './sync-status';

const SENDING_TEXT = 'Sending to Google Business Profile…';

describe( 'SyncStatus', () => {
	it( 'says the post is on its way, with a spinner, while the send is queued', () => {
		const { container } = render(
			<SyncStatus syncOutcome={ { outcome: 'queued' } } />
		);

		expect( screen.getByText( SENDING_TEXT ) ).toBeInTheDocument();
		expect(
			container.querySelector( '.components-spinner' )
		).toBeInTheDocument();
	} );

	it.each( [ 'error', 'warning' ] )(
		'shows the message for an outcome needing attention as a %s notice',
		( status ) => {
			const { container } = render(
				<SyncStatus
					syncOutcome={ {
						outcome: 'sent-pending-verification',
						display_message: 'Your profile is unverified.',
						display_status: status,
					} }
				/>
			);

			expect(
				screen.getByText( 'Your profile is unverified.', {
					selector: '.components-notice__content',
				} )
			).toBeInTheDocument();
			expect(
				container.querySelector( `.components-notice.is-${ status }` )
			).toBeInTheDocument();
		}
	);

	it( 'shows a message without a severity as an error', () => {
		const { container } = render(
			<SyncStatus
				syncOutcome={ {
					outcome: 'request-failure',
					display_message: 'Google couldn’t be reached.',
				} }
			/>
		);

		expect(
			container.querySelector( '.components-notice.is-error' )
		).toBeInTheDocument();
	} );

	it.each( [
		[ 'never sent', null ],
		[ 'shared with nothing to report', { outcome: 'success' } ],
	] )( 'renders nothing for a post %s', ( label, syncOutcome ) => {
		const { container } = render(
			<SyncStatus syncOutcome={ syncOutcome } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
