/**
 * External dependencies
 */
import classnames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import { getQuery, onQueryChange } from '@woocommerce/navigation';

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
import { FREE_LISTINGS_PROGRAM_ID, CAMPAIGN_TYPE_PMAX } from '.~/constants';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import ProgramToggle from './program-toggle';
import FreeListingsDisabledToggle from './free-listings-disabled-toggle';
import CampaignAssetsTour from './campaign-assets-tour';

const PROGRAMS_TABLE_CARD_CLASS_NAME = 'gla-all-programs-table-card';
const CAMPAIGN_EDIT_BUTTON_CLASS_NAME = 'gla-campaign-edit-button';

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
	const { data: finalCountryCodesData } =
		useTargetAudienceFinalCountryCodes();
	const { data: adsCampaignsData } = useAdsCampaigns();
	const map = useCountryKeyNameMap();

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
			disabledEdit: false,
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
				disabledEdit: el.type !== CAMPAIGN_TYPE_PMAX,
			};
		} ),
	];

	const tableCard = (
		<AppTableCard
			className={ PROGRAMS_TABLE_CARD_CLASS_NAME }
			title={ __( 'Programs', 'google-listings-and-ads' ) }
			actions={
				<AddPaidCampaignButton
					eventProps={ { context: 'programs-table-card' } }
				/>
			}
			headers={ headers }
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
						display: (
							<div className="program-actions">
								<EditProgramButton
									className={ editButtonClassName }
									programId={ el.id }
									disabled={ el.disabledEdit }
								/>
								{ el.id !== FREE_LISTINGS_PROGRAM_ID && (
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

	return (
		<>
			{ campaignAssetsTour }
			{ tableCard }
		</>
	);
};

export default AllProgramsTableCard;
