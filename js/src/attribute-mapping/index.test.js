/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import AttributeMapping from './index';

describe( 'Attribute Mapping', () => {
	test( 'Renders table', () => {
		const { queryByText, queryByRole } = render( <AttributeMapping /> );
		expect( queryByRole( 'table' ) ).toBeTruthy();
		expect( queryByText( 'Target Attribute' ) ).toBeTruthy();
		expect( queryByText( 'Reference Field / Default Value' ) ).toBeTruthy();
		expect( queryByText( 'Categories' ) ).toBeTruthy();
	} );

	test( 'Renders Add new Attribute mapping button', () => {
		const { queryByText } = render( <AttributeMapping /> );
		expect( queryByText( 'Add new attribute mapping' ) ).toBeTruthy();
	} );

	test( 'Renders Section title, description and documentation link', () => {
		const { queryByText } = render( <AttributeMapping /> );
		expect( queryByText( 'Attribute Mapping' ) ).toBeTruthy();
		expect(
			queryByText(
				"Automatically populate Google’s required attributes by mapping them to your store's existing product fields. Whenever you make changes to the value of your product fields, it instantly updates where it’s referenced."
			)
		).toBeTruthy();
		expect(
			queryByText(
				'You can override default values at specific product (or variant) level to give you the most flexibility.'
			)
		).toBeTruthy();

		const button = queryByText( 'Learn more about attribute mapping' );

		expect( button ).toBeTruthy();
		expect( button ).toHaveAttribute(
			'href',
			'https://support.google.com/'
		); // TODO: Update link
	} );
} );
