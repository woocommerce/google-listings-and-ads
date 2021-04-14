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
import EditProgramLink from './edit-program-link';
import './index.scss';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import useCurrencyFactory from '.~/hooks/useCurrencyFactory';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import AppSpinner from '.~/components/app-spinner';
import { FREE_LISTINGS_PROGRAM_ID } from '.~/constants';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';
import ProgramToggle from './program-toggle';

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
	const {
		data: finalCountryCodesData,
	} = useTargetAudienceFinalCountryCodes();
	const { data: adsCampaignsData } = useAdsCampaigns();
	const map = useCountryKeyNameMap();
	const { formatAmount } = useCurrencyFactory();

	if ( ! finalCountryCodesData || ! adsCampaignsData ) {
		return <AppSpinner />;
	}

	// TODO: data from backend API.
	// using the above query (e.g. orderby, order and page) as parameter.
	const data = [
		{
			id: FREE_LISTINGS_PROGRAM_ID,
			title: __(
				'Google Shopping Free Listings',
				'google-listings-and-ads'
			),
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
				dailyBudget: formatAmount( el.amount ),
				country: map[ el.country ],
				active: el.status === 'enabled',
			};
		} ),
	];

	return (
		<AppTableCard
			className="gla-all-programs-table-card"
			title={
				<div className="gla-all-programs-table-card__header">
					{ __( 'Programs', 'google-listings-and-ads' ) }
					<AddPaidCampaignButton
						eventProps={ { context: 'programs-table-card' } }
					/>
				</div>
			}
			headers={ headers }
			rows={ data.map( ( el ) => {
				return [
					{ display: el.title },
					{ display: el.country },
					{ display: el.dailyBudget },
					{
						display:
							el.id === FREE_LISTINGS_PROGRAM_ID ? (
								__( 'Enabled', 'google-listings-and-ads' )
							) : (
								<ProgramToggle program={ el } />
							),
					},
					{
						display: (
							<div className="program-actions">
								<EditProgramLink programId={ el.id } />
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
