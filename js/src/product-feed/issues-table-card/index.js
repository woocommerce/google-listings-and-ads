/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import EditProductLink from '../edit-product-link';
import WarningIcon from '../warning-icon';
import HelpPopover from '../help-popover';
import StyledTableCard from '../styled-table-card';

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
			<StyledTableCard
				title={
					<>
						{ __( 'Issues to Resolve', 'google-listings-and-ads' ) }
						<HelpPopover>
							{ createInterpolateElement(
								__(
									'Products and stores must meet <link>Google Merchant Center’s requirements</link> in order to get approved. WooCommerce and Google automatically check your product feed to help you resolve any issues. ',
									'google-listings-and-ads'
								),
								{
									link: (
										<Link
											type="external"
											href="https://support.google.com/merchants/answer/6363310"
											target="_blank"
										/>
									),
								}
							) }
						</HelpPopover>
					</>
				}
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
