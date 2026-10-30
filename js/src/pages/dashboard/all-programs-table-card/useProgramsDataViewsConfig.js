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
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import Badge from '~/components/badge';
import { getActionedCampaigns } from '~/utils/actionedCampaignsCache';
import { FREE_LISTINGS_PROGRAM_ID, CAMPAIGN_TYPE_PMAX } from '~/constants';
import BudgetRecommendationBadge from './budget-recommendation-badge';
import CountryColumn from './country-column';

export const PMAX_TITLE_CLASS_NAME = 'gla-all-programs-table-card__pmax-title';

export const STATUS_ENABLED = 'enabled';
export const STATUS_PAUSED = 'paused';

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
 * Provides the DataViews fields and items for the dashboard programs table.
 *
 * @return {ProgramsDataViewsConfig} The DataViews config.
 */
const useProgramsDataViewsConfig = () => {
	const { formatAmount } = useAdsCurrency();
	const { data: finalCountryCodes } = useTargetAudienceFinalCountryCodes();
	const { data: campaigns } = useAdsCampaigns();
	const { campaigns: recommendations } = useRaiseBudgetRecommendations();
	const countryNameMap = useCountryKeyNameMap();

	const isLoading = ! finalCountryCodes || ! campaigns;

	const items = useMemo( () => {
		if ( isLoading ) {
			return [];
		}

		const actionedCampaigns = getActionedCampaigns();
		const recommendedCampaignIds = new Set(
			recommendations?.map( ( { campaign_id: id } ) => id )
		);
		const hasBudgetRecommendation = ( campaignId ) =>
			! actionedCampaigns.includes( `${ campaignId }` ) &&
			recommendedCampaignIds.has( campaignId );

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

	const fields = useMemo( () => {
		const statusBadges = {
			[ STATUS_ENABLED ]: {
				label: __( 'Active', 'google-listings-and-ads' ),
				intent: 'success',
			},
			[ STATUS_PAUSED ]: {
				label: __( 'Paused', 'google-listings-and-ads' ),
				intent: 'default',
			},
		};

		return [
			{
				id: 'title',
				label: __( 'Program', 'google-listings-and-ads' ),
				enableHiding: false,
				getValue: ( { item } ) => item.name,
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
				render: ( { item } ) =>
					item.isFreeListings
						? __( 'Free', 'google-listings-and-ads' )
						: formatAmount( item.amount, true ),
			},
			{
				id: 'status',
				label: __( 'Status', 'google-listings-and-ads' ),
				getValue: ( { item } ) => item.status,
				elements: Object.entries( statusBadges ).map(
					( [ value, { label } ] ) => ( { value, label } )
				),
				// DataViews makes every field with `elements` filterable. Filters are out of scope for this table.
				filterBy: { operators: [] },
				render: ( { item } ) => {
					const { label, intent } = statusBadges[ item.status ];
					return <Badge intent={ intent }>{ label }</Badge>;
				},
			},
		];
	}, [ formatAmount, countryNameMap ] );

	return { fields, items, isLoading };
};

export default useProgramsDataViewsConfig;
