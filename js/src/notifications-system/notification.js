/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { dateI18n } from '@wordpress/date';
import { addQueryArgs } from '@wordpress/url';
import { CardBody, Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { useAppDispatch } from '~/data';
import useSettings from '~/hooks/useSettings';
import AppButton from '~/components/app-button';
import NotificationSkeleton from './notification-skeleton';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import { handleApiError } from '~/utils/handleError';
import {
	recordGlaEvent,
	CONTEXT_MARKETING_OVERVIEW,
	REFERRER_TYPE_NOTIFICATION,
} from '~/utils/tracks';
import './notification.scss';

/**
 * Appends the notification's referrer info to a CTA href, so the destination
 * flow can attribute its own tracking events back to this notification.
 *
 * @param {string} href Original CTA destination.
 * @param {string} notificationId Notification ID to attribute the referral to.
 * @return {string} `href` with `referrer_type`/`referrer_id` query params appended.
 */
function withReferrer( href, notificationId ) {
	return addQueryArgs( href, {
		referrer_type: REFERRER_TYPE_NOTIFICATION,
		referrer_id: notificationId,
	} );
}

/**
 * @typedef {Object} NotificationAction
 * @property {string} id Unique key for the action.
 * @property {string} href Link destination.
 * @property {string} children Button label.
 * @property {string} [target] Link target (e.g. '_blank').
 * @property {string} [rel] Link rel attribute.
 * @property {string} [settingKey] When set, clicking the action first saves this settings
 *   field as `true` and only navigates to `href` once the save succeeds, instead of just navigating.
 */

/**
 * The `Notification` component is rendered.
 *
 * @event gla_notifications_system_notification_shown
 * @property {string} context Where the notification is shown, e.g. `'marketing-overview'`.
 * @property {string} id The notification ID.
 */

/**
 * A merchant clicks a notification's CTA.
 *
 * @event gla_notifications_system_notification_cta_clicked
 * @property {string} context Where the notification is shown, e.g. `'marketing-overview'`.
 * @property {string} id The notification ID.
 * @property {string} href The CTA link's destination.
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
 * @param {Function} [props.onDismiss] Callback invoked after dismissNotification succeeds.
 * @param {boolean} [props.isReady] Whether the notification data is ready. Renders a skeleton when false.
 * @fires gla_notifications_system_notification_shown with `{ context: 'marketing-overview', id }`.
 * @fires gla_notifications_system_notification_cta_clicked with `{ context: 'marketing-overview', id, href }`.
 */
const Notification = ( {
	id,
	title,
	description,
	triggeredAt,
	actions = [],
	onDismiss,
	isReady,
} ) => {
	const { dismissNotification } = useAppDispatch();
	const { settings, saveSettings } = useSettings();
	const [ savingActionId, setSavingActionId ] = useState( null );

	useEffect( () => {
		if ( isReady === false ) {
			return;
		}

		recordGlaEvent( 'gla_notifications_system_notification_shown', {
			context: CONTEXT_MARKETING_OVERVIEW,
			id,
		} );
	}, [ id, isReady ] );

	if ( isReady === false ) {
		return <NotificationSkeleton />;
	}

	const formattedDate = dateI18n(
		glaData.dateFormat,
		new Date( triggeredAt * 1000 )
	);

	const handleDismissClick = async () => {
		try {
			await dismissNotification( id );
			onDismiss( id );
		} catch {
			// dismissNotification failed, do not dismiss from slot store
		}
	};

	const handleCtaClick = async ( event, action ) => {
		const { id: actionId, href, settingKey } = action;

		recordGlaEvent( 'gla_notifications_system_notification_cta_clicked', {
			context: CONTEXT_MARKETING_OVERVIEW,
			id,
			href,
		} );

		if ( ! settingKey ) {
			return;
		}

		// This action must save the setting before navigating, so it can't
		// rely on the anchor's own default navigation.
		event.preventDefault();
		setSavingActionId( actionId );

		try {
			await saveSettings( { ...settings, [ settingKey ]: true } );
			window.location.assign( withReferrer( href, id ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error updating the setting. Please try again.',
					'google-listings-and-ads'
				)
			);
			setSavingActionId( null );
		}
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
							{ actions.map( ( action ) => {
								const {
									id: actionId,
									href,
									children,
									target,
									rel,
								} = action;

								return (
									<AppButton
										key={ actionId }
										className="gla-notification__action"
										variant="link"
										href={ withReferrer( href, id ) }
										target={ target }
										rel={ rel }
										loading={ savingActionId === actionId }
										disabled={
											savingActionId !== null &&
											savingActionId !== actionId
										}
										onClick={ ( event ) =>
											handleCtaClick( event, action )
										}
									>
										{ children }
									</AppButton>
								);
							} ) }
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
