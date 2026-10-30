/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import { getActionedCampaigns } from '~/utils/actionedCampaignsCache';
import { FREE_LISTINGS_PROGRAM_ID, CAMPAIGN_TYPE_PMAX } from '~/constants';
import BudgetRecommendationBadge from './budget-recommendation-badge';
import CountryColumn from './country-column';

/**
 * Class name of the title of PMax campaign rows. It's used as an anchor of the campaign assets tour.
 */
export const PMAX_TITLE_CLASS_NAME = 'gla-all-programs-table-card__pmax-title';

const STATUS_ENABLED = 'enabled';
const STATUS_PAUSED = 'paused';

/**
 * @typedef {Object} ProgramItem
 * @property {number} id Program ID. The free listings program uses `FREE_LISTINGS_PROGRAM_ID`.
 * @property {string} name Program name.
 * @property {string} [type] Campaign type. Not set for the free listings program.
 * @property {string} status Normalized status, either 'enabled' or 'paused'.
 * @property {number} amount Daily budget. It's 0 for the free listings program.
 * @property {Array<string>} countryCodes Country codes of the program.
 * @property {boolean} isFreeListings Whether this is the free listings program.
 * @property {boolean} hasBudgetRecommendation Whether to show the budget recommendation badge.
 */

/**
 * @typedef {Object} ProgramsDataViewsConfig
 * @property {Array<Object>} fields DataViews field definitions.
 * @property {Array<ProgramItem>} items DataViews items. It's empty until all data are resolved.
 * @property {boolean} isLoading Whether the data are still being resolved.
 */

/**
 * Compares two values for DataViews field sorting.
 *
 * @param {string|number} a The first value.
 * @param {string|number} b The second value.
 * @param {'asc'|'desc'} direction Sorting direction.
 * @return {number} The sorting result.
 */
const compare = ( a, b, direction ) => {
	const result =
		typeof a === 'string'
			? a.localeCompare( b )
			: Number( a ) - Number( b );

	return direction === 'asc' ? result : -result;
};

/**
 * Provides the DataViews fields and items for the dashboard programs table.
 *
 * @return {ProgramsDataViewsConfig} The DataViews config.
 */
const useProgramsDataViewsConfig = () => {
	const { formatAmount } = useAdsCurrency();
	const countryNameMap = useCountryKeyNameMap();
	const { data: finalCountryCodes } = useTargetAudienceFinalCountryCodes();
	const { data: campaigns } = useAdsCampaigns();
	const { campaigns: recommendations } = useRaiseBudgetRecommendations();

	const isLoading = ! finalCountryCodes || ! campaigns;

	const items = useMemo( () => {
		if ( isLoading ) {
			return [];
		}

		const actionedCampaigns = getActionedCampaigns();
		const hasBudgetRecommendation = ( campaignId ) =>
			! actionedCampaigns.includes( `${ campaignId }` ) &&
			Boolean(
				recommendations?.some(
					( recommendation ) =>
						recommendation.campaign_id === campaignId
				)
			);

		return [
			{
				id: FREE_LISTINGS_PROGRAM_ID,
				name: __( 'Product feed', 'google-listings-and-ads' ),
				status: STATUS_ENABLED,
				amount: 0,
				countryCodes: finalCountryCodes,
				isFreeListings: true,
				hasBudgetRecommendation: false,
			},
			...campaigns.map( ( campaign ) => ( {
				id: campaign.id,
				name: campaign.name,
				type: campaign.type,
				status:
					campaign.status === STATUS_ENABLED
						? STATUS_ENABLED
						: STATUS_PAUSED,
				amount: Number( campaign.amount ),
				countryCodes: campaign.displayCountries,
				isFreeListings: false,
				hasBudgetRecommendation: hasBudgetRecommendation( campaign.id ),
			} ) ),
		];
	}, [ isLoading, finalCountryCodes, campaigns, recommendations ] );

	const fields = useMemo(
		() => [
			{
				id: 'title',
				label: __( 'Program', 'google-listings-and-ads' ),
				enableHiding: false,
				getValue: ( { item } ) => item.name,
				sort: ( a, b, direction ) =>
					compare( a.name, b.name, direction ),
				render: ( { item } ) => (
					<Flex
						className={
							item.type === CAMPAIGN_TYPE_PMAX
								? PMAX_TITLE_CLASS_NAME
								: undefined
						}
						gap={ 2 }
						align="center"
						justify="flex-start"
						wrap
					>
						{ item.name }
						{ item.hasBudgetRecommendation && (
							<BudgetRecommendationBadge />
						) }
					</Flex>
				),
			},
			{
				id: 'country',
				label: __( 'Country', 'google-listings-and-ads' ),
				getValue: ( { item } ) => item.countryCodes.join( '-' ),
				sort: ( a, b, direction ) =>
					compare(
						a.countryCodes.join( '-' ),
						b.countryCodes.join( '-' ),
						direction
					),
				render: ( { item } ) => (
					<CountryColumn
						countryCodes={ item.countryCodes }
						countryNameMap={ countryNameMap }
					/>
				),
			},
			{
				id: 'budget',
				label: __( 'Daily budget', 'google-listings-and-ads' ),
				getValue: ( { item } ) => item.amount,
				sort: ( a, b, direction ) =>
					compare( a.amount, b.amount, direction ),
				render: ( { item } ) =>
					item.isFreeListings
						? __( 'Free', 'google-listings-and-ads' )
						: formatAmount( item.amount, true ),
			},
			{
				id: 'status',
				label: __( 'Status', 'google-listings-and-ads' ),
				getValue: ( { item } ) => item.status,
				elements: [
					{
						value: STATUS_ENABLED,
						label: __( 'Active', 'google-listings-and-ads' ),
					},
					{
						value: STATUS_PAUSED,
						label: __( 'Paused', 'google-listings-and-ads' ),
					},
				],
				// DataViews makes every field with `elements` filterable. Filters are out of scope for this table.
				filterBy: { operators: [] },
				sort: ( a, b, direction ) =>
					compare(
						Number( a.status === STATUS_PAUSED ),
						Number( b.status === STATUS_PAUSED ),
						direction
					),
			},
		],
		[ countryNameMap, formatAmount ]
	);

	return { fields, items, isLoading };
};

export default useProgramsDataViewsConfig;
