jest.mock( '.~/data/actions', () => ( {
	__esModule: true,
	createMappingRule: jest
		.fn()
		.mockName( 'createMappingRule' )
		.mockImplementation( ( args ) => {
			return { ...args };
		} ),
	updateMappingRule: jest
		.fn()
		.mockName( 'updateMappingRule' )
		.mockImplementation( ( args ) => {
			return { ...args };
		} ),
	deleteMappingRule: jest
		.fn()
		.mockName( 'deleteMappingRule' )
		.mockImplementation( ( args ) => {
			return { ...args };
		} ),
} ) );

jest.mock( '.~/hooks/useMappingAttributes', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMappingAttributes' )
		.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: [
					{ id: 'adult', label: 'Adult', enum: true },
					{ id: 'brands', label: 'Brands', enum: false },
					{ id: 'color', label: 'Color', enum: false },
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
				data: [
					{ id: 'yes', label: 'Yes' },
					{ id: 'no', label: 'No' },
				],
			};
		} ),
} ) );

jest.mock( '.~/hooks/useMappingRules', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMappingRules' )
		.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: {
					rules: [
						{
							id: 1,
							attribute: 'adult',
							source: 'yes',
							category_condition_type: 'ALL',
							categories: null,
						},
						{
							id: 2,
							attribute: 'brands',
							source: 'taxonomy:product_brands',
							category_condition_type: 'EXCEPT',
							categories: '1',
						},
						{
							id: 3,
							attribute: 'color',
							source: 'attribute:color',
							category_condition_type: 'ONLY',
							categories:
								'1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3,1,2,3',
						},
					],
					total: 6,
				},
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
import {
	createMappingRule,
	deleteMappingRule,
	updateMappingRule,
} from '.~/data/actions';

describe( 'Attribute Mapping', () => {
	test( 'Renders table', () => {
		const { queryByText, queryByRole } = render( <AttributeMapping /> );
		expect( queryByRole( 'table' ) ).toBeTruthy();
		expect( queryByText( 'Target Attribute' ) ).toBeTruthy();
		expect( queryByText( 'Data Source / Default Value' ) ).toBeTruthy();
		expect( queryByText( 'Categories' ) ).toBeTruthy();
	} );

	test( 'Renders table data', () => {
		const { queryByText } = render( <AttributeMappingTable /> );

		expect( queryByText( 'Adult' ) ).toBeTruthy();
		expect( queryByText( 'Brands' ) ).toBeTruthy();
		expect( queryByText( 'Color' ) ).toBeTruthy();
		expect( queryByText( 'All' ) ).toBeTruthy();
		expect( queryByText( 'All except' ) ).toBeTruthy();
		expect( queryByText( '1 category' ) ).toBeTruthy();
		expect( queryByText( 'Only in' ) ).toBeTruthy();
		expect( queryByText( '54 categories' ) ).toBeTruthy();
	} );

	test( 'Add new Attribute mapping - Enum', async () => {
		const { queryByText, findByRole } = render( <AttributeMapping /> );

		// Modal is open when clicking th button
		const button = queryByText( 'Create attribute rule' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );

		// Show select option when enum attribute is selected
		const select = await findByRole( 'combobox', {
			name: 'Select a Google attribute that you want to manage',
		} );

		const buttonSave = queryByText( 'Save rule' );
		expect( buttonSave ).toBeTruthy();
		expect( buttonSave ).toBeDisabled();

		userEvent.selectOptions( select, 'adult' );
		const enumSelect = await findByRole( 'combobox', {
			name: 'Select default value',
		} );
		userEvent.selectOptions( enumSelect, 'no' );

		expect( buttonSave ).toBeEnabled();
		fireEvent.click( buttonSave );

		expect( createMappingRule ).toHaveBeenCalledWith( {
			attribute: 'adult',
			source: 'no',
			category_condition_type: 'ALL',
			categories: '',
		} );
	} );

	test( 'Add new Attribute mapping - Field / Fixed value', async () => {
		const { queryByText, findByRole } = render( <AttributeMapping /> );

		// Modal is open when clicking th button
		const button = queryByText( 'Create attribute rule' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );

		const select = await findByRole( 'combobox', {
			name: 'Select a Google attribute that you want to manage',
		} );
		userEvent.selectOptions( select, 'brands' );

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

	test( 'Update Attribute mapping Rule', async () => {
		const { queryAllByText, queryByText, findByRole } = render(
			<AttributeMapping />
		);

		const button = queryAllByText( 'Manage' )[ 0 ];
		expect( button ).toBeTruthy();
		fireEvent.click( button );

		const enumSelect = await findByRole( 'combobox', {
			name: 'Select default value',
		} );

		const buttonSave = queryByText( 'Save rule' );
		expect( buttonSave ).toBeDisabled();
		userEvent.selectOptions( enumSelect, 'no' );
		expect( buttonSave ).toBeEnabled();
		fireEvent.click( buttonSave );

		expect( updateMappingRule ).toHaveBeenCalledWith( {
			id: 1,
			attribute: 'adult',
			source: 'no',
			categories: '',
			category_condition_type: 'ALL',
		} );
	} );

	test( 'Delete Attribute mapping Rule', async () => {
		const { queryAllByText, queryByText } = render( <AttributeMapping /> );

		const button = queryAllByText( 'Delete' )[ 0 ];
		expect( button ).toBeTruthy();
		fireEvent.click( button );

		const buttonDelete = queryByText( 'Delete attribute rule' );
		expect( buttonDelete ).toBeTruthy();
		fireEvent.click( buttonDelete );

		expect( deleteMappingRule ).toHaveBeenCalledWith( {
			id: 1,
			attribute: 'adult',
			source: 'yes',
			category_condition_type: 'ALL',
			categories: null,
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
		userEvent.hover( category );
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
		userEvent.hover( categories );
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
