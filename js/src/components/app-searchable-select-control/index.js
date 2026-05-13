/**
 * Internal dependencies
 */
import SearchableSelectControl from '../searchable-select-control';
import './index.scss';

/**
 * Wrapper around SearchableSelectControl to apply consistent styling across the app.
 */
const AppSearchableSelectControl = ( props ) => {
	return (
		<SearchableSelectControl
			{ ...props }
			className="gla-app-searchable-select-control"
		/>
	);
};

export default AppSearchableSelectControl;
