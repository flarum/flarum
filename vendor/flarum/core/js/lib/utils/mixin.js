/**
 * The `mixin` utility assigns the properties of a set of 'mixin' objects to
 * the prototype of a parent object.
 *
 * @example
 * class MyClass extends mixin(ExistingClass, evented, etc) {}
 *
 * @param {Class} Parent The class to extend the new class from.
 * @param {...Object} mixins The objects to mix in.
 * @return {Class} A new class that extends Parent and contains the mixins.
 */
export default function mixin(Parent, ...mixins) {
  class Mixed extends Parent {}

  mixins.forEach(object => {
    Object.assign(Mixed.prototype, object);
  });

  return Mixed;
}
