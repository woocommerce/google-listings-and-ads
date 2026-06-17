/**
 * Internal dependencies
 */
import init from './slot';
import './index.scss';

init();

export { registerNotifications as registerNotificationsInMarketingSlot } from './data';
export { default as useDismissNotificationFromMarketingSlot } from './hooks/useDismissNotification';
