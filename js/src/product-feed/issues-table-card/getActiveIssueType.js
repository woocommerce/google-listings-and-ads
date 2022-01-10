/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_ACCOUNT } from '.~/constants';

/**
 * Gets the active Issue Type Filter based on the `issueType` query property.
 *
 * @return {string} The active Issue Type Filter. If `issueType` is falsy, it returns Account Issue Type by default.
 */
const getActiveIssueType = () => getQuery()?.issueType || ISSUE_TYPE_ACCOUNT;

export default getActiveIssueType;
