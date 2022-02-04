/**
 * External dependencies
 */
import { __experimentalText as Text, Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const ReviewRequestIssues = ( { issues = [] } ) => {
	const [ expanded, setExpanded ] = useState( false );

	const toggleExpanded = () => {
		setExpanded( ! expanded );
		return false;
	};

	const issuesToRender = issues.slice( 0, expanded ? issues.length : 5 );

	return (
		<>
			<Text variant="subtitle">
				{ __(
					'Request a review on the following issue(s):',
					'google-listings-and-ads'
				) }
			</Text>
			<ul className="gla-review-request-modal__issue-list">
				{ issuesToRender.map( ( issue ) => (
					<li key={ issue }>{ issue }</li>
				) ) }
			</ul>
			{ issues.length > 5 && (
				<Button isTertiary onClick={ toggleExpanded }>
					{ expanded
						? __( 'Show less', 'google-listing-and-ads' )
						: sprintf(
								// translators: %d: The number of extra issues issues
								__(
									'+ %d more issue(s)',
									'google-listing-and-ads'
								),
								issues.length - 5
						  ) }
				</Button>
			) }
		</>
	);
};

export default ReviewRequestIssues;
