/**
 * Internal dependencies
 */
import useGoogleBusinessProfileLocations from '../../hooks/useGoogleBusinessProfileLocations';
import AppSelectControl from '~/components/app-select-control';
import formatLocationAddress from '../../format-location-address';

/**
 * Renders an `AppSelectControl` sourced from the candidate Google Business Profile locations.
 *
 * @param {Object} props The component props, forwarded to `AppSelectControl`.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const GoogleBusinessProfileLocationSelectControl = ( props ) => {
	const { locations } = useGoogleBusinessProfileLocations();

	const options = locations?.map( ( location ) => ( {
		value: location.id,
		label: formatLocationAddress( location ),
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default GoogleBusinessProfileLocationSelectControl;
