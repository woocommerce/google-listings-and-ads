/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';

/**
 * @param {Object} props The component props
 * @param {string} [props.value] The selected value. IF no value is defined, then the first option is selected and onChange function is triggered.
 * @param {Function} [props.onChange] Callback when the select value changes.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const AdsAccountSelectControl = ( {
	value,
	onChange = () => {},
	...props
} ) => {
	const { existingAccounts: accounts = [] } = useExistingGoogleAdsAccounts();

	const options = accounts.map( ( acc ) => ( {
		value: acc.id,
		label: `${ acc.name } (${ acc.id })`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			onChange={ onChange }
			value={ value }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default AdsAccountSelectControl;
