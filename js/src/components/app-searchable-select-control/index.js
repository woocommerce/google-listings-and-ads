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
			className="gla-app-searchable-select-control"
			{ ...props }
		/>
	);
};

export default AppSearchableSelectControl;
