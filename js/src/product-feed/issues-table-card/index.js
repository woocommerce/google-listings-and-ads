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
import {
	EmptyTable,
	Pagination,
	Table,
	TablePlaceholder,
} from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { recordTablePageEvent } from '.~/utils/recordEvent';
import AppTableCardDiv from '.~/components/app-table-card-div';
import EditProductLink from '.~/components/edit-product-link';
import HelpPopover from '.~/components/help-popover';
import ErrorIcon from '.~/components/error-icon';
import WarningIcon from '.~/components/warning-icon';
import AppDocumentationLink from '.~/components/app-documentation-link';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import { ISSUE_TABLE_PER_PAGE } from '../constants';
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
		label: __( 'Affected product', 'google-listings-and-ads' ),
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
		label: __( 'Suggested action', 'google-listings-and-ads' ),
		isLeftAligned: true,
		required: true,
	},
	{ key: 'action', label: '', required: true },
];

const actions = (
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
);

/**
 * Triggered when edit links are clicked from Issues to resolve table.
 *
 * @event gla_edit_product_issue_click
 * @property {string} code Issue code returned from Google
 * @property {string} issue Issue description returned from Google
 */

/**
 * @fires gla_edit_product_issue_click
 * @fires gla_table_go_to_page with `context: 'issues-to-resolve'`
 * @fires gla_table_page_click with `context: 'issues-to-resolve'`
 * @fires gla_documentation_link_click with `{ context: 'product-feed', link_id: 'issues-to-resolve', href: 'https://support.google.com/merchants/answer/6363310' }`
 */
const IssuesTableCard = () => {
	const [ page, setPage ] = useState( 1 );
	const { hasFinishedResolution, data } = useAppSelectDispatch(
		'getMCIssues',
		{
			page,
			per_page: ISSUE_TABLE_PER_PAGE,
		}
	);

	const handlePageChange = ( newPage, direction ) => {
		setPage( newPage );
		recordTablePageEvent( 'issues-to-resolve', newPage, direction );
	};

	return (
		<AppTableCardDiv className="gla-issues-table-card">
			<Card
				className={ classnames( 'woocommerce-table', {
					'has-actions': !! actions,
				} ) }
			>
				<CardHeader>
					{ /* We use this Text component to make it similar to TableCard component. */ }
					<Text variant="title.small" as="h2">
						{ __( 'Issues to resolve', 'google-listings-and-ads' ) }
					</Text>
					{ /* This is also similar to TableCard component implementation. */ }
					<div className="woocommerce-table__actions">
						{ actions }
					</div>
				</CardHeader>
				<CardBody size={ null }>
					{ ! hasFinishedResolution && (
						<TablePlaceholder headers={ headers } />
					) }
					{ hasFinishedResolution && ! data && (
						<EmptyTable headers={ headers } numberOfRows={ 1 }>
							{ __(
								'An error occurred while retrieving issues. Please try again later.',
								'google-listings-and-ads'
							) }
						</EmptyTable>
					) }
					{ hasFinishedResolution && data && (
						<Table
							headers={ headers }
							rows={ data.issues.map( ( el ) => {
								return [
									{
										display:
											el.severity === 'warning' ? (
												<WarningIcon />
											) : (
												<ErrorIcon />
											),
									},
									{ display: el.product },
									{ display: el.issue },
									{
										display: (
											<AppDocumentationLink
												context="issues-to-resolve"
												linkId={ el.code }
												href={ el.action_url }
											>
												{ el.action }
											</AppDocumentationLink>
										),
									},
									{
										display: el.type === 'product' && (
											<EditProductLink
												productId={ el.product_id }
												eventName="gla_edit_product_issue_click"
												eventProps={ {
													code: el.code,
													issue: el.issue,
												} }
											/>
										),
									},
								];
							} ) }
						/>
					) }
				</CardBody>
				<CardFooter justify="center">
					<Pagination
						page={ page }
						perPage={ ISSUE_TABLE_PER_PAGE }
						total={ data?.total }
						showPagePicker={ false }
						showPerPagePicker={ false }
						onPageChange={ handlePageChange }
					/>
				</CardFooter>
			</Card>
		</AppTableCardDiv>
	);
};

export default IssuesTableCard;
