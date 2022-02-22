/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	Flex,
	FlexItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import REVIEW_STATUSES from './review-request-statuses';

const ReviewRequestNotice = ( {
	account,
	onRequestReviewClick = () => {},
} ) => {
	const accountReviewStatus = REVIEW_STATUSES[ account.status ];

	return (
		<Flex
			data-testid="gla-review-request-notice"
			className="gla-review-request-notice"
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
				{ accountReviewStatus.requestButton && (
					<AppButton
						isPrimary
						onClick={ onRequestReviewClick }
						disabled={ accountReviewStatus.requestButton.disabled }
						text={ __(
							'Request review',
							'google-listings-and-ads'
						) }
					/>
				) }
			</FlexItem>
		</Flex>
	);
};

export default ReviewRequestNotice;
