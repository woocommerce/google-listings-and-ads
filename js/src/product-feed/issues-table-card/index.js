/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import EditProductLink from '../edit-product-link';
import WarningIcon from '../warning-icon';
import './index.scss';

const headers = [
	{
		key: 'type',
		label: __( 'Type', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'affectedProduct',
		label: __( 'Affected Product', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'issue',
		label: __( 'Issue', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{
		key: 'suggestedAction',
		label: __( 'Suggested Action', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{ key: 'action', label: '', required: true },
];

// TODO: this rows should be data coming from backend API.
// Also, i18n for the display labels too.
const rows = [
	[
		{ display: <WarningIcon /> },
		{ display: 'Pink marble tee' },
		{ display: 'Missing tax value' },
		{ display: 'Add a tax setting for your product' },
		{
			display: <EditProductLink productId={ 123 } />,
		},
	],
];

const IssuesTableCard = () => {
	return (
		<div className="gla-issues-table-card">
			<TableCard
				title={ __( 'Issues to Resolve', 'google-listings-and-ads' ) }
				showMenu={ false }
				headers={ headers }
				rows={ rows }
				totalRows={ rows.length }
				rowsPerPage={ 10 }
			/>
		</div>
	);
};

export default IssuesTableCard;
