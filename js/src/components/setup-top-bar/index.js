/**
 * Internal dependencies
 */
import './index.scss';

const SetupTopBar = ( props ) => {
	const { backLink, title, helpButton } = props;

	return (
		<div className="gla-setup-top-bar">
			{ backLink }
			<span className="title">{ title }</span>
			{ helpButton }
		</div>
	);
};

export default SetupTopBar;
