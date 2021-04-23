/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import {
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	__experimentalText as Text,
} from '@wordpress/components';
import { Pagination, Table } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import EditProductLink from '.~/components/edit-product-link';
import HelpPopover from '.~/components/help-popover';
import ErrorIcon from '.~/components/error-icon';
import WarningIcon from '.~/components/warning-icon';
import AppDocumentationLink from '.~/components/app-documentation-link';
import useMCIssues from '.~/hooks/useMCIssues';
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

const PER_PAGE = 5;

const IssuesTableCard = () => {
	const [ page, setPage ] = useState( 1 );
	const { data } = useMCIssues( page, PER_PAGE );

	const rows = data.issues.map( ( el ) => {
		return [
			{
				display:
					el.type === 'account' ? <ErrorIcon /> : <WarningIcon />,
			},
			{ display: el.product },
			{ display: el.issue },
			{ display: el.action },
			{
				display: <EditProductLink productId={ el.product_id } />,
			},
		];
	} );

	const handlePageChange = ( newPage ) => {
		setPage( newPage );
	};

	return (
		<div className="gla-issues-table-card">
			<Card>
				<CardHeader>
					<Text variant="title.small" as="h2">
						<>
							{ __(
								'Issues to Resolve',
								'google-listings-and-ads'
							) }
							<HelpPopover id="issues-to-resolve">
								{ createInterpolateElement(
									__(
										'Products and stores must meet <link>Google Merchant Center’s requirements</link> in order to get approved. WooCommerce and Google automatically check your product feed to help you resolve any issues. ',
										'google-listings-and-ads'
									),
									{
										link: (
											<AppDocumentationLink
												context="product-feed"
												linkId="issues-to-resolve"
												href="https://support.google.com/merchants/answer/6363310"
											/>
										),
									}
								) }
							</HelpPopover>
						</>
					</Text>
				</CardHeader>
				<CardBody size={ null }>
					<Table headers={ headers } rows={ rows } />
				</CardBody>
				<CardFooter justify="center">
					<Pagination
						page={ page }
						perPage={ PER_PAGE }
						total={ data.total }
						showPagePicker={ false }
						showPerPagePicker={ false }
						onPageChange={ handlePageChange }
					/>
				</CardFooter>
			</Card>
		</div>
	);
};

export default IssuesTableCard;
