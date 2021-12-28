/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

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
 * @return {{data: Object, page: number, hasFinishedResolution: boolean, setPage: Function}} The issues data,
 * the current page, the page setter and the resolution status
 */
const useMCIssuesTypeFilter = ( issueType = ISSUE_TYPE_ACCOUNT ) => {
	const [ page, setPage ] = useState( defaultQuery.page );

	const { data, hasFinishedResolution } = useAppSelectDispatch(
		'getMCIssues',
		{
			...defaultQuery,
			page,
			issue_type: issueType,
		}
	);

	return { data, hasFinishedResolution, page, setPage };
};

export default useMCIssuesTypeFilter;
