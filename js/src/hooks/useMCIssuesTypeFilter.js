/**
 * Internal dependencies
 */
import { ISSUE_TABLE_PER_PAGE } from '.~/product-feed/constants';
import { ISSUE_TYPE_ACCOUNT } from '.~/constants';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

const defaultQuery = {
	page: 1,
	per_page: ISSUE_TABLE_PER_PAGE,
};

/**
 * Hook that returns the issues data filtered by issueType with their paginator state.
 *
 * @param {string} [issueType=product|account] `account` by default
 * @param {number} page allows to set the page, by default 1
 * @param {number} perPage allows to set the number of items per page, by default 5
 * @return {{data: Object, hasFinishedResolution: boolean}} The issues data and the resolution status
 */
const useMCIssuesTypeFilter = (
	issueType = ISSUE_TYPE_ACCOUNT,
	page = defaultQuery.page,
	perPage = defaultQuery.per_page
) => {
	return useAppSelectDispatch( 'getMCIssues', {
		page,
		issue_type: issueType,
		per_page: perPage,
	} );
};

export default useMCIssuesTypeFilter;
