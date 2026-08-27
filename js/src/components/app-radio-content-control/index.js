/**
 * External dependencies
 */
import classnames from 'classnames';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const AppRadioContentControl = ( props ) => {
	const {
		className,
		label,
		value,
		selected,
		collapsible = false,
		children,
		...rest
	} = props;

	const isSelected = selected === value;

	return (
		<div className={ classnames( 'app-radio-content-control', className ) }>
			<RadioControl
				{ ...rest }
				checked={ isSelected }
				help=""
				options={ [
					{
						label,
						value,
					},
				] }
				selected={ selected }
			/>
			{ ( ! collapsible || isSelected ) && (
				<div className="app-radio-content-control__content">
					{ children }
				</div>
			) }
		</div>
	);
};

export default AppRadioContentControl;
