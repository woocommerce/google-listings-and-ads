/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getQuery, onQueryChange } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppTableCard from '.~/components/app-table-card';
import RemoveProgramButton from './remove-program-button';
import EditProgramButton from './edit-program-button';
import './index.scss';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import useAdsCurrency from '.~/hooks/useAdsCurrency';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import AppSpinner from '.~/components/app-spinner';
import { FREE_LISTINGS_PROGRAM_ID, CAMPAIGN_STEP } from '.~/constants';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import ProgramToggle from './program-toggle';
import FreeListingsDisabledToggle from './free-listings-disabled-toggle';
import { getEditCampaignUrl } from '.~/utils/urls';

const headers = [
	{
		key: 'title',
		label: __( 'Program', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'country',
		label: __( 'Country', 'google-listings-and-ads' ),
		isLeftAligned: true,
	},
	{
		key: 'dailyBudget',
		label: __( 'Daily budget', 'google-listings-and-ads' ),
	},
	{
		key: 'assets',
		label: __( 'Assets', 'google-listings-and-ads' ),
	},
	{
		key: 'enabled',
		label: __( 'Enabled', 'google-listings-and-ads' ),
	},
	{ key: 'actions', label: '', required: true },
];

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
 * All programs table.
 *
 * @see AppTableCard
 *
 * @param {Object} [props] Properties to be forwarded to AppTableCard.
 */
const AllProgramsTableCard = ( props ) => {
	const query = getQuery();
	// Budget is given in the currency that is used by Google Ads, which may differ from the current store's currency.
	// We will still use the store's currency **formatting** settings.
	const { formatAmount } = useAdsCurrency();
	const {
		data: finalCountryCodesData,
	} = useTargetAudienceFinalCountryCodes();
	const { data: adsCampaignsData } = useAdsCampaigns();
	const map = useCountryKeyNameMap();

	if ( ! finalCountryCodesData || ! adsCampaignsData ) {
		return <AppSpinner />;
	}

	const data = [
		{
			id: FREE_LISTINGS_PROGRAM_ID,
			title: __( 'Free listings', 'google-listings-and-ads' ),
			dailyBudget: __( 'Free', 'google-listings-and-ads' ),
			country: (
				<CountryColumn
					countryCodes={ finalCountryCodesData }
					countryNameMap={ map }
				/>
			),
			active: true,
		},
		...adsCampaignsData.map( ( el ) => {
			return {
				id: el.id,
				title: el.name,
				dailyBudget: formatAmount( el.amount, true ),
				country: (
					<CountryColumn
						countryCodes={ el.displayCountries }
						countryNameMap={ map }
					/>
				),
				active: el.status === 'enabled',
			};
		} ),
	];

	return (
		<AppTableCard
			className="gla-all-programs-table-card"
			title={ __( 'Programs', 'google-listings-and-ads' ) }
			actions={
				<AddPaidCampaignButton
					eventProps={ { context: 'programs-table-card' } }
				/>
			}
			headers={ headers }
			rows={ data.map( ( el ) => {
				const isPaidCampaign = el.id !== FREE_LISTINGS_PROGRAM_ID;
				const editingAssetsPath = getEditCampaignUrl(
					el.id,
					CAMPAIGN_STEP.ASSET_GROUP
				);

				// Since the <Table> component uses array index as key to render rows,
				// it might cause incorrect state control if a column has an internal state.
				// So we have to specific `key` prop on some components of the `display` to work around it.
				// Ref: https://github.com/woocommerce/woocommerce-admin/blob/v2.1.2/packages/components/src/table/table.js#L288-L289
				return [
					{ display: el.title },
					{ display: el.country },
					{ display: el.dailyBudget },
					{
						display: isPaidCampaign && (
							// TODO: Revisit this temporary demo since the spec of assets column is not yet decided.
							<Link href={ editingAssetsPath }>Edit</Link>
						),
					},
					{
						display: isPaidCampaign ? (
							<ProgramToggle key={ el.id } program={ el } />
						) : (
							<FreeListingsDisabledToggle />
						),
					},
					{
						display: (
							<div className="program-actions" key={ el.id }>
								<EditProgramButton programId={ el.id } />
								{ isPaidCampaign && (
									<RemoveProgramButton programId={ el.id } />
								) }
							</div>
						),
					},
				];
			} ) }
			totalRows={ data.length }
			rowsPerPage={ data.length }
			query={ query }
			onQueryChange={ onQueryChange }
			{ ...props }
		/>
	);
};

export default AllProgramsTableCard;
