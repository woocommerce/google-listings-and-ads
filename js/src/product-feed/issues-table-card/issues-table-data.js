/**
 * External dependencies
 */
import { EmptyTable, Table } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import WarningIcon from '.~/components/warning-icon';
import ErrorIcon from '.~/components/error-icon';
import AppDocumentationLink from '.~/components/app-documentation-link';
import EditProductLink from '.~/components/edit-product-link';

const IssuesTableData = ( { data, headers } ) => {
	if ( ! data ) {
		return (
			<EmptyTable headers={ headers } numberOfRows={ 1 }>
				{ __(
					'An error occurred while retrieving issues. Please try again later.',
					'google-listings-and-ads'
				) }
			</EmptyTable>
		);
	}

	return (
		<Table
			headers={ headers }
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
			] ) }
		/>
	);
};

export default IssuesTableData;
