/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import Text from '.~/components/app-text';

const COLLAPSED_ISSUES_SIZE = 5;

const ReviewRequestIssues = ( { issues = [] } ) => {
	const [ expanded, setExpanded ] = useState( false );
	if ( ! issues.length ) return null;

	const toggleExpanded = () => {
		recordEvent( 'gla_request_review_issue_list_toggle_click', {
			action: expanded ? 'collapse' : 'expand',
		} );
		setExpanded( ! expanded );
	};

	const issuesToRender = expanded
		? issues
		: issues.slice( 0, COLLAPSED_ISSUES_SIZE );

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
					<li key={ issue.code }>{ issue.issue }</li>
				) ) }
			</ul>
			{ issues.length > COLLAPSED_ISSUES_SIZE && (
				<AppButton isTertiary onClick={ toggleExpanded }>
					{ expanded
						? __( 'Show less', 'google-listing-and-ads' )
						: sprintf(
								// translators: %d: The number of extra issues issues
								__(
									'+ %d more issue(s)',
									'google-listing-and-ads'
								),
								issues.length - COLLAPSED_ISSUES_SIZE
						  ) }
				</AppButton>
			) }
		</>
	);
};

export default ReviewRequestIssues;
