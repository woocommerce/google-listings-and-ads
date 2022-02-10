/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import { ISSUE_TYPE_ACCOUNT } from '.~/constants';
import ReviewRequestModal from './review-request-modal';
import ReviewRequestNotice from './review-request-notice';
import REVIEW_STATUSES from './review-request-statuses';
import './index.scss';

const showNotice = ( status ) =>
	Object.keys( REVIEW_STATUSES ).includes( status );

const ReviewRequest = ( { account = {} } ) => {
	const [ modalActive, setModalActive ] = useState( false );
	const activeIssueType = useActiveIssueType();
	const { data: accountData, hasFinishedResolution } = account;

	if (
		! hasFinishedResolution ||
		! showNotice( accountData.status ) ||
		activeIssueType !== ISSUE_TYPE_ACCOUNT
	) {
		return null;
	}

	return (
		<div className="gla-review-request">
			<ReviewRequestModal
				issues={ accountData?.issues }
				isActive={ modalActive }
				onClose={ () => {
					setModalActive( false );
				} }
			/>
			<ReviewRequestNotice
				account={ accountData }
				onRequestReviewClick={ () => setModalActive( true ) }
			/>
		</div>
	);
};

export default ReviewRequest;
