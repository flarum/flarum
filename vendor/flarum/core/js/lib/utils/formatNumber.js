/**
 * The `formatNumber` utility localizes a number into a string with the
 * appropriate punctuation.
 *
 * @example
 * formatNumber(1234);
 * // 1,234
 *
 * @param {Number} number
 * @return {String}
 */
export default function formatNumber(number) {
  return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
