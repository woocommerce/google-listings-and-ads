/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * @typedef {import('react').React} React
 */

/**
 * @typedef {Object} InputProps
 * @property {*} value Form value.
 * @property {boolean} checked Form value converted to boolean.
 * @property {*} selected Form value.
 * @property {(value: Event<HTMLInputElement> | *) => void} onChange Function to handle onChange event.
 * @property {() => void} onBlur Function to handle onBlur event.
 * @property {'has-error' | undefined} className 'has-error' if the form value is invalid and marked as touched. `undefined` otherwise.
 * @property {string | undefined | null} help The corresponding value in form `errors` if the form value is marked as touched. `null` otherwise.
 */

/**
 * @typedef {Object} AdaptiveFormContextAdapter
 * @property {boolean} isSubmitting `true` if the form is currently being submitted.
 * @property {boolean} isSubmitted Set to `true` after the form is submitted. Initial value and during submission are set to `false`.
 * @property { HTMLElement | null} submitter Set to the element triggering the `handleSubmit` callback until the processing of `onSubmit` is completed. `null` otherwise.
 */

/**
 * @typedef {Object} AdaptiveFormContext
 * @property {Object} values Form values, e.g. `{ nickname: '' age: 0 }`.
 * @property {Object} errors Object with key-value pairs representing errors for form values. Empty object if no errors. For example, `{ nickname: 'Nickname is required.' }`.
 * @property {Object} touched Object with key-value pairs representing the corresponding input fields of the form values have received focus, e.g. `{ nickname: true }`.
 * @property {boolean} isValidForm `true` if form values pass the validation.
 * @property {boolean} isDirty `true` after any of the form values is modified.
 * @property {(name: string, value: *) => void} setValue Function to set a form value.
 * @property {(name: string) => InputProps} getInputProps Function to get the corresponding input props by a name of the form `values`. The returned props is usually used to assign to input field.
 * @property {() => Promise<Object>} handleSubmit Function to trigger form submission.
 * @property {(initialValues: Object) => void} resetForm Function to reset form with given initial values.
 * @property {React.Dispatch<React.SetStateAction<Object>>} setTouched Function to update the `touched` state, e.g. `setTouched( { nickname: false } )`.
 * @property {AdaptiveFormContextAdapter} adapter Additional enhancements to AdaptiveForm.
 */

export const AdaptiveFormContext = createContext( null );

/**
 * AdaptiveForm's context hook.
 *
 * @return {AdaptiveFormContext} AdaptiveForm's context.
 * @throws Will throw an error if its context provider is not existing in its parents.
 */
export function useAdaptiveFormContext() {
	const adaptiveFormContext = useContext( AdaptiveFormContext );

	if ( adaptiveFormContext === null ) {
		throw new Error(
			'useAdaptiveFormContext was used outside of its context provider AdaptiveForm.'
		);
	}

	return adaptiveFormContext;
}
