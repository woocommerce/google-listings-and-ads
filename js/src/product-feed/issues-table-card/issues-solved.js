/**
 * External dependencies
 */
import { capitalize } from 'lodash/string';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { __experimentalText as Text, Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ISSUE_TYPE_PRODUCT } from '.~/constants';
import useActiveIssueType from '.~/hooks/useActiveIssueType';

/**
 * This component renders a message when no issues of the
 * active issue type are pending to solve.
 *
 * @return {JSX.Element} The component
 */
const IssuesSolved = () => {
	const issueType = useActiveIssueType();
	const otherIssueType =
		issueType === ISSUE_TYPE_PRODUCT ? 'account' : 'product';

	const map = {
		otherIssueTab: <strong>{ capitalize( otherIssueType ) } Issues</strong>,
		otherIssueType: <span>{ otherIssueType }</span>,
	};

	return (
		<div className="gla-issues-solved">
			<Dashicon icon="yes-alt" className="gla-issues-solved__icon" />
			<Text variant="subtitle">
				{ sprintf(
					// translators: %s: Active issue type (account or product)
					__( 'All %s issues resolved', 'google-listings-and-ads' ),
					issueType
				) }
			</Text>
			<Text variant="body" className="gla-issues-solved__body">
				{ createInterpolateElement(
					__(
						'However, there are issues affecting your <otherIssueType /> that needs to be resolved. Head over to the <otherIssueTab /> tab to view them.',
						'google-listings-and-ads'
					),
					map
				) }
			</Text>
		</div>
	);
};

export default IssuesSolved;
