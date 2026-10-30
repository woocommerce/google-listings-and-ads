/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, renderHook, screen } from '@testing-library/react';
import { filterSortAndPaginate } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import useProgramsDataViewsConfig, {
	PMAX_TITLE_CLASS_NAME,
} from './useProgramsDataViewsConfig';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import { getActionedCampaigns } from '~/utils/actionedCampaignsCache';

jest.mock( '~/hooks/useAdsCampaigns', () =>
	jest.fn().mockName( 'useAdsCampaigns' )
);

jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn().mockName( 'useTargetAudienceFinalCountryCodes' )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockName( 'useRaiseBudgetRecommendations' )
);

jest.mock( '~/hooks/useCountryKeyNameMap', () =>
	jest.fn().mockReturnValue( {
		US: 'United States (US)',
		JP: 'Japan',
		AU: 'Australia',
		GB: 'United Kingdom (UK)',
	} )
);

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( {
		formatAmount: ( value ) => `$${ Number( value ).toFixed( 2 ) }`,
	} )
);

jest.mock( '~/utils/actionedCampaignsCache', () => ( {
	getActionedCampaigns: jest.fn().mockReturnValue( [] ),
} ) );

const campaignA = {
	id: 1,
	name: 'Alpha',
	status: 'enabled',
	type: 'performance_max',
	amount: 20,
	displayCountries: [ 'GB' ],
};

const campaignB = {
	id: 2,
	name: 'Bravo',
	status: 'paused',
	type: 'shopping',
	amount: 5,
	displayCountries: [ 'AU', 'US' ],
};

const sortedIds = ( items, fields, sort ) =>
	filterSortAndPaginate(
		items,
		{ type: 'table', sort, fields: fields.map( ( f ) => f.id ) },
		fields
	).data.map( ( item ) => item.id );

const renderField = ( fields, fieldId, item ) => {
	const field = fields.find( ( { id } ) => id === fieldId );
	return render( <field.render item={ item } field={ field } /> );
};

describe( 'useProgramsDataViewsConfig', () => {
	beforeEach( () => {
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			data: [ 'US', 'JP', 'AU' ],
		} );
		useAdsCampaigns.mockReturnValue( {
			data: [ campaignA, campaignB ],
		} );
		useRaiseBudgetRecommendations.mockReturnValue( { campaigns: [] } );
		getActionedCampaigns.mockReturnValue( [] );
	} );

	it( 'returns the product feed followed by every campaign', () => {
		const { result } = renderHook( () => useProgramsDataViewsConfig() );
		const [ productFeed, ...campaigns ] = result.current.items;

		expect( result.current.isLoading ).toBe( false );
		expect( productFeed ).toMatchObject( {
			id: 0,
			name: 'Product feed',
			status: 'enabled',
			amount: 0,
			countryCodes: [ 'US', 'JP', 'AU' ],
			isFreeListings: true,
		} );
		expect( campaigns.map( ( { id } ) => id ) ).toEqual( [ 1, 2 ] );
	} );

	it( 'returns only the product feed when there are no campaigns', () => {
		useAdsCampaigns.mockReturnValue( { data: [] } );
		const { result } = renderHook( () => useProgramsDataViewsConfig() );

		expect( result.current.items.map( ( { id } ) => id ) ).toEqual( [ 0 ] );
	} );

	it.each( [
		[ 'campaigns', { data: undefined }, { data: [ 'US' ] } ],
		[ 'final country codes', { data: [] }, { data: undefined } ],
	] )(
		'returns no items and isLoading while %s are unresolved',
		( _, campaignsResult, countryCodesResult ) => {
			useAdsCampaigns.mockReturnValue( campaignsResult );
			useTargetAudienceFinalCountryCodes.mockReturnValue(
				countryCodesResult
			);
			const { result } = renderHook( () => useProgramsDataViewsConfig() );

			expect( result.current.isLoading ).toBe( true );
			expect( result.current.items ).toEqual( [] );
		}
	);

	it( 'normalizes any status other than enabled to paused', () => {
		useAdsCampaigns.mockReturnValue( {
			data: [ { ...campaignA, status: 'disabled' } ],
		} );
		const { result } = renderHook( () => useProgramsDataViewsConfig() );

		expect( result.current.items[ 1 ].status ).toBe( 'paused' );
	} );

	describe( 'sorting across all items', () => {
		it( 'sorts by program name', () => {
			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			expect(
				sortedIds( items, fields, {
					field: 'title',
					direction: 'asc',
				} )
			).toEqual( [ 1, 2, 0 ] );
			expect(
				sortedIds( items, fields, {
					field: 'title',
					direction: 'desc',
				} )
			).toEqual( [ 0, 2, 1 ] );
		} );

		it( 'sorts by daily budget numerically with the product feed as 0', () => {
			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			expect(
				sortedIds( items, fields, {
					field: 'budget',
					direction: 'asc',
				} )
			).toEqual( [ 0, 2, 1 ] );
		} );

		it( 'sorts by joined country codes', () => {
			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			// "AU-US" < "GB" < "US-JP-AU"
			expect(
				sortedIds( items, fields, {
					field: 'country',
					direction: 'asc',
				} )
			).toEqual( [ 2, 1, 0 ] );
		} );

		it( 'sorts active programs before paused ones in ascending order', () => {
			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			expect(
				sortedIds( items, fields, {
					field: 'status',
					direction: 'asc',
				} ).at( -1 )
			).toBe( 2 );
			expect(
				sortedIds( items, fields, {
					field: 'status',
					direction: 'desc',
				} )[ 0 ]
			).toBe( 2 );
		} );
	} );

	it( 'disables filtering on the status field', () => {
		const { result } = renderHook( () => useProgramsDataViewsConfig() );
		const statusField = result.current.fields.find(
			( { id } ) => id === 'status'
		);

		expect( statusField.elements ).toHaveLength( 2 );
		expect( statusField.filterBy ).toEqual( { operators: [] } );
	} );

	it( 'only allows hiding the non-title fields', () => {
		const { result } = renderHook( () => useProgramsDataViewsConfig() );
		const hideable = result.current.fields.map(
			( field ) => field.enableHiding !== false
		);

		expect( hideable ).toEqual( [ false, true, true, true ] );
	} );

	describe( 'rendering', () => {
		it( 'renders the budget as Free for the product feed and formatted for campaigns', () => {
			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			renderField( fields, 'budget', items[ 0 ] );
			expect( screen.getByText( 'Free' ) ).toBeInTheDocument();

			renderField( fields, 'budget', items[ 1 ] );
			expect( screen.getByText( '$20.00' ) ).toBeInTheDocument();
		} );

		it( 'renders the first country name with the count of the rest', () => {
			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			renderField( fields, 'country', items[ 0 ] );
			expect(
				screen.getByText( 'United States (US) + 2 more' )
			).toBeInTheDocument();

			renderField( fields, 'country', items[ 1 ] );
			expect(
				screen.getByText( 'United Kingdom (UK)' )
			).toBeInTheDocument();
		} );

		it( 'adds the PMax marker class only to PMax campaign titles', () => {
			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			const { container: pmax } = renderField(
				fields,
				'title',
				items[ 1 ]
			);
			const { container: shopping } = renderField(
				fields,
				'title',
				items[ 2 ]
			);

			expect(
				pmax.querySelector( `.${ PMAX_TITLE_CLASS_NAME }` )
			).toHaveTextContent( 'Alpha' );
			expect(
				shopping.querySelector( `.${ PMAX_TITLE_CLASS_NAME }` )
			).toBeNull();
		} );

		it( 'shows the budget recommendation badge unless the campaign was actioned', () => {
			useRaiseBudgetRecommendations.mockReturnValue( {
				campaigns: [ { campaign_id: 1 }, { campaign_id: 2 } ],
			} );
			getActionedCampaigns.mockReturnValue( [ '2' ] );

			const { result } = renderHook( () => useProgramsDataViewsConfig() );
			const { items, fields } = result.current;

			renderField( fields, 'title', items[ 1 ] );
			expect(
				screen.getByText( 'Budget recommendation' )
			).toBeInTheDocument();

			expect( items[ 2 ].hasBudgetRecommendation ).toBe( false );
		} );
	} );
} );
