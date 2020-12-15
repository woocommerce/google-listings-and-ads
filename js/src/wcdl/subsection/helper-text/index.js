/**
 * Internal dependencies
 */
import './index.scss';

const HelperText = ( props ) => {
	const { children } = props;

	return <div className="wcdl-subsection-helper-text">{ children }</div>;
};

export default HelperText;
