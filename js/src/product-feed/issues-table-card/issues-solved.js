/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { __experimentalText as Text, Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import { ISSUE_TYPE_PRODUCT, ISSUE_TYPE_ACCOUNT } from '.~/constants';

/**
 * This component renders a message when no issues of the
 * active issue type are pending to solve.
 *
 * @return {JSX.Element} The component
 */
const IssuesSolved = () => {
	const issueType = useActiveIssueType();

	const subtitle = {
		[ ISSUE_TYPE_ACCOUNT ]: __(
			'All account issues resolved',
			'google-listings-and-ads'
		),
		[ ISSUE_TYPE_PRODUCT ]: __(
			'All product issues resolved',
			'google-listings-and-ads'
		),
	};

	const body = {
		[ ISSUE_TYPE_ACCOUNT ]: createInterpolateElement(
			__(
				'However, there are issues affecting your products that needs to be resolved. Head over to the <strong>Product Issues</strong> tab to view them.',
				'google-listings-and-ads'
			),
			{ strong: <strong /> }
		),
		[ ISSUE_TYPE_PRODUCT ]: createInterpolateElement(
			__(
				'However, there are issues affecting your account that needs to be resolved. Head over to the <strong>Account Issues</strong> tab to view them.',
				'google-listings-and-ads'
			),
			{ strong: <strong /> }
		),
	};

	return (
		<div className="gla-issues-solved">
			<Dashicon icon="yes-alt" className="gla-issues-solved__icon" />
			<Text variant="subtitle">{ subtitle[ issueType ] }</Text>
			<Text variant="body" className="gla-issues-solved__body">
				{ body[ issueType ] }
			</Text>
		</div>
	);
};

export default IssuesSolved;
