/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { Card, CardHeader, CardDivider } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Badge } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useNotifications from '~/hooks/useNotifications';
import Notification from './notification';
import useNotificationsSystemMap from './useNotificationsSystemMap';
import Text from '~/components/app-text';
import './notifications-panel.scss';

/**
 * Renders the list of active notifications fetched from the notifications API.
 *
 * Looks up each notification's display config from {@link useNotificationsSystemMap}
 * by ID and renders a {@link Notification} directly, and re-fetches notifications
 * whenever the browser tab becomes visible.
 *
 * @return {JSX.Element|null} A panel of notification cards, or null if there are no active notifications.
 */
const NotificationsPanel = () => {
	const { notifications } = useNotifications();
	const { invalidateResolutionForStoreSelector } = useAppDispatch();
	const notificationMap = useNotificationsSystemMap();

	useEffect( () => {
		const handleVisibilityChange = () => {
			if ( ! document.hidden ) {
				invalidateResolutionForStoreSelector( 'getNotifications' );
			}
		};

		document.addEventListener( 'visibilitychange', handleVisibilityChange );

		return () => {
			document.removeEventListener(
				'visibilitychange',
				handleVisibilityChange
			);
		};
	}, [ invalidateResolutionForStoreSelector ] );

	if ( ! notifications.length ) {
		return null;
	}

	return (
		<Card className="gla-notifications-panel">
			<CardHeader>
				<Text
					variant="title-small"
					className="gla-notifications-panel__title woocommerce-marketing-card-header-title"
				>
					{ __( 'Action required', 'google-listings-and-ads' ) }
					<Badge
						count={ notifications.length }
						className="gla-notifications-panel__badge"
					/>
				</Text>
			</CardHeader>

			{ notifications.map( ( { id, triggered_at }, index ) => {
				const config = notificationMap[ id ];

				if ( ! config ) {
					return null;
				}

				return (
					<>
						<Notification
							key={ id }
							id={ id }
							triggeredAt={ triggered_at }
							{ ...config }
						/>

						{ index !== notifications.length - 1 && (
							<CardDivider />
						) }
					</>
				);
			} ) }
		</Card>
	);
};

export default NotificationsPanel;
