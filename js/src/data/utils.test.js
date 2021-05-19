/**
 * Internal dependencies
 */
import { getReportQuery, freeFields, paidFields } from './utils';

/**
 * Calls given function with all combinations of possible categories and dataReferences.
 *
 * @param {(category: string, dateReference: string) => void} callback Function to be called with all combinations.
 */
function forAnyCategoryAndDateReference( callback ) {
	for ( const category of [ 'products', 'programs' ] ) {
		for ( const dateReference of [ 'primary', 'secondary' ] ) {
			callback( category, dateReference );
		}
	}
}

describe( 'getReportQuery', () => {
	test( "should foward the `orderby` query parameter if it's one of supported fields for given type", () => {
		forAnyCategoryAndDateReference( ( category, dateReference ) => {
			for ( const orderby of freeFields ) {
				expect(
					getReportQuery(
						category,
						'free',
						{ orderby },
						dateReference
					)
				).toHaveProperty( 'orderby', orderby );
			}
			for ( const orderby of paidFields ) {
				expect(
					getReportQuery(
						category,
						'paid',
						{ orderby },
						dateReference
					)
				).toHaveProperty( 'orderby', orderby );
			}
		} );
	} );
	test( 'should set the `orderby` property to the first supported field if provided `query.orderby` is not supported', () => {
		forAnyCategoryAndDateReference( ( category, dateReference ) => {
			// Some key that's totally off.
			const unsupported = 'unsupported_foo';
			const query = {
				orderby: unsupported,
			};
			expect(
				getReportQuery( category, 'free', query, dateReference )
			).toHaveProperty( 'orderby', freeFields[ 0 ] );
			expect(
				getReportQuery( category, 'paid', query, dateReference )
			).toHaveProperty( 'orderby', paidFields[ 0 ] );
			// Supported for paid, but not for free.
			expect(
				getReportQuery(
					category,
					'free',
					{ orderby: paidFields[ 0 ] },
					dateReference
				)
			).toHaveProperty( 'orderby', freeFields[ 0 ] );
		} );
	} );
} );
