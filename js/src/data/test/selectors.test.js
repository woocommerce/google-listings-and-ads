/**
 * Internal dependencies
 */
import { getMCProductFeed, getNotifications } from '../selectors';

describe( 'getNotifications', () => {
	it( 'returns state.notifications when present', () => {
		const notifications = [
			{ id: 'notif-1', triggered_at: 1000 },
			{ id: 'notif-2', triggered_at: 2000 },
		];

		expect( getNotifications( { notifications } ) ).toEqual(
			notifications
		);
	} );

	it( 'returns empty array when state.notifications is undefined', () => {
		expect( getNotifications( {} ) ).toEqual( [] );
	} );

	it( 'returns empty array when state.notifications is null', () => {
		expect( getNotifications( { notifications: null } ) ).toEqual( [] );
	} );
} );

describe( 'getMCProductFeed', () => {
	const state = {
		mc_product_feed: {
			per_page: 2,
			order: 'asc',
			orderby: 'title',
			journey: 'disapproved',
			visibility: '',
			search: '',
			total: 3,
			pages: { 1: [ '#1', '#2' ], 2: [ '#3' ] },
		},
	};
	const query = {
		page: 2,
		per_page: 2,
		order: 'asc',
		orderby: 'title',
		journey: 'disapproved',
	};

	it( 'returns the stored page and total for the stored filter', () => {
		expect( getMCProductFeed( state, query ) ).toEqual( {
			products: [ '#3' ],
			total: 3,
		} );
	} );

	it.each( [
		[ 'journey', 'approved' ],
		[ 'visibility', 'dont-sync-and-show' ],
		[ 'search', 'hoodie' ],
	] )(
		'returns no products while the `%s` filter differs from the stored one',
		( key, value ) => {
			expect(
				getMCProductFeed( state, { ...query, [ key ]: value } )
			).toEqual( { products: undefined, total: undefined } );
		}
	);
} );
