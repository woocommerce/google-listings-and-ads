/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { REVIEW_STATUSES } from '../constants';
import Text from '~/components/app-text';

const ReviewRequestNotice = ( {
	account,
	onRequestReviewClick = () => {},
} ) => {
	const accountReviewStatus = REVIEW_STATUSES[ account.status ];

	if ( ! accountReviewStatus ) {
		return null;
	}

	const { reviewAction } = account;
	const canRequestReview =
		accountReviewStatus.requestButton && reviewAction?.isAvailable;

	return (
		<Flex
			className="gla-review-request-notice"
			data-testid="gla-review-request-notice"
		>
			<FlexItem>
				<Flex>
					<FlexItem className="gla-review-request-notice__icon">
						{ accountReviewStatus.icon }
					</FlexItem>

					<FlexItem className="gla-review-request-notice__text">
						<Text variant="subtitle">
							{ accountReviewStatus.title }
						</Text>
						<Text
							className="gla-review-request-notice__text-body"
							variant="body"
						>
							{ accountReviewStatus.body }
						</Text>
					</FlexItem>
				</Flex>
			</FlexItem>
			<FlexItem className="gla-review-request-notice__button">
				{ canRequestReview &&
					( reviewAction.type === 'redirect' ? (
						<AppButton
							href={ reviewAction.uri }
							rel="noopener noreferrer"
							target="_blank"
							text={ __(
								'Request review',
								'google-listings-and-ads'
							) }
							isPrimary
						/>
					) : (
						<AppButton
							onClick={ onRequestReviewClick }
							text={ __(
								'Request review',
								'google-listings-and-ads'
							) }
							isPrimary
						/>
					) ) }
			</FlexItem>
		</Flex>
	);
};

export default ReviewRequestNotice;
