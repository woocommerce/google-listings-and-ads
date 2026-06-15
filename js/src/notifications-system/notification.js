/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { CardBody, Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import './notification.scss';

/**
 * @typedef {Object} NotificationAction
 * @property {string} id Unique key for the action.
 * @property {string} href Link destination.
 * @property {string} children Button label.
 * @property {string} [target] Link target (e.g. '_blank').
 * @property {string} [rel] Link rel attribute.
 */

/**
 * Base notification card component.
 *
 * @param {Object} props
 * @param {string} props.id Notification ID, used to call dismissNotification on dismiss.
 * @param {string} props.title Notification headline.
 * @param {string} props.description Notification body text.
 * @param {number} props.triggeredAt Unix timestamp (seconds) when the notification was triggered.
 * @param {NotificationAction[]} props.actions CTA buttons.
 * @param {Function} [props.onDismiss] Optional override for the dismiss handler. When omitted, dismissNotification is called on the GLA data store.
 */
const Notification = ( {
	id,
	title,
	description,
	triggeredAt,
	actions = [],
	onDismiss = noop,
} ) => {
	const { dismissNotification } = useAppDispatch();
	const formattedDate = dateI18n(
		glaData.dateFormat,
		new Date( triggeredAt * 1000 )
	);

	const handleDismissClick = async () => {
		try {
			await dismissNotification( id );
		} catch {
			return;
		}
		onDismiss( id );
	};

	return (
		<CardBody align="flex-start" className="gla-notification" gap="4">
			<FlexItem>
				<img
					src={ googleLogoURL }
					alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
					width="16"
					height="16"
				/>
			</FlexItem>
			<FlexBlock className="gla-notification__body">
				<Flex direction="column" gap="1">
					<p className="gla-notification__title">{ title }</p>
					<p className="gla-notification__description">
						{ description }
					</p>

					<Flex
						className="gla-notification__footer"
						align="center"
						justify="start"
						wrap="wrap"
					>
						<span className="gla-notification__date">
							{ formattedDate }
						</span>
						<FlexBlock>
							{ actions.map(
								( {
									id: actionId,
									href,
									children,
									target,
									rel,
								} ) => (
									<AppButton
										key={ actionId }
										className="gla-notification__action"
										variant="link"
										href={ href }
										target={ target }
										rel={ rel }
									>
										{ children }
									</AppButton>
								)
							) }
						</FlexBlock>
					</Flex>
				</Flex>
			</FlexBlock>
			<FlexItem>
				<AppButton
					aria-label={ __(
						'Dismiss notification',
						'google-listings-and-ads'
					) }
					className="gla-notification__dismiss"
					onClick={ handleDismissClick }
					icon={ closeSmall }
				/>
			</FlexItem>
		</CardBody>
	);
};

export default Notification;
