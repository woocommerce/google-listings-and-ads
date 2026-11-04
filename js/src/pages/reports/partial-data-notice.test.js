/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PartialDataNotice from './partial-data-notice';

describe( 'PartialDataNotice', () => {
	it( 'renders the notice when the report data is truncated', () => {
		const { container } = render( <PartialDataNotice truncated /> );
		const notice = container.querySelector(
			'.gla-reports-partial-data-notice'
		);

		expect( notice ).not.toBeNull();
		expect( notice.textContent ).toContain(
			'parts of this page may be incomplete'
		);
	} );

	it.each( [ false, undefined, null ] )(
		'renders nothing when truncated is %s',
		( truncated ) => {
			const { container } = render(
				<PartialDataNotice truncated={ truncated } />
			);

			expect( container.firstChild ).toBeNull();
		}
	);
} );
