/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Text from '~/components/app-text';

/**
 * This component renders a message when no account issues are pending to solve.
 *
 * @return {JSX.Element} The component
 */
const IssuesSolved = () => {
	return (
		<div className="gla-issues-solved">
			<Dashicon icon="yes-alt" className="gla-issues-solved__icon" />
			<Text variant="subtitle">
				{ __(
					'All account issues resolved',
					'google-listings-and-ads'
				) }
			</Text>
		</div>
	);
};

export default IssuesSolved;
