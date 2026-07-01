export { default as createMarketingNotificationsSlot } from './slot';
export {
	registerNotifications as registerNotificationsInMarketingSlot,
	setNotifications as setNotificationsInMarketingSlot,
} from './data';
export { default as useDismissNotificationFromMarketingSlot } from './hooks/useDismissNotification';
export { SYNC_MARKETING_NOTIFICATIONS_EVENT } from './constants';
