/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ShippingInfoNotice from '.';

describe( 'ShippingInfoNotice', () => {
	test( 'renders a Notice with status info and not dismissible', () => {
		const { container } = render(
			<ShippingInfoNotice>Notice content</ShippingInfoNotice>
		);
		const notice = container.querySelector( '.gla-shipping-info-notice' );
		expect( notice ).toBeInTheDocument();
		expect( notice ).toHaveClass( 'is-info' );
		expect(
			container.querySelector( 'button.components-notice__dismiss' )
		).not.toBeInTheDocument();
	} );

	test( 'renders children inside the notice', () => {
		const { container } = render(
			<ShippingInfoNotice>
				<span>Custom content</span>
			</ShippingInfoNotice>
		);
		expect(
			container.querySelector( '.gla-shipping-info-notice' )
		).toHaveTextContent( 'Custom content' );
	} );
} );
