/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { format } from '@wordpress/date';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	Flex,
	FlexItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { REVIEW_STATUSES } from '.~/product-feed/review-request/review-request-statuses';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';

const ReviewRequestNotice = ( { data, onRequestReviewClick = () => {} } ) => {
	const isDisabled = data.status === 'BLOCKED';

	return (
		<Flex
			data-testid="gla-review-request-notice"
			className="gla-review-request-notice"
		>
			<FlexItem>
				<Flex>
					<FlexItem className="gla-review-request-notice__icon">
						{ REVIEW_STATUSES[ data.status ].icon }
					</FlexItem>

					<FlexItem className="gla-review-request-notice__text">
						<Text variant="subtitle">
							{ REVIEW_STATUSES[ data.status ].title }
						</Text>
						<Text variant="body">
							{ REVIEW_STATUSES[ data.status ].body }
							{ isDisabled &&
								createInterpolateElement(
									sprintf(
										// translators: %s: The date to wait until the next request review.
										__(
											' You can request a new review on %s. <Link>Learn more</Link>',
											'google-listings-and-ads'
										),
										format(
											'd M Y',
											data.nextAttempt * 1000
										)
									),
									{
										Link: (
											<AppDocumentationLink
												href="https://support.google.com/merchants/answer/2948694"
												context="request-review"
												linkId="request-review-learn-more"
											/>
										),
									}
								) }
						</Text>
					</FlexItem>
				</Flex>
			</FlexItem>
			<FlexItem className="gla-review-request-notice__button">
				{ REVIEW_STATUSES[ data.status ].showRequestButton && (
					<AppButton
						isPrimary
						onClick={ onRequestReviewClick }
						disabled={ isDisabled }
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
