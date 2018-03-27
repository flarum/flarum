/**
 * The `extract` utility deletes a property from an object and returns its
 * value.
 *
 * @param {Object} object The object that owns the property
 * @param {String} property The name of the property to extract
 * @return {*} The value of the property
 */
export default function extract(object, property) {
  const value = object[property];

  delete object[property];

  return value;
}
