/**
 * Internal dependencies
 */
import './index.scss';

const Title = ( props ) => {
	const { children } = props;

	return <div className="wcdl-subsection-title">{ children }</div>;
};

export default Title;
