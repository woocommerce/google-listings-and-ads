/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { Flex, FlexBlock, FlexItem, Icon } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

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
 * @property {string}  id        Unique key for the action.
 * @property {string}  href      Link destination.
 * @property {string}  children  Button label.
 * @property {string}  [target]  Link target (e.g. '_blank').
 * @property {string}  [rel]     Link rel attribute.
 */

/**
 * Base notification card component.
 *
 * @param {Object}               props
 * @param {string}               props.id          Notification ID, used to call dismissNotification on dismiss.
 * @param {string}               props.title       Notification headline.
 * @param {string}               props.description Notification body text.
 * @param {number}               props.triggeredAt Unix timestamp (seconds) when the notification was triggered.
 * @param {NotificationAction[]} props.actions     CTA buttons.
 */
const Notification = ( {
	id,
	title,
	description,
	triggeredAt,
	actions = [],
} ) => {
	const { dismissNotification } = useAppDispatch();
	const formattedDate = dateI18n(
		glaData.dateFormat,
		new Date( triggeredAt * 1000 )
	);

	const handleDismissClick = () => {
		dismissNotification( id );
	};

	return (
		<Flex align="flex-start" className="gla-notification" gap="4">
			<FlexItem>
				<img
					src={ googleLogoURL }
					alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
					width="24"
					height="24"
				/>
			</FlexItem>
			<FlexBlock className="gla-notification__body">
				<Flex direction="column" gap="1">
					<h3 className="gla-notification__title">{ title }</h3>
					<p className="gla-notification__description">
						{ description }
					</p>

					<Flex
						className="gla-notification__footer"
						align="center"
						justify="start"
						wrap="wrap"
					>
						<p className="gla-notification__date">
							{ formattedDate }
						</p>
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
					</Flex>
				</Flex>
			</FlexBlock>
			<FlexBlock
				className="gla-notification__dismiss-container"
				align="flex-start"
			>
				<AppButton
					className="gla-notification__dismiss"
					onClick={ handleDismissClick }
					aria-label={ __(
						'Dismiss notification',
						'google-listings-and-ads'
					) }
				>
					<Icon icon={ closeSmall } />
				</AppButton>
			</FlexBlock>
		</Flex>
	);
};

export default Notification;
