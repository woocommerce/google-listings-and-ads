/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ISSUE_TABLE_PER_PAGE } from '.~/product-feed/constants';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

const defaultQuery = {
	page: 1,
	per_page: ISSUE_TABLE_PER_PAGE,
};

const useMCIssues = ( issueType ) => {
	const [ page, setPage ] = useState( defaultQuery.page );

	const { data, hasFinishedResolution } = useAppSelectDispatch(
		'getMCIssues',
		{
			...defaultQuery,
			page,
			issue_type: issueType,
		}
	);

	return [ data, hasFinishedResolution, page, setPage ];
};

export default useMCIssues;
