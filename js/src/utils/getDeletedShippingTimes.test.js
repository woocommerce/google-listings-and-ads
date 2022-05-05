/**
 * Internal dependencies
 */
import getDeletedShippingTimes from './getDeletedShippingTimes';

describe( 'getDeletedShippingTimes', () => {
	it( 'returns empty array when newShippingTimes and oldShippingTimes are the same', () => {
		const newShippingTimes = [
			{
				countryCode: 'US',
				time: 16,
			},
			{
				countryCode: 'MY',
				time: 17,
			},
			{
				countryCode: 'SG',
				time: 18,
			},
		];

		const oldShippingTimes = [
			{
				countryCode: 'US',
				time: 16,
			},
			{
				countryCode: 'MY',
				time: 17,
			},
			{
				countryCode: 'SG',
				time: 18,
			},
		];

		const result = getDeletedShippingTimes(
			newShippingTimes,
			oldShippingTimes
		);

		expect( result ).toStrictEqual( [] );
	} );

	it( 'returns array containing deleted shipping times only when shipping times have been added, edited and deleted', () => {
		const newShippingTimes = [
			// country US is deleted.
			// country MY is edited.
			{
				countryCode: 'MY',
				time: 27,
			},
			// country SG has no change.
			{
				countryCode: 'SG',
				time: 18,
			},
			// country AU is added.
			{
				countryCode: 'AU',
				time: 18,
			},
		];

		const oldShippingTimes = [
			{
				countryCode: 'US',
				time: 16,
			},
			{
				countryCode: 'MY',
				time: 17,
			},
			{
				countryCode: 'SG',
				time: 18,
			},
		];

		const result = getDeletedShippingTimes(
			newShippingTimes,
			oldShippingTimes
		);

		expect( result ).toStrictEqual( [
			{
				countryCode: 'US',
				time: 16,
			},
		] );
	} );
} );
