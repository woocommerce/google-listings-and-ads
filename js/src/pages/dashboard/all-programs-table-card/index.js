/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { getQuery, onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTableCard from '~/components/app-table-card';
import RemoveProgramButton from './remove-program-button';
import EditProgramButton from './edit-program-button';
import './index.scss';
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AppSpinner from '~/components/app-spinner';
import { FREE_LISTINGS_PROGRAM_ID, CAMPAIGN_TYPE_PMAX } from '~/constants';
import AddPaidCampaignButton from '~/components/paid-ads/add-paid-campaign-button';
import ProgramToggle from './program-toggle';
import FreeListingsDisabledToggle from './free-listings-disabled-toggle';
import CampaignAssetsTour from '~/components/tours/campaign-assets-tour';
import BudgetRecommendationBadge from './budget-recommendation-badge';
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import { getActionedCampaigns } from '~/utils/actionedCampaignsCache';
import { recordGlaEvent } from '~/utils/tracks';

const PROGRAMS_TABLE_CARD_CLASS_NAME = 'gla-all-programs-table-card';
const CAMPAIGN_EDIT_BUTTON_CLASS_NAME = 'gla-campaign-edit-button';
const SORTING_OPTIONS_MAP = {
	id: 'id',
	title: 'name',
	dailyBudget: 'amount',
	country: 'country',
	enabled: 'status',
};

function CountryColumn( { countryCodes, countryNameMap } ) {
	const [ first ] = countryCodes;
	return (
		<span>
			{ countryNameMap[ first ] }
			{ countryCodes.length >= 2 &&
				sprintf(
					// translators: %d: number of countries, with minimum value of 1.
					__( ' + %d more', 'google-listings-and-ads' ),
					countryCodes.length - 1
				) }
		</span>
	);
}

/**
 * When there are campaigns with budget recommendations in the table.
 *
 * @event gla_raise_budget_recommendation_badge_campaigns
 * @property {string} context The context in which the banner was dismissed. Set to 'programs-table-card'.
 * @property {Array<number>} campaign_ids The IDs of the campaigns with budget recommendations.
 */

/**
 * All programs table.
 *
 * @see AppTableCard
 *
 * @fires gla_raise_budget_recommendation_badge_campaigns when there are campaigns with budget recommendations in the table.
 * @param {Object} [props] Properties to be forwarded to AppTableCard.
 */
const AllProgramsTableCard = ( props ) => {
	const query = getQuery();
	// Budget is given in the currency that is used by Google Ads, which may differ from the current store's currency.
	// We will still use the store's currency **formatting** settings.
	const { formatAmount } = useAdsCurrency();
	const [ sortOptions, setSortOptions ] = useState( {
		key: 'id',
		direction: 'asc',
	} );
	const { data: finalCountryCodesData } =
		useTargetAudienceFinalCountryCodes();
	const { data: adsCampaignsData } = useAdsCampaigns();
	const { campaigns: raiseBudgetRecommendationCampaigns } =
		useRaiseBudgetRecommendations();
	const map = useCountryKeyNameMap();
	const actionedCampaignsCache = getActionedCampaigns();

	useEffect( () => {
		if (
			! adsCampaignsData?.length ||
			! raiseBudgetRecommendationCampaigns?.length
		) {
			return;
		}

		const raiseBudgetRecommendationCampaignIds = new Set(
			raiseBudgetRecommendationCampaigns.map( ( obj ) => obj.campaign_id )
		);
		const existingRecommendedCampaignIds = adsCampaignsData
			.filter( ( campaignData ) =>
				raiseBudgetRecommendationCampaignIds.has( campaignData.id )
			)
			.map( ( campaignData ) => campaignData.id );

		if ( existingRecommendedCampaignIds.length ) {
			recordGlaEvent( 'gla_raise_budget_recommendation_badge_campaigns', {
				context: 'programs-table-card',
				campaign_ids: existingRecommendedCampaignIds,
			} );
		}
	}, [ adsCampaignsData, raiseBudgetRecommendationCampaigns ] );

	const sortedAdsCampaignsData = useMemo( () => {
		if ( ! adsCampaignsData ) {
			return [];
		}

		const sortKey = SORTING_OPTIONS_MAP[ sortOptions.key ];
		if ( sortKey ) {
			return [ ...adsCampaignsData ].sort( ( programA, programB ) => {
				let aValue = programA[ sortKey ];
				let bValue = programB[ sortKey ];

				switch ( sortOptions.key ) {
					case 'country':
						aValue = Array.isArray( programA.displayCountries )
							? programA.displayCountries.join( '-' )
							: '';
						bValue = Array.isArray( programB.displayCountries )
							? programB.displayCountries.join( '-' )
							: '';
						break;
					case 'dailyBudget':
						aValue = Number( aValue );
						bValue = Number( bValue );
						break;
					case 'enabled':
						aValue = aValue === 'enabled' ? 1 : 0;
						bValue = bValue === 'enabled' ? 1 : 0;
						break;
					default:
						break;
				}

				const direction = sortOptions.direction === 'asc' ? 1 : -1;
				if (
					typeof aValue === 'string' &&
					typeof bValue === 'string'
				) {
					return (
						String( aValue ).localeCompare( String( bValue ) ) *
						direction
					);
				}

				if ( aValue < bValue ) {
					return -1 * direction;
				} else if ( aValue > bValue ) {
					return 1 * direction;
				}

				return 0;
			} );
		}

		return [ ...adsCampaignsData ];
	}, [ adsCampaignsData, sortOptions ] );

	if ( ! finalCountryCodesData || ! adsCampaignsData ) {
		return <AppSpinner />;
	}

	const pmaxCampaigns = adsCampaignsData.filter(
		( { type } ) => type === CAMPAIGN_TYPE_PMAX
	);
	let campaignAssetsTour = null;

	if ( pmaxCampaigns.length ) {
		const selector = `.${ PROGRAMS_TABLE_CARD_CLASS_NAME } .${ CAMPAIGN_EDIT_BUTTON_CLASS_NAME }`;
		campaignAssetsTour = (
			<CampaignAssetsTour referenceElementCssSelector={ selector } />
		);
	}

	/**
	 * Returns sorting properties for a given key if it matches the current sort option.
	 *
	 * @param {string} key - The key to check against the current sort option.
	 * @return {Object} An object containing `defaultSort` and `defaultOrder` if the key matches the current sort option; otherwise, an empty object.
	 */
	const getSortProps = ( key ) => {
		if ( sortOptions?.key !== key ) {
			return {};
		}

		return {
			defaultSort: sortOptions.key === key,
			defaultOrder: sortOptions.direction,
		};
	};

	const headers = [
		{
			key: 'id',
			label: __( 'Program', 'google-listings-and-ads' ),
			isLeftAligned: true,
			required: true,
			isSortable: true,
			...getSortProps( 'id' ),
		},
		{
			key: 'country',
			label: __( 'Country', 'google-listings-and-ads' ),
			isLeftAligned: true,
			isSortable: true,
			...getSortProps( 'country' ),
		},
		{
			key: 'dailyBudget',
			label: __( 'Daily budget', 'google-listings-and-ads' ),
			isSortable: true,
			...getSortProps( 'dailyBudget' ),
		},
		{
			key: 'enabled',
			label: __( 'Enabled', 'google-listings-and-ads' ),
			isSortable: true,
			...getSortProps( 'enabled' ),
		},
		{ key: 'actions', label: '', required: true },
	];

	const hasRaiseBudgetRecommendation = ( campaignId ) => {
		if ( actionedCampaignsCache.includes( `${ campaignId }` ) ) {
			return false;
		}

		return raiseBudgetRecommendationCampaigns.some(
			( recommendation ) => recommendation.campaign_id === campaignId
		);
	};

	const data = [
		{
			id: FREE_LISTINGS_PROGRAM_ID,
			title: __( 'Product feed', 'google-listings-and-ads' ),
			dailyBudget: __( 'Free', 'google-listings-and-ads' ),
			country: (
				<CountryColumn
					countryCodes={ finalCountryCodesData }
					countryNameMap={ map }
				/>
			),
			active: true,
			disabledEdit: false,
		},
		...sortedAdsCampaignsData.map( ( el ) => {
			return {
				id: el.id,
				title: (
					<Flex align="center" gap={ 2 } justify="flex-start" wrap>
						{ el.name }

						{ hasRaiseBudgetRecommendation( el.id ) && (
							<>
								{ ' ' }
								<BudgetRecommendationBadge />
							</>
						) }
					</Flex>
				),
				dailyBudget: formatAmount( el.amount, true ),
				country: (
					<CountryColumn
						countryCodes={ el.displayCountries }
						countryNameMap={ map }
					/>
				),
				active: el.status === 'enabled',
				disabledEdit: el.type !== CAMPAIGN_TYPE_PMAX,
			};
		} ),
	];

	const tableCard = (
		<AppTableCard
			actions={
				<AddPaidCampaignButton
					eventProps={ { context: 'programs-table-card' } }
				/>
			}
			className={ PROGRAMS_TABLE_CARD_CLASS_NAME }
			headers={ headers }
			onQueryChange={ onQueryChange }
			onSort={ ( key, direction ) => {
				setSortOptions( { key, direction } );
			} }
			query={ query }
			rowKey={ ( cells ) => cells[ 0 ].id }
			rows={ data.map( ( el ) => {
				const isFreeListings = el.id === FREE_LISTINGS_PROGRAM_ID;
				const editButtonClassName = classnames( {
					[ CAMPAIGN_EDIT_BUTTON_CLASS_NAME ]:
						! isFreeListings && ! el.disabledEdit,
				} );

				// The `id` property in the first cell is for the `rowKey` callback.
				return [
					{ display: el.title, id: el.id.toString() },
					{ display: el.country },
					{ display: el.dailyBudget },
					{
						display: isFreeListings ? (
							<FreeListingsDisabledToggle />
						) : (
							<ProgramToggle program={ el } />
						),
					},
					{
						display: el.id !== FREE_LISTINGS_PROGRAM_ID && (
							<div className="program-actions">
								<EditProgramButton
									className={ editButtonClassName }
									disabled={ el.disabledEdit }
									programId={ el.id }
								/>
								<RemoveProgramButton programId={ el.id } />
							</div>
						),
					},
				];
			} ) }
			rowsPerPage={ data.length }
			title={ __( 'Programs', 'google-listings-and-ads' ) }
			totalRows={ data.length }
			{ ...props }
		/>
	);

	return (
		<>
			{ campaignAssetsTour }
			{ tableCard }
		</>
	);
};

export default AllProgramsTableCard;
