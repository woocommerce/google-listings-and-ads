/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
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
		<div className="gla-notification">
			<div className="gla-notification__logo-container">
				<img
					src={ googleLogoURL }
					alt="Google"
					className="gla-notification__logo"
					width="24"
					height="24"
				/>
			</div>
			<div className="gla-notification__body">
				<h3 className="gla-notification__title">{ title }</h3>
				<p className="gla-notification__description">{ description }</p>

				<div className="gla-notification__actions">
					<p className="gla-notification__date">{ formattedDate }</p>
					{ actions.length > 0 &&
						actions.map( ( actionProps, index ) => (
							<AppButton
								key={ index }
								className="gla-notification__action"
								variant="link"
								{ ...actionProps }
							/>
						) ) }
				</div>
			</div>
			<div className="gla-notification__dismiss-container">
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
		</div>
	);
};

export default Notification;
