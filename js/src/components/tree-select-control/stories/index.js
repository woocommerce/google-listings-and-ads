/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TreeSelectControl from '../index';

export default {
	/* 👇 The title prop is optional.
	 * See https://storybook.js.org/docs/react/configure/overview#configure-story-loading
	 * to learn how to generate automatic titles
	 */
	title: 'Tree Select Control',
	component: TreeSelectControl
};

const treeSelectControlOptions = [
	{
		value: 'EU',
		label: 'Europe',
		children: [
			{ value: 'ES', label: 'Spain' },
			{ value: 'FR', label: 'France' },
			{ key: 'FR-Colonies', value: 'FR', label: 'France (Colonies)' },
		],
	},
	{
		value: 'AS',
		label: 'Asia',
		children: [
			{
				value: 'JP',
				label: 'Japan',
				children: [
					{
						value: 'TO',
						label: 'Tokio',
						children: [
							{ value: 'SI', label: 'Shibuya' },
							{ value: 'GI', label: 'Ginza' },
						],
					},
					{ value: 'OK', label: 'Okinawa' },
				],
			},
			{ value: 'CH', label: 'China' },
			{
				value: 'MY',
				label: 'Malaysia',
				children: [ { value: 'KU', label: 'Kuala Lumpur' } ],
			},
		],
	},
	{
		value: 'NA',
		label: 'North America',
		children: [
			{
				value: 'US',
				label: 'United States',
				children: [
					{ value: 'NY', label: 'New York' },
					{ value: 'TX', label: 'Texas' },
					{ value: 'GE', label: 'Georgia' },
				],
			},
			{
				value: 'CA',
				label: 'Canada',
			},
		],
	},
];

const Template = ( args ) => {
	const [ selected, setSelected ] = useState( [ 'ES' ] );

	return (
		<TreeSelectControl
			{ ...args }
			value={ selected }
			onChange={ setSelected }
		/>
	);
};

export const Base = Template.bind( {} );

Base.args = {
	id: 'my-id',
	label: 'Select Countries',
	placeholder: 'Search countries',
	disabled: false,
	options: treeSelectControlOptions,
	maxVisibleTags: 3,
	selectAllLabel: 'All countries',
};
