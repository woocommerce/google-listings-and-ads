/**
 * Internal dependencies
 */
import initMarketingNotificationsSlot from './slot';

export { registerNotifications as registerNotificationsInMarketingSlot } from './data';
export { default as useDismissNotificationFromMarketingSlot } from './hooks/useDismissNotification';

/**
 * Self-initializes on script load so the slot mounts on its own, independent
 * of any particular plugin's bundle depending on and calling into it.
 */
initMarketingNotificationsSlot();
