/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const AdsAccountSelectControl = ( props ) => {
	const { existingAccounts: accounts = [] } = useExistingGoogleAdsAccounts();

	const options = accounts?.map( ( acc ) => ( {
		value: acc.id,
		label: `${ acc.name } (${ acc.id })`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default AdsAccountSelectControl;
