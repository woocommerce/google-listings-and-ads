/**
 * External dependencies
 */
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
import { useAdsCurrencyConfig } from '.~/hooks/useAdsCurrency';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import AppSpinner from '.~/components/app-spinner';
import { FREE_LISTINGS_PROGRAM_ID } from '.~/constants';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import ProgramToggle from './program-toggle';
import FreeListingsDisabledToggle from './free-listings-disabled-toggle';
import formatAmountWithCode from '.~/utils/formatAmountWithCode';

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
	const { adsCurrencyConfig } = useAdsCurrencyConfig();
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
				<span>
					{ map[ finalCountryCodesData[ 0 ] ] }
					{ finalCountryCodesData.length >= 2 &&
						sprintf(
							// translators: %s: number of campaigns, with minimum value of 1.
							__( ' + %s more', 'google-listings-and-ads' ),
							finalCountryCodesData.length - 1
						) }
				</span>
			),
			active: true,
		},
		...adsCampaignsData.map( ( el ) => {
			return {
				id: el.id,
				title: el.name,
				dailyBudget: formatAmountWithCode(
					adsCurrencyConfig,
					el.amount
				),
				country: map[ el.country ],
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
				// Since the <Table> component uses array index as key to render rows,
				// it might cause incorrect state control if a column has an internal state.
				// So we have to specific `key` prop on some components of the `display` to work around it.
				// Ref: https://github.com/woocommerce/woocommerce-admin/blob/v2.1.2/packages/components/src/table/table.js#L288-L289
				return [
					{ display: el.title },
					{ display: el.country },
					{ display: el.dailyBudget },
					{
						display:
							el.id === FREE_LISTINGS_PROGRAM_ID ? (
								<FreeListingsDisabledToggle />
							) : (
								<ProgramToggle key={ el.id } program={ el } />
							),
					},
					{
						display: (
							<div className="program-actions" key={ el.id }>
								<EditProgramButton programId={ el.id } />
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
};

export default AllProgramsTableCard;
