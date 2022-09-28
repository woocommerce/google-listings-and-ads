/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';

/**
 * Renders a selector for choosing the source field.
 *
 * @param { Object } props The component props
 * @param { Array } props.sources The sources available for the selector
 * @param { JSX.Element } props.help Help text or component to be render at the bottom of the selector
 */
const AttributeMappingFieldSourcesControl = ( {
	sources = [],
	help,
	...props
} ) => {
	return (
		<>
			<AppSelectControl
				options={ [
					{
						value: '',
						label: __(
							'Select one option',
							'google-listings-and-ads'
						),
					},
					...sources.map( ( source ) => {
						return {
							...source,
							disabled: source.value.includes( 'disabled:' ),
						};
					} ),
				] }
				{ ...props }
			/>
			{ help }
		</>
	);
};

export default AttributeMappingFieldSourcesControl;
