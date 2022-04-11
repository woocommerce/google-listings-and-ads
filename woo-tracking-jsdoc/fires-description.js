/**
 * JSDoc plugin that allows to use descriptions for built-in `@fires` & `@emmits` tags.
 *
 * Overwrites the standard definition with `canHaveName: true` option,
 * and tweaks `applyNamespace` to apply it only to `value.name`.
 */
const { applyNamespace } = require( 'jsdoc/name' );

exports.defineTags = function ( dictionary ) {
	dictionary
		.defineTag( 'fires', {
			mustHaveValue: true,
			canHaveName: true,
			onTagged( doclet, tag ) {
				doclet.fires = doclet.fires || [];
				tag.value.name = applyNamespace( tag.value.name, 'event' );
				doclet.fires.push( tag.value );
			},
		} )
		.synonym( ' emmits' );
};
