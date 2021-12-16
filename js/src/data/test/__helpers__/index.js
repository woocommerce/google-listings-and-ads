/**
 * External dependencies
 */
import { cloneDeep, set, get, isPlainObject } from 'lodash';

/**
 * Use native `Object.freeze()` to make an object immutable, recursively freeze each property which is of type object.
 *
 * Copied from [Object.freeze() of MDN]{@link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/freeze}.
 *
 * @param {Object|Array} object The object or array to freeze.
 * @return {Object|Array} The `object` that was passed-in this function.
 */
export function deepFreeze( object ) {
	// Retrieve the property names defined on object
	const propNames = Object.getOwnPropertyNames( object );

	// Freeze properties before freezing self
	for ( const name of propNames ) {
		const value = object[ name ];
		if ( value && typeof value === 'object' ) {
			deepFreeze( value );
		}
	}

	return Object.freeze( object );
}

/**
 * @typedef {Object} CheckingGroup
 * @property {Object} ref The attached object for further reference checks.
 * @property {string} refPath The access path of `ref` in the object tree.
 */
/**
 * Loops through the passed-in `object` recursively and attaches (by mutating on passed-in `object`)
 * a new object as a reference checking mark to any nodes which is an plain object type.
 * Returns an array that contains each attached reference checking mark and its access path.
 *
 * @param {Object} object The object to be attached reference marks.
 * @param {Array<string>} ignore The paths to be ignored to attach reference marks.
 * @param {Array<CheckingGroup>} [checkingGroups=[]] The accumulator of attached reference marks passed by internal recursive call.
 * @param {Array<string>} [paths=[]] The current looping sub-tree paths by internal recursive call.
 *
 * @return {Array<CheckingGroup>} An array that contains each attached reference checking mark and its access path.
 */
function attachRef( object, ignore, checkingGroups = [], paths = [] ) {
	const refKey = '__refForCheck';

	for ( const [ name, value ] of Object.entries( object ) ) {
		if ( isPlainObject( value ) ) {
			const checkPaths = [ ...paths, name ];
			attachRef( value, ignore, checkingGroups, checkPaths );

			const currentPath = checkPaths.join( '.' );
			if ( ! ignore.includes( currentPath ) ) {
				const ref = {
					whyFail: `If there's no mutation at \`state.${ currentPath }\`, it should be kept the same reference.`,
				};
				checkingGroups.push( {
					ref,
					refPath: `${ currentPath }.${ refKey }`,
				} );
				value[ refKey ] = ref;
			}
		}
	}
	return checkingGroups;
}

/**
 * Creates a deep freeze state based on the passed-in state,
 * and also implants a `assertConsistentRef` function for reference check.
 * An initial value of a specific path can be set optionally to facilitate testing.
 *
 * Usage of `assertConsistentRef`:
 *   After getting the new state from `reducer( preparedState, action )`,
 *   calls `newState.assertConsistentRef()` for reference checking.
 *
 * @param {Object|Array} srcState The state to be attached deep freeze and reference checking.
 * @param {string} [path] The path of state to be set.
 * @param {*} [value] The initial value to be set.
 * @param {true|Array<string>} [ignoreRefCheckOnMutatingPath] Indicate state paths that don't require reference check.
 *   `true` - Set `true` to use the passed-in `path` parameter as the ignoring path.
 *   `Array<string>` - Given an array of state paths to be ignored.
 *
 * @return {Object} Prepared state.
 */
export function prepareImmutableStateWithRefCheck(
	srcState,
	path,
	value,
	ignoreRefCheckOnMutatingPath
) {
	const state = cloneDeep( srcState );
	if ( path ) {
		set( state, path, value );
	}

	// Prepare the paths to be ignored the reference check.
	const ignorePaths = [];
	if ( ignoreRefCheckOnMutatingPath === true ) {
		ignorePaths.push( path );
	} else if ( Array.isArray( ignoreRefCheckOnMutatingPath ) ) {
		ignorePaths.push( ...ignoreRefCheckOnMutatingPath );
	}
	const checkingGroups = attachRef( state, ignorePaths );

	state.assertConsistentRef = function () {
		if ( this === state ) {
			throw new Error(
				'Please use the state returned by `reducer` to check this assertion in the test case.'
			);
		}

		checkingGroups.forEach( ( { refPath, ref } ) => {
			// If you see this failed assertion, it may be because a reference has been changed unexpectedly, please check if other states have been changed. Or, the reference chage is expected but hasn't be specified ignoring. Please add that path to the `ignoreRefCheckOnMutatingPath` parameter.
			expect( get( this, refPath ) ).toBe( ref );
		} );
	};

	return deepFreeze( state );
}
