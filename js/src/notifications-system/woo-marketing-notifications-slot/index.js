/**
 * Internal dependencies
 */
import init from './slot';
import './index.scss';

init();

export { registerNotifications } from './data';
export { default as useDismissNotification } from './hooks/useDismissNotification';
