/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import AttributeMapping from '.~/settings/attribute-mapping/index';

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

	test( 'Renders Navigation', () => {
		const { queryAllByRole } = render( <AttributeMapping /> );
		const tabs = queryAllByRole( 'tab' );

		expect( tabs ).toHaveLength( 2 );

		// General Tab
		expect( tabs[ 0 ] ).toHaveTextContent( 'General' );
		expect( tabs[ 0 ] ).toHaveAttribute( 'id', '/google/settings' );
		expect( tabs[ 0 ] ).toHaveAttribute(
			'href',
			'admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings'
		);

		// Attribute Mapping tab
		expect( tabs[ 1 ] ).toHaveTextContent( 'Attribute Mapping' );
		expect( tabs[ 1 ] ).toHaveAttribute( 'id', '/attribute-mapping' );
		expect( tabs[ 1 ] ).toHaveAttribute(
			'href',
			'admin.php?page=wc-admin&subpath=%2Fattribute-mapping&path=%2Fgoogle%2Fsettings'
		);
	} );

	test( 'Renders Section title, description and documentation link', () => {
		const { queryByText, queryAllByText } = render( <AttributeMapping /> );
		expect( queryAllByText( 'Attribute Mapping' ) ).toHaveLength( 2 );
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
