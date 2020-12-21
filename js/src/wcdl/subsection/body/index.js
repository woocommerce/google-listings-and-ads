/**
 * Internal dependencies
 */
import './index.scss';

const Body = ( props ) => {
	const { children } = props;

	return <div className="wcdl-subsection-body">{ children }</div>;
};

export default Body;
