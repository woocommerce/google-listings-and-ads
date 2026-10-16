/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import ShippingRateNotice from '.';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'ShippingRateNotice', () => {
	test( 'renders the WC shipping settings link with the correct href', () => {
		render( <ShippingRateNotice /> );
		expect( screen.getByRole( 'link' ) ).toHaveAttribute(
			'href',
			'admin.php?page=wc-settings&tab=shipping'
		);
	} );

	test( 'fires the tracking event when the link is clicked', () => {
		render( <ShippingRateNotice /> );
		fireEvent.click( screen.getByRole( 'link' ) );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_shipping_rate_notice_shipping_settings_link_click',
			undefined
		);
	} );
} );
