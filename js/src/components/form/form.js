/**
 * External dependencies
 */
import { Form as WCForm } from '@woocommerce/components';
import { useCallback } from '@wordpress/element';

function alwaysValid() {
	return {};
}

/**
 * Wraps a Form component of @woocommerce/components with patched props
 * to avoid some compatible issues since WooCommerce 6.9.
 *
 * - Patched the dated parameters of the `props.onChange` callback.
 *
 * @param {*} props Props to be forwarded to {@link WCForm}.
 */
export default function Form( { onChange, ...props } ) {
	const { validate = alwaysValid } = props;

	const patchedOnChange = useCallback(
		( change, datedValues ) => {
			// The changed value in the second parameter is not up-to-date,
			// and the third parameter uses a wrong reference of `error` variable.
			// See: https://github.com/woocommerce/woocommerce/blob/78c28ae9f3a70e996e40c4f173c032dfa0d0551a/packages/js/components/src/form/form.tsx#L170-L171
			const values = { ...datedValues, [ change.name ]: change.value };
			const isValid = ! Object.keys( validate( values ) ).length;
			onChange( change, values, isValid );
		},
		[ onChange, validate ]
	);

	if ( onChange ) {
		props.onChange = patchedOnChange;
	}

	return <WCForm { ...props } />;
}
