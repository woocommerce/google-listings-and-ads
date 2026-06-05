/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import googleLogoURL from '~/images/logo/google-logo.svg';
import './notification.scss';

/**
 * Base notification card component.
 *
 * @param {Object}   props
 * @param {string}   props.id          Notification ID, used to call dismissNotification on dismiss.
 * @param {string}   props.title       Notification headline.
 * @param {string}   props.description Notification body text.
 * @param {number}   props.triggeredAt Unix timestamp (seconds) when the notification was triggered.
 * @param {Array}    props.actions     Array of AppButton prop objects for CTA buttons.
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

	return (
		<Card className="gla-notification">
			<div className="gla-notification__header">
				<img
					src={ googleLogoURL }
					alt="Google"
					className="gla-notification__logo"
					width="64"
					height="22"
				/>
				<button
					type="button"
					className="gla-notification__dismiss"
					onClick={ () => dismissNotification( id ) }
					aria-label={ __(
						'Dismiss notification',
						'google-listings-and-ads'
					) }
				>
					&#x2715;
				</button>
			</div>
			<div className="gla-notification__body">
				<h3 className="gla-notification__title">{ title }</h3>
				<p className="gla-notification__date">{ formattedDate }</p>
				<p className="gla-notification__description">{ description }</p>
			</div>
			{ actions.length > 0 && (
				<div className="gla-notification__actions">
					{ actions.map( ( actionProps, index ) => (
						<AppButton key={ index } { ...actionProps } />
					) ) }
				</div>
			) }
		</Card>
	);
};

export default Notification;
