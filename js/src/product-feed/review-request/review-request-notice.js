/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { format as formatDate } from '@wordpress/date';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import REVIEW_STATUSES from './review-request-statuses';
import { glaData } from '.~/constants';
import Text from '.~/components/app-text';

const ReviewRequestNotice = ( {
	account,
	onRequestReviewClick = () => {},
} ) => {
	const accountReviewStatus = REVIEW_STATUSES[ account.status ];

	if ( ! accountReviewStatus ) {
		return null;
	}

	const cooldown =
		account.cooldown &&
		sprintf(
			// translators: %s: Cool down period date.
			__(
				'Your account is under cool down period. You can request a new review on %s.',
				'google-listings-and-ads'
			),
			formatDate(
				`${ glaData.dateFormat }, ${ glaData.timeFormat }`,
				new Date( account.cooldown )
			)
		);

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
							{ cooldown || accountReviewStatus.body }
						</Text>
					</FlexItem>
				</Flex>
			</FlexItem>
			<FlexItem className="gla-review-request-notice__button">
				{ accountReviewStatus.requestButton &&
					( account.cooldown ||
						Object.keys( account.reviewEligibleRegions )?.length >
							0 ) && (
						<AppButton
							isPrimary
							onClick={ onRequestReviewClick }
							disabled={ !! account.cooldown }
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
