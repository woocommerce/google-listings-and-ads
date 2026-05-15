/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import ShippingInfoNotice from '.';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'ShippingInfoNotice', () => {
	const props = {
		message: 'See the <link>details here</link>.',
		href: 'https://example.com',
		eventName: 'test_event_click',
		eventProps: { url: 'https://example.com' },
		className: 'test-class',
	};

	test( 'renders a Notice with status info and not dismissible', () => {
		const { container } = render( <ShippingInfoNotice { ...props } /> );
		const notice = container.querySelector( '.gla-shipping-info-notice' );
		expect( notice ).toBeInTheDocument();
		expect( notice ).toHaveClass( 'is-info' );
		expect(
			container.querySelector( 'button.components-notice__dismiss' )
		).not.toBeInTheDocument();
	} );

	test( 'renders the message with the link placeholder interpolated as an anchor', () => {
		render( <ShippingInfoNotice { ...props } /> );
		expect(
			screen.getByRole( 'link', { name: /details here/i } )
		).toBeInTheDocument();
	} );

	test( 'link href matches the href prop', () => {
		render( <ShippingInfoNotice { ...props } /> );
		expect( screen.getByRole( 'link' ) ).toHaveAttribute(
			'href',
			props.href
		);
	} );

	test( 'clicking the link fires the tracking event with eventName and eventProps', () => {
		render( <ShippingInfoNotice { ...props } /> );
		fireEvent.click( screen.getByRole( 'link' ) );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			props.eventName,
			props.eventProps
		);
	} );

	test( 'matches snapshot', () => {
		const { asFragment } = render( <ShippingInfoNotice { ...props } /> );
		expect( asFragment() ).toMatchSnapshot();
	} );
} );
