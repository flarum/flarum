/**
 * Extend an object's method by running its output through a mutating callback
 * every time it is called.
 *
 * The callback accepts the method's return value and should perform any
 * mutations directly on this value. For this reason, this function will not be
 * effective on methods which return scalar values (numbers, strings, booleans).
 *
 * Care should be taken to extend the correct object – in most cases, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example
 * extend(Discussion.prototype, 'badges', function(badges) {
 *   // do something with `badges`
 * });
 *
 * @param {Object} object The object that owns the method
 * @param {String} method The name of the method to extend
 * @param {function} callback A callback which mutates the method's output
 */
export function extend(object, method, callback) {
  const original = object[method];

  object[method] = function(...args) {
    const value = original ? original.apply(this, args) : undefined;

    callback.apply(this, [value].concat(args));

    return value;
  };

  Object.assign(object[method], original);
}

/**
 * Override an object's method by replacing it with a new function, so that the
 * new function will be run every time the object's method is called.
 *
 * The replacement function accepts the original method as its first argument,
 * which is like a call to 'super'. Any arguments passed to the original method
 * are also passed to the replacement.
 *
 * Care should be taken to extend the correct object – in most cases, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example
 * override(Discussion.prototype, 'badges', function(original) {
 *   const badges = original();
 *   // do something with badges
 *   return badges;
 * });
 *
 * @param {Object} object The object that owns the method
 * @param {String} method The name of the method to override
 * @param {function} newMethod The method to replace it with
 */
export function override(object, method, newMethod) {
  const original = object[method];

  object[method] = function(...args) {
    return newMethod.apply(this, [original.bind(this)].concat(args));
  };

  Object.assign(object[method], original);
}
