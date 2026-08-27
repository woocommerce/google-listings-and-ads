/**
 * External dependencies
 */
import { CardBody, Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './notification-skeleton.scss';

/**
 * Skeleton placeholder for a notification card, rendered while the notification's
 * dynamic config is still resolving. Mirrors the layout of the Notification component.
 */
const NotificationSkeleton = () => {
	return (
		<CardBody
			align="flex-start"
			className="gla-notification gla-notification-skeleton"
			gap="4"
		>
			<FlexItem>
				<span className="gla-notification-skeleton__logo" />
			</FlexItem>
			<FlexBlock className="gla-notification__body">
				<Flex direction="column" gap="1">
					<span className="gla-notification-skeleton__title" />
					<span className="gla-notification-skeleton__description" />
					<Flex
						align="center"
						className="gla-notification__footer"
						justify="start"
					>
						<span className="gla-notification-skeleton__date" />
						<span className="gla-notification-skeleton__action" />
					</Flex>
				</Flex>
			</FlexBlock>
		</CardBody>
	);
};

export default NotificationSkeleton;
