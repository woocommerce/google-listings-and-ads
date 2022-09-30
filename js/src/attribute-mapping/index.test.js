jest.mock( '.~/hooks/useMappingAttributes', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMappingAttributes' )
		.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: [
					{ id: 'adult', label: 'Adult', is_enum: true },
					{ id: 'brands', label: 'Brands', is_enum: false },
					{ id: 'color', label: 'Color', is_enum: false },
				],
			};
		} ),
} ) );

jest.mock( '.~/hooks/useMappingAttributesSources', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMappingAttributesSources' )
		.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: { yes: 'Yes', no: 'No' },
			};
		} ),
} ) );

/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AttributeMapping from './index';
import AttributeMappingTable from '.~/attribute-mapping/attribute-mapping-table';
import AttributeMappingTableCategories from '.~/attribute-mapping/attribute-mapping-table-categories';
import useMappingAttributes from '.~/hooks/useMappingAttributes';

const DUMMY_TABLE_DATA = [
	{
		destination: 'adult',
		source: 'yes',
		source_name: 'Yes',
		category_conditional_type: 'ALL',
	},
	{
		destination: 'brands',
		source: 'taxonomy:product_brands',
		source_name: 'Taxonomy - Product Brands',
		category_conditional_type: 'EXCEPT',
		categories: '1',
	},
	{
		destination: 'color',
		source: 'attribute:color',
		source_name: 'Attribute - Color',
		category_conditional_type: 'ONLY',
		categories:
			'1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3',
	},
];

describe( 'Attribute Mapping', () => {
	test( 'Renders table', () => {
		const { queryByText, queryByRole } = render( <AttributeMapping /> );
		expect( queryByRole( 'table' ) ).toBeTruthy();
		expect( queryByText( 'Target Attribute' ) ).toBeTruthy();
		expect( queryByText( 'Data Source / Default Value' ) ).toBeTruthy();
		expect( queryByText( 'Categories' ) ).toBeTruthy();
	} );

	test( 'Renders table data', () => {
		const { queryByText } = render(
			<AttributeMappingTable rules={ DUMMY_TABLE_DATA } />
		);

		expect( queryByText( 'Adult' ) ).toBeTruthy();
		expect( queryByText( 'Brands' ) ).toBeTruthy();
		expect( queryByText( 'Color' ) ).toBeTruthy();
		expect( queryByText( 'All' ) ).toBeTruthy();
		expect( queryByText( 'All except' ) ).toBeTruthy();
		expect( queryByText( '1 category' ) ).toBeTruthy();
		expect( queryByText( 'Only in' ) ).toBeTruthy();
		expect( queryByText( '54 categories' ) ).toBeTruthy();
	} );

	test( 'Add new Attribute mapping button', async () => {
		const { queryByText, findByRole, findByText } = render(
			<AttributeMapping />
		);

		// Modal is open when clicking th button
		const button = queryByText( 'Create attribute rule' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );

		// Show select option when enum attribute is selected
		const select = await findByRole( 'combobox', {
			name: 'Select a Google attribute that you want to manage',
		} );
		userEvent.selectOptions( select, 'adult' );
		await findByText( 'Select one option' );

		// Show type selector when no enum is selected
		userEvent.selectOptions( select, 'brands' );
		await findByText(
			'Choose how to assign a value to the target attribute'
		);

		// Show fixed value when we check "Set a fixed value" radio
		const setFixedRadio = await findByRole( 'radio', {
			name: 'Set a fixed value.',
		} );
		userEvent.click( setFixedRadio );
		await findByRole( 'textbox' );

		// Show selector value when we check "Use value from existing product field" radio
		const setFieldRadio = await findByRole( 'radio', {
			name: 'Use value from existing product field.',
		} );
		userEvent.click( setFieldRadio );
		await findByRole( 'combobox', {
			name: 'Use value from existing product field.',
		} );
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

	test( 'Renders categories helper', async () => {
		const { queryByText, findByText } = render(
			<AttributeMappingTableCategories
				categories={ '1' }
				condition="EXCEPT"
			/>
		);
		expect( queryByText( 'All except' ) ).toBeTruthy();
		const category = queryByText( '1 category' );
		expect( category ).toBeTruthy();
		fireEvent.mouseOver( category );
		await findByText( 'Category 1' );
	} );

	test( 'Renders categories helper with tons of categories', async () => {
		const { queryByText, findByText } = render(
			<AttributeMappingTableCategories
				categories={ '1,2,3,1,2,3,1,2,3,1,2,3' }
				condition="ONLY"
			/>
		);

		expect( queryByText( 'Only in' ) ).toBeTruthy();
		const categories = queryByText( '12 categories' );
		expect( categories ).toBeTruthy();
		fireEvent.mouseOver( categories );
		await findByText(
			'Category 1, Category 2, Category 3, Category 1, Category 2'
		);
		await findByText( '+ 7 more' );
	} );

	test( 'Renders placeholder table when data is has not finished resolution', () => {
		useMappingAttributes.mockReturnValue( {
			hasFinishedResolution: false,
			data: [],
		} );

		const { queryByText } = render( <AttributeMapping /> );
		expect( queryByText( 'Loading Attribute Mapping rules' ) ).toBeTruthy();
	} );
} );
