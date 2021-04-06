/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery, getNewPath, onQueryChange } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import AppTableCard from '.~/components/app-table-card';
import RemoveProgramButton from './remove-program-button';
import EditProgramLink from './edit-program-link';
import PauseProgramButton from './pause-program-button';
import ResumeProgramButton from './resume-program-button';
import './index.scss';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import useCurrencyFactory from '.~/hooks/useCurrencyFactory';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import AppSpinner from '.~/components/app-spinner';

const headers = [
	{
		key: 'title',
		label: __( 'Program', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
		isSortable: true,
	},
	{
		key: 'country',
		label: __( 'Country', 'google-listings-and-ads' ),
		isLeftAligned: true,
		isSortable: true,
	},
	{
		key: 'dailyBudget',
		label: __( 'Daily budget', 'google-listings-and-ads' ),
		isSortable: true,
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
		data: finalCountryCodesdata,
	} = useTargetAudienceFinalCountryCodes();
	const { data: adsCampaignsData } = useAdsCampaigns();
	const map = useCountryKeyNameMap();
	const { formatAmount } = useCurrencyFactory();

	if ( ! finalCountryCodesdata || ! adsCampaignsData ) {
		return <AppSpinner />;
	}

	// TODO: data from backend API.
	// using the above query (e.g. orderby, order and page) as parameter.
	const data = [
		{
			id: 0,
			title: __(
				'Google Shopping Free Listings',
				'google-listings-and-ads'
			),
			dailyBudget: __( 'Free', 'google-listings-and-ads' ),
			country: finalCountryCodesdata.length,
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
					<Link
						className={ classnames(
							'components-button',
							'is-secondary',
							'is-small'
						) }
						href={ getNewPath( {}, '/google/setup-ads' ) }
					>
						{ __( 'Add paid campaign', 'google-listings-and-ads' ) }
					</Link>
				</div>
			}
			headers={ headers }
			rows={ data.map( ( el ) => {
				return [
					{ display: el.title },
					{ display: el.country },
					{ display: el.dailyBudget },
					{
						display: (
							<div className="program-actions">
								<EditProgramLink programId={ el.id } />
								{ el.active ? (
									<PauseProgramButton programId={ el.id } />
								) : (
									<ResumeProgramButton programId={ el.id } />
								) }
								{ el.id !== 0 && (
									<RemoveProgramButton programId={ el.id } />
								) }
							</div>
						),
					},
				];
			} ) }
			totalRows={ data.length }
			rowsPerPage={ 10 }
			query={ query }
			onQueryChange={ onQueryChange }
			{ ...props }
		/>
	);
};

export default AllProgramsTableCard;
