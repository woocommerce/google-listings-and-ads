/**
 * Internal dependencies
 */
import createPagedQueryChangeHandler from './createPagedQueryChangeHandler';

const mockOnQueryChange = jest.fn();
const mockUpdateQueryString = jest.fn();
jest.mock( '@woocommerce/navigation', () => ( {
	onQueryChange: ( ...args ) => mockOnQueryChange( ...args ),
	updateQueryString: ( ...args ) => mockUpdateQueryString( ...args ),
} ) );

describe( 'createPagedQueryChangeHandler', () => {
	const handleQueryChange = createPagedQueryChangeHandler( 'things_paged' );

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'ignores sorting', () => {
		const handler = handleQueryChange( 'sort' );

		handler( 'clicks', 'asc' );

		expect( mockOnQueryChange ).not.toHaveBeenCalled();
		expect( mockUpdateQueryString ).not.toHaveBeenCalled();
	} );

	it( 'writes the page into its own URL param', () => {
		handleQueryChange( 'paged' )( '3' );

		expect( mockUpdateQueryString ).toHaveBeenCalledWith( {
			things_paged: '3',
		} );
		expect( mockOnQueryChange ).not.toHaveBeenCalled();
	} );

	it( 'leaves every other param to the default handler', () => {
		const defaultHandler = jest.fn();
		mockOnQueryChange.mockReturnValue( defaultHandler );

		const handler = handleQueryChange( 'per_page' );

		expect( mockOnQueryChange ).toHaveBeenCalledWith( 'per_page' );
		expect( handler ).toBe( defaultHandler );
	} );
} );
