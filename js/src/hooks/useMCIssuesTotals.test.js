jest.mock( '.~/hooks/useMCIssuesTypeFilter', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useMCIssuesTypeFilter' )
		.mockReturnValue( {
			data: {
				total: 1,
			},
		} ),
} ) );

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';
import useMCIssues from '.~/hooks/useMCIssues';
import { ISSUE_TYPE_ACCOUNT, ISSUE_TYPE_PRODUCT } from '.~/constants';

afterEach( () => {
	jest.clearAllMocks();
} );

describe( 'useMcIssuesTotals', () => {
	test( 'Is equivalent to the sum of Account Totals and Product Totals', () => {
		// combined totals
		const { result } = renderHook( () => useMCIssuesTotals() );

		// totals only for account type
		const { result: resultAccountTotals } = renderHook( () =>
			useMCIssues( ISSUE_TYPE_ACCOUNT )
		);

		// totals only for product type
		const { result: resultProductTotals } = renderHook( () =>
			useMCIssues( ISSUE_TYPE_PRODUCT )
		);

		const accountTotal = resultAccountTotals.current.data.total;
		const productTotal = resultProductTotals.current.data.total;

		expect( result.current.totals ).toStrictEqual( {
			account: accountTotal,
			product: productTotal,
			total: accountTotal + productTotal,
		} );
	} );
} );
