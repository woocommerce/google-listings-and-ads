/**
 * External dependencies
 */
import { EmptyTable, Table } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import WarningIcon from '.~/components/warning-icon';
import ErrorIcon from '.~/components/error-icon';
import AppDocumentationLink from '.~/components/app-documentation-link';
import EditProductLink from '.~/components/edit-product-link';
import AppButtonModalTrigger from '.~/components/app-button-modal-trigger';
import IssuesSolved from './issues-solved';
import IssuesTableDataModal from './issues-table-data-modal';
import { ISSUE_TYPE_PRODUCT } from '.~/constants';
import ISSUES_TABLE_DATA_HEADERS from './issues-table-data-headers';

/**
 * The rows with data for the Issues table
 *
 * @param {Object} args The data and the headers
 * @param {Object} args.data The data containing the issues to render.
 * @return {JSX.Element} The rendered component with the issues or with an empty table if no data is provided
 */
const IssuesTableData = ( { data } ) => {
	const readMore = __(
		'Read more about this issue',
		'google-listings-and-ads'
	);

	if ( ! data ) {
		return (
			<EmptyTable
				headers={ ISSUES_TABLE_DATA_HEADERS }
				numberOfRows={ 1 }
			>
				{ __(
					'An error occurred while retrieving issues. Please try again later.',
					'google-listings-and-ads'
				) }
			</EmptyTable>
		);
	}

	if ( ! data.issues?.length ) {
		return <IssuesSolved />;
	}
	return (
		<Table
			caption={ __( 'Issues to resolve', 'google-listings-and-ads' ) }
			headers={ ISSUES_TABLE_DATA_HEADERS }
			rows={ data.issues.map( ( el ) => [
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
					display: el.action ? (
						<AppButtonModalTrigger
							button={
								<AppButton
									isLink
									eventName={
										'gla_click_read_more_about_issue'
									}
									eventProps={ {
										context: 'issues-to-resolve',
										issue: el.code,
									} }
								>
									{ readMore }
								</AppButton>
							}
							modal={ <IssuesTableDataModal issue={ el } /> }
						/>
					) : (
						<AppDocumentationLink
							context="issues-to-resolve"
							linkId={ el.code }
							href={ el.action_url }
						>
							{ readMore }
						</AppDocumentationLink>
					),
				},
				{
					display: el.type === ISSUE_TYPE_PRODUCT && (
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
			] ) }
		/>
	);
};

export default IssuesTableData;
