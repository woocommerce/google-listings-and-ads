/**
 * Internal dependencies
 */
import { getMCProductFeed } from '../resolvers';
import TYPES from '../action-types';

describe( 'getMCProductFeed.shouldInvalidate', () => {
	const query = {
		page: 1,
		per_page: 10,
		order: 'asc',
		orderby: 'title',
	};
	const receive = ( receivedQuery ) => ( {
		type: TYPES.RECEIVE_MC_PRODUCT_FEED,
		query: receivedQuery,
	} );

	it( 'invalidates after channel visibility changed', () => {
		expect(
			getMCProductFeed.shouldInvalidate(
				{ type: TYPES.UPDATE_MC_PRODUCTS_VISIBILITY },
				query
			)
		).toBe( true );
	} );

	it( 'keeps other pages of the same query', () => {
		expect(
			getMCProductFeed.shouldInvalidate(
				receive( { ...query, page: 2 } ),
				query
			)
		).toBe( false );
	} );

	it.each( [
		[ 'journey', 'disapproved' ],
		[ 'visibility', 'dont-sync-and-show' ],
		[ 'search', 'hoodie' ],
	] )(
		'invalidates when products for another `%s` filter are received',
		( key, value ) => {
			expect(
				getMCProductFeed.shouldInvalidate(
					receive( { ...query, page: 2, [ key ]: value } ),
					query
				)
			).toBe( true );
		}
	);
} );
