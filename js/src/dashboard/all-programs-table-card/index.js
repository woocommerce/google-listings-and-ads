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
		key: 'spend',
		label: __( 'Spend', 'google-listings-and-ads' ),
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
	const { loading, data: adsCampaigns } = useAdsCampaigns();
	const map = useCountryKeyNameMap();

	// TODO: data from backend API.
	// using the above query (e.g. orderby, order and page) as parameter.
	const data = ! adsCampaigns
		? []
		: [
				{
					id: 123,
					title: 'Google Shopping Free Listings',
					spend: 'Free',
					country: '',
					active: true,
				},
				...adsCampaigns.map( ( el ) => {
					return {
						id: el.id,
						title: el.name,
						spend: el.amount,
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
						{ __( 'Add Paid Campaign', 'google-listings-and-ads' ) }
					</Link>
				</div>
			}
			isLoading={ loading || ! adsCampaigns }
			headers={ headers }
			rows={ data.map( ( el ) => {
				return [
					{ display: el.title },
					{ display: el.country },
					{ display: el.spend },
					{
						display: (
							<div className="program-actions">
								<EditProgramLink programId={ el.id } />
								{ el.active ? (
									<PauseProgramButton programId={ el.id } />
								) : (
									<ResumeProgramButton programId={ el.id } />
								) }
								<RemoveProgramButton programId={ el.id } />
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
