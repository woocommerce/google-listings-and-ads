jest.mock( '@wordpress/date', () => {
	return {
		format: jest
			.fn()
			.mockName( 'createMappingRule' )
			.mockReturnValue( 'November 1, 2022, 10:37 pm' ),
	};
} );

jest.mock( '.~/hooks/useTour', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useTour' )
		.mockImplementation( () => {
			return {
				tour: undefined,
				showTour: false,
				setTour: jest.fn(),
			};
		} ),
} ) );

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

jest.mock( '.~/hooks/useAppSelectDispatch', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useAppSelectDispatch' )
		.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: [
					{ id: 1, name: 'Category 1', parent: 0 },
					{ id: 2, name: 'Category 2', parent: 0 },
					{ id: 3, name: 'Category 3', parent: 0 },
					{ id: 4, name: 'Category 3.1', parent: 3 },
				],
			};
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

jest.mock( '.~/hooks/usePolling', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'usePolling' )
		.mockImplementation( () => {
			return {
				start: () => {},
				data: { is_scheduled: false, last_sync: null },
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
import AttributeMappingSync from '.~/attribute-mapping/attribute-mapping-sync';
import usePolling from '.~/hooks/usePolling';

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
			name: 'Set a fixed value',
		} );
		userEvent.click( setFixedRadio );
		await findByRole( 'textbox' );

		// Show selector value when we check "Use value from existing product field" radio
		const setFieldRadio = await findByRole( 'radio', {
			name: 'Use value from existing product field',
		} );
		userEvent.click( setFieldRadio );
		await findByRole( 'combobox', {
			name: 'Use value from existing product field',
		} );
	} );

	test( 'Update Attribute mapping Rule', async () => {
		const { queryAllByText, queryByText, findByRole } = render(
			<AttributeMapping />
		);

		const button = queryAllByText( 'Edit' )[ 0 ];
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

	test( 'Renders Section title and description.', () => {
		const { queryByText } = render( <AttributeMapping /> );
		expect( queryByText( 'Manage attributes' ) ).toBeTruthy();
		expect(
			queryByText(
				'Create attribute rules to control what product data gets sent to Google and to manage product attributes in bulk.'
			)
		).toBeTruthy();
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

	test( 'Shows deleted categories', async () => {
		const { queryByText, findByText } = render(
			<AttributeMappingTableCategories
				categories={ '1,6' }
				condition="ONLY"
			/>
		);

		expect( queryByText( 'Only in' ) ).toBeTruthy();
		const categories = queryByText( '2 categories' );
		expect( categories ).toBeTruthy();
		userEvent.hover( categories );
		await findByText( 'Category 1, Category ID 6 (deleted)' );
	} );

	test( 'Renders placeholder table when data is has not finished resolution', () => {
		useMappingAttributes.mockReturnValue( {
			hasFinishedResolution: false,
			data: [],
		} );

		const { queryByText } = render( <AttributeMapping /> );
		expect( queryByText( 'Loading Attribute Mapping rules' ) ).toBeTruthy();
	} );

	test( 'Syncer is never', () => {
		const { queryByText } = render( <AttributeMappingSync /> );
		expect( queryByText( 'Never' ) ).toBeTruthy();
	} );

	test( 'Syncer is on a valid date', () => {
		usePolling.mockReturnValue( {
			start: () => {},
			data: { is_scheduled: false, last_sync: 1667338631 },
		} );
		const { queryByText } = render( <AttributeMappingSync /> );
		expect( queryByText( 'November 1, 2022, 10:37 pm' ) ).toBeTruthy();
	} );

	test( 'Syncer is scheduled for syncing', () => {
		usePolling.mockReturnValue( {
			start: () => {},
			data: { is_scheduled: true, last_sync: 1667338631 },
		} );

		const { queryByText } = render( <AttributeMappingSync /> );
		expect( queryByText( 'Scheduled for sync' ) ).toBeTruthy();
	} );
} );
