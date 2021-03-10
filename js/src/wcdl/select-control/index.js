/**
 * External dependencies
 */
import { SelectControl as WCSelectControl } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const SelectControl = ( props ) => {
	const { label, helperText, className, ...rest } = props;

	return (
		<div className={ classnames( 'wcdl-select-control', className ) }>
			{ label && (
				<div className="wcdl-select-control__label">{ label }</div>
			) }
			<div className="wcdl-select-control__input">
				<WCSelectControl { ...rest } />
			</div>
			{ helperText && (
				<div className="wcdl-select-control__helper-text">
					{ helperText }
				</div>
			) }
		</div>
	);
};

export default SelectControl;
