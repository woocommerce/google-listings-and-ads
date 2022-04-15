/**
 * Internal dependencies
 */
import getDifferentShippingTimes from './getDifferentShippingTimes';

describe( 'getDifferentShippingTimes', () => {
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

		const result = getDifferentShippingTimes(
			newShippingTimes,
			oldShippingTimes
		);

		expect( result ).toStrictEqual( [] );
	} );

	it( 'returns array containing newly added shipping times from newShippingTimes', () => {
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

		const result = getDifferentShippingTimes(
			newShippingTimes,
			oldShippingTimes
		);

		expect( result ).toStrictEqual( [
			{
				countryCode: 'AU',
				time: 18,
			},
		] );
	} );

	it( 'returns array containing edited shipping times from shippingTimes1', () => {
		const shipingTimes1 = [
			{
				countryCode: 'US',
				time: 16,
			},
			// edited.
			{
				countryCode: 'MY',
				time: 22,
			},
			{
				countryCode: 'SG',
				time: 18,
			},
		];

		const shipingTimes2 = [
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

		const result = getDifferentShippingTimes(
			shipingTimes1,
			shipingTimes2
		);

		expect( result ).toStrictEqual( [
			{
				countryCode: 'MY',
				time: 22,
			},
		] );
	} );
} );
