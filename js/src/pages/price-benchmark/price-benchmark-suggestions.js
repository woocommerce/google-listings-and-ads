/**
 * External dependencies
 */
import { lazy } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PriceBenchmarkTour from './price-benchmark-tour';
import usePriceBenchmarkSuggestions from '~/hooks/usePriceBenchmarkSuggestions';
import ChangePrice from './change-price';
import EffectivenessIndicator from './effectiveness-indicator';
import Label from './label';
import Price from './price';
import {
	LABELS,
	LABEL_PRICE_CHANGE_EFFECTIVENESS,
	LABEL_PRICE_ON_GOOGLE,
	LABEL_PRICE_GAP,
	LABEL_SUGGESTED_PRICE,
	LABEL_REGULAR_PRICE,
	LABEL_ACTION,
} from './constants';

const PriceBenchmarkTable = lazy( () =>
	import(
		/* webpackChunkName: "price-benchmark-table" */ './price-benchmark-table'
	)
);

const TABLE_FIELDS = [
	{
		id: 'effectiveness',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: (
			<Label labelKey={ LABEL_PRICE_CHANGE_EFFECTIVENESS } alignLeft />
		),
		label: LABELS[ LABEL_PRICE_CHANGE_EFFECTIVENESS ].title,
		render: ( { item } ) => {
			if ( item.effectiveness === undefined ) {
				return null;
			}

			return (
				<EffectivenessIndicator effectiveness={ item.effectiveness } />
			);
		},
	},
	{
		id: 'regular_price',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		label: <Label labelKey={ LABEL_REGULAR_PRICE } />,
		render: ( { item } ) => {
			return <Price amount={ item.regular_price } highlight />;
		},
	},
	{
		id: 'price_on_google',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_PRICE_ON_GOOGLE } />,
		label: LABELS[ LABEL_PRICE_ON_GOOGLE ].title,
		render: ( { item } ) => {
			return <Price amount={ item.price_on_google } />;
		},
	},
	{
		id: 'price_gap',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_PRICE_GAP } />,
		label: LABELS[ LABEL_PRICE_GAP ].title,
		render: ( { item } ) => {
			if ( ! item.price_gap ) {
				return null;
			}

			return `${ item.price_gap }%`;
		},
	},
	{
		id: 'suggested_price',
		enableHiding: false,
		enableSorting: true,
		enableGlobalSearch: false,
		header: <Label labelKey={ LABEL_SUGGESTED_PRICE } />,
		label: LABELS[ LABEL_SUGGESTED_PRICE ].title,
		render: ( { item } ) => {
			return <Price amount={ item.suggested_price } />;
		},
	},
	{
		id: 'action',
		enableHiding: false,
		enableSorting: false,
		enableGlobalSearch: false,
		label: LABELS[ LABEL_ACTION ].title,
		render: ( { item } ) => {
			return <ChangePrice productID={ item.id } />;
		},
	},
];

const TABLE_FIELDS_MOBILE = [ 'action' ];

/**
 * PriceBenchmarkSuggestions component.
 *
 * This component fetches and displays price benchmark suggestions using the
 * `usePriceBenchmarkSuggestions` hook. It renders a table with the suggestions
 * data and handles responsiveness by providing different field configurations
 * for desktop and mobile views.
 *
 * @return {JSX.Element} A div containing the PriceBenchmarkTable component.
 */
const PriceBenchmarkSuggestions = () => {
	const { suggestions, hasFinishedResolution } =
		usePriceBenchmarkSuggestions();

	return (
		<div className="gla-price-benchmark-suggestions">
			<PriceBenchmarkTour />
			<PriceBenchmarkTable
				data={ suggestions }
				fields={ TABLE_FIELDS }
				fieldsMobile={ TABLE_FIELDS_MOBILE }
				isReady={ hasFinishedResolution }
			/>
		</div>
	);
};

export default PriceBenchmarkSuggestions;
