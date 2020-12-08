/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import StyledTableCard from '../../components/styled-table-card';
import RemoveProgramButton from './remove-program-button';
import EditProgramLink from './edit-program-link';
import './index.scss';

const headers = [
	{
		key: 'title',
		label: __( 'Title', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'spend',
		label: __( 'Spend', 'google-listings-and-ads' ),
	},
	{
		key: 'numberOfProducts',
		label: __( 'Number of Products', 'google-listings-and-ads' ),
	},
	{
		key: 'active',
		label: __( 'Active', 'google-listings-and-ads' ),
	},
	{ key: 'remove', label: '', required: true },
	{ key: 'edit', label: '', required: true },
];

const AllProgramsTableCard = () => {
	// TODO: data from backend API.
	const data = [
		{
			id: 123,
			title: 'Google Shopping Free Listings',
			spend: 'Free',
			numberOfProducts: 497,
			active: true,
		},
		{
			id: 456,
			title: 'Smart Shopping Campaign 1',
			spend: '$200.00',
			numberOfProducts: 133,
			active: true,
		},
	];

	// TODO: what happens when click add paid campaign button.
	const handleAddPaidCampaignClick = () => {};

	return (
		<StyledTableCard
			className="gla-all-programs-table-card"
			title={
				<div className="gla-all-programs-table-card__header">
					{ __( 'All Programs', 'google-listings-and-ads' ) }
					<Button
						isSecondary
						isSmall
						onClick={ handleAddPaidCampaignClick }
					>
						Add Paid Campaign
					</Button>
				</div>
			}
			headers={ headers }
			rows={ data.map( ( el ) => {
				return [
					{ display: el.title },
					{ display: el.spend },
					{ display: el.numberOfProducts },
					{ display: el.active },
					{
						display: <RemoveProgramButton programId={ el.id } />,
					},
					{ display: <EditProgramLink programId={ el.id } /> },
				];
			} ) }
			totalRows={ data.length }
			rowsPerPage={ 10 }
		/>
	);
};

export default AllProgramsTableCard;
