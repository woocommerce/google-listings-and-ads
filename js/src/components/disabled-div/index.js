/**
 * Internal dependencies
 */
import './index.scss';

const DisabledDiv = ( props ) => {
	const { disabled = true, children } = props;

	return (
		<div
			className={ `gla_disabled_div ${
				disabled && 'gla_disabled_div__disabled'
			}` }
		>
			{ children }
		</div>
	);
};

export default DisabledDiv;
