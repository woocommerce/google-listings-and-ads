/* global jest */
export default jest.fn( () => ( {
	hasFinishedResolution: true,
	data: {
		continents: {
			EU: {
				name: 'Europe',
				countries: [ 'GB', 'ES' ],
			},
			NA: {
				name: 'North America',
				countries: [ 'US', 'CN' ],
			},
		},
		countries: {
			CN: { name: 'Canada' },
			GB: { name: 'United Kingdom' },
			US: { name: 'United States' },
			ES: { name: 'Spain' },
		},
	},
} ) );
