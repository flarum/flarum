/**
 * The `abbreviateNumber` utility converts a number to a shorter localized form.
 *
 * @example
 * abbreviateNumber(1234);
 * // "1.2K"
 *
 * @param {Integer} number
 * @return {String}
 */
export default function abbreviateNumber(number) {
  // TODO: translation
  if (number >= 1000000) {
    return Math.floor(number / 1000000) + app.translator.trans('core.lib.number_suffix.mega_text');
  } else if (number >= 1000) {
    return Math.floor(number / 1000) + app.translator.trans('core.lib.number_suffix.kilo_text');
  } else {
    return number.toString();
  }
}
