/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT } from '.~/constants';

const getActiveIssueType = () => getQuery()?.issueType ?? ISSUE_TYPE_ACCOUNT;

export default getActiveIssueType;
