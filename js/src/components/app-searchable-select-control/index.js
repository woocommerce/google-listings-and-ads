/**
 * Internal dependencies
 */
import SearchableSelectControl from '../searchable-select-control';
import './index.scss';

const AppSearchableSelectControl = ( props ) => {
	return (
		<SearchableSelectControl
			className="gla-app-searchable-select-control"
			{ ...props }
		/>
	);
};

export default AppSearchableSelectControl;
