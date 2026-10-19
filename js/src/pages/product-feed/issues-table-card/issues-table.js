/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CardBody, CardFooter } from '@wordpress/components';
import { Pagination, TablePlaceholder } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ISSUE_TABLE_PER_PAGE, ISSUE_TYPE_ACCOUNT } from '~/constants';
import ISSUES_TABLE_DATA_HEADERS from './issues-table-data-headers';
import { recordTablePageEvent } from '~/utils/tracks';
import IssuesTableData from './issues-table-data';
import useMCIssuesTypeFilter from '~/hooks/useMCIssuesTypeFilter';
import usePagination from '~/hooks/usePagination';
import './index.scss';

/**
 * The table rendering the account issues data with a paginator.
 * It uses useMCIssuesTypeFilter for getting the issues
 * It uses usePagination in order to handle the pagination.
 *
 * @see useMCIssuesTypeFilter
 * @see usePagination
 * @return {JSX.Element} The rendered component
 */
const IssuesTable = () => {
	const issueType = ISSUE_TYPE_ACCOUNT;
	const { page, setPage } = usePagination( issueType );
	const { data, hasFinishedResolution } = useMCIssuesTypeFilter(
		issueType,
		page
	);

	const handlePageChange = ( newPage, direction ) => {
		setPage( newPage );
		recordTablePageEvent(
			`${ issueType }-issues-to-resolve`,
			newPage,
			direction
		);
	};

	return (
		<>
			<CardBody size={ null }>
				{ ! hasFinishedResolution ? (
					<TablePlaceholder
						headers={ ISSUES_TABLE_DATA_HEADERS }
						caption={ __(
							'Loading Issues To Resolve',
							'google-listings-and-ads'
						) }
					/>
				) : (
					<IssuesTableData data={ data } />
				) }
			</CardBody>
			{ data?.total > 0 && (
				<CardFooter justify="center">
					<Pagination
						page={ page }
						perPage={ ISSUE_TABLE_PER_PAGE }
						total={ data.total }
						showPagePicker={ false }
						showPerPagePicker={ false }
						onPageChange={ handlePageChange }
					/>
				</CardFooter>
			) }
		</>
	);
};

export default IssuesTable;
