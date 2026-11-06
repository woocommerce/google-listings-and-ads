/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders the `DataViews` component from the `window.wp.dataviews` global,
 * passing all props through unchanged.
 *
 * The `window.wp.dataviews` global must be available before rendering this
 * component, e.g. by waiting until `useDataViewsScript` returns `'ready'`.
 *
 * @param {Object} props Props forwarded to `DataViews`.
 * @return {JSX.Element} The rendered DataViews component.
 */
const AppDataViews = ( props ) => {
	const { DataViews } = window.wp.dataviews;

	return <DataViews { ...props } />;
};

export default AppDataViews;
