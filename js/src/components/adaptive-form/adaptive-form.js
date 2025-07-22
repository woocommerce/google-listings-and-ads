/**
 * External dependencies
 */
import {
	useRef,
	useState,
	useEffect,
	useCallback,
	useImperativeHandle,
	forwardRef,
} from '@wordpress/element';
import { Form } from '@woocommerce/components';
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import useIsMounted from '~/hooks/useIsMounted';
import { AdaptiveFormContext } from './adaptive-form-context';

function isEvent( value ) {
	return ( value?.nativeEvent || value ) instanceof Event;
}

/**
 * @typedef {Object} AdaptiveFormHandler
 * @property {(initialValues: Object) => void} resetForm Reset form with given initial values.
 * @property {(name: string, value: *) => void} setValue Set the `name` field of the form states to the given `value`.
 */

/**
 * @typedef {Object} AdaptiveFormSubmissionEnhancer
 * @property {HTMLElement} submitter The element triggering the `handleSubmit` callback.
 * @property {() => void} signalFailedSubmission Function to signal AdaptiveForm that the submission is failed so that the AdaptiveForm will reset the submission states.
 */

const SUBMITTING = 'submitting';
const SUBMITTED = 'submitted';

/**
 * Renders an adapted form component that wraps the `Form` of `@woocommerce/components` with
 * several workarounds in order to be compatible with WC 6.9 to 7.1.
 *
 * This component includes additional enhancements to make AdaptiveForm have more useful or
 * reusable features. It could also be the playground of the practical instances before pushing
 * them upstream.
 *
 * @param {Object} props React props.
 * @param {(values: Object, submissionEnhancer: AdaptiveFormSubmissionEnhancer) => void} [props.onSubmit] Function to call when a form is requesting submission.
 * @param {(formContext: AdaptiveFormContext) => Object} [props.extendAdapter] Function to get the custom adapter to be appended to form's adapter so that they can be accessed via formContext.adapter.
 * @param {(formContext: AdaptiveFormContext) => JSX.Element | JSX.Element} props.children Children to be rendered. Could be a render prop function.
 * @param {import('react').MutableRefObject<AdaptiveFormHandler>} ref React ref to be attached to the handler of this component.
 */
function AdaptiveForm( { onSubmit, extendAdapter, children, ...props }, ref ) {
	const formRef = useRef();
	const adapterRef = useRef( { submitter: null } );
	const [ batchQueue, setBatchQueue ] = useState( [] );
	const [ delegation, setDelegation ] = useState();

	const queueSetValue = useCallback( ( ...args ) => {
		setBatchQueue( ( items ) => [ ...items, args ] );
	}, [] );

	useEffect( () => {
		if ( delegation ) {
			adapterRef.current.setValueCompatibly( ...delegation );
		}
	}, [ delegation ] );

	// Since WC 6.9, the exposed interfaces were completely changed. Given that
	// there is no longer a regular interface for updating Form values externally,
	// this is a workaround to add the access of `setValue` for external use.
	// Ref: https://github.com/woocommerce/woocommerce/blob/6.9.0/packages/js/components/src/form/form.tsx#L125-L127
	useImperativeHandle( ref, () => ( {
		// Placing `setValue` before object spreading is for compatibility <= 6.8
		setValue: queueSetValue,
		...formRef.current,
	} ) );

	/* === Start of enhancement-related codes === */

	const isMounted = useIsMounted();

	// Add states for form user sides to determine whether to show validation results.
	const [ validationRequestCount, setValidationRequestCount ] = useState( 0 );
	const showValidation = useCallback( () => {
		setValidationRequestCount( ( count ) => count + 1 );
	}, [] );
	const hideValidation = useCallback( () => {
		setValidationRequestCount( 0 );
	}, [] );

	// Add `isSubmitting` and `isSubmitted` states for facilitating across multiple layers of
	// component controlling, such as disabling inputs or buttons.
	const [ submission, setSubmission ] = useState( null );
	const isSubmitting = submission === SUBMITTING;
	const isSubmitted = submission === SUBMITTED;

	if ( onSubmit ) {
		props.onSubmit = async function ( values ) {
			setSubmission( SUBMITTING );

			let shouldResetSubmission = false;
			const submissionEnhancer = {
				submitter: adapterRef.current.submitter,
				signalFailedSubmission() {
					shouldResetSubmission = true;
				},
			};

			await onSubmit.call( this, values, submissionEnhancer );

			if ( isMounted() ) {
				adapterRef.current.submitter = null;

				if ( shouldResetSubmission ) {
					setSubmission( null );
				} else {
					setSubmission( SUBMITTED );
				}
			}
		};
	}

	/* === End of enhancement-related codes === */

	return (
		<Form { ...props } ref={ formRef }>
			{ ( { setValue, getInputProps, handleSubmit, ...formContext } ) => {
				// Since WC 6.9, the original Form is re-implemented as Functional component from
				// Class component. But when `setValue` is called, the closure of `values` is
				// referenced to the currently rendered snapshot states instead of a reference
				// that is continuously kept up to date to handle batch updates.
				//
				// Therefore, if the `setValue` is called more than once synchronously, the later call
				// will overwrite the previous update one by one, so that only the last call is updated
				// in the end.
				//
				// Ref:
				// - https://github.com/woocommerce/woocommerce/blob/6.8.2/packages/js/components/src/form/index.js#L42-L46
				// - https://github.com/woocommerce/woocommerce/blob/6.9.0/packages/js/components/src/form/form.tsx#L134-L138
				adapterRef.current.setValueCompatibly = ( name, value ) => {
					// WC 7.1 workaround handles the issue that after calling `setValue` to update
					// a single value, all form `values` will be triggered `onChange` individually,
					// even if those values don't actually change.
					//
					// Ref:
					// - https://github.com/woocommerce/woocommerce/blob/7.1.0/packages/js/components/src/form/form.tsx#L209-L211
					// - https://github.com/woocommerce/woocommerce/blob/7.1.0/packages/js/components/src/form/form.tsx#L182-L197
					if ( formContext.setValues ) {
						formContext.setValues( { [ name ]: value } );
					} else {
						// WC < 7.1 goes here as `setValues` was introduced in 7.1.
						setValue( name, value );
					}
				};

				// WC 6.9 workaround makes the reference of `formContext.setValue` stable to prevent
				// an infinite re-rendering loop when using `setValue` within `useEffect`.
				// Ref: https://github.com/woocommerce/woocommerce/blob/6.9.0/packages/js/components/src/form/form.tsx#L177
				formContext.setValue = queueSetValue;

				// The same WC 7.1 workaround as `setValueCompatibly` above, avoiding the
				// `getInputProps(name).onChange` calling the problematic `setValue`.
				//
				// Ref:
				// - https://github.com/woocommerce/woocommerce/blob/7.1.0/packages/js/components/src/form/form.tsx#L291-L293
				// - https://github.com/woocommerce/woocommerce/blob/7.1.0/packages/js/components/src/form/form.tsx#L215-L232
				formContext.getInputProps = ( name ) => {
					const inputProps = getInputProps( name );

					function onChange( value ) {
						// Get value from SyntheticEvent or native Event.
						if ( isEvent( value ) ) {
							if ( value.target.type === 'checkbox' ) {
								value = ! get( inputProps.values, name );
							} else {
								value = value.target.value;
							}
						}

						adapterRef.current.setValueCompatibly( name, value );
					}
					return {
						...inputProps,
						onChange,
					};
				};

				// Related to WC 6.9. Only one delegate can be consumed at a time in this render prop to
				// ensure the updating states will always be the latest when calling.
				if ( batchQueue.length ) {
					// Use `setTimeout` to avoid the warning of request state updates while rendering.
					// Mutating a React hook state is an anti-pattern in most cases. Here is done intentionally
					// because it's necessary to ensure this component will be triggered re-rendering through
					// `setBatchQueue`, but also to avoid calling `setBatchQueue` here and triggering additional
					// rendering again.
					setTimeout( () => setDelegation( batchQueue.shift() ) );
				}

				/* === Start of enhancement-related codes === */

				// Keep the target element for identifying which one triggered the submission when
				// there are multiple submit buttons.
				formContext.handleSubmit = function ( event ) {
					adapterRef.current.submitter = event?.currentTarget || null;
					return handleSubmit.call( this, event );
				};

				formContext.adapter = {
					isSubmitting,
					isSubmitted,
					submitter: adapterRef.current.submitter,
					validationRequestCount,
					requestedShowValidation: validationRequestCount > 0,
					showValidation,
					hideValidation,
				};

				if ( typeof extendAdapter === 'function' ) {
					const customAdapter = extendAdapter( formContext );
					Object.assign( formContext.adapter, customAdapter );
				}

				/* === End of enhancement-related codes === */

				// Since WC 6.9, it added the ability to obtain Form context via `useFormContext` hook.
				// However, if a component uses that hook, the compatible fixes in this component won't
				// be applied to form context. Therefore, here creates a context as well to make
				// AdaptiveForm's context also apply compatible fixes.
				// Ref: https://github.com/woocommerce/woocommerce/blob/6.9.0/packages/js/components/src/form/form.tsx#L277-L317
				return (
					<AdaptiveFormContext.Provider value={ formContext }>
						{ typeof children === 'function'
							? children( formContext )
							: children }
					</AdaptiveFormContext.Provider>
				);
			} }
		</Form>
	);
}

export default forwardRef( AdaptiveForm );
