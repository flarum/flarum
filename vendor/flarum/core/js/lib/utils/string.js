/**
 * Truncate a string to the given length, appending ellipses if necessary.
 *
 * @param {String} string
 * @param {Number} length
 * @param {Number} [start=0]
 * @return {String}
 */
export function truncate(string, length, start = 0) {
  return (start > 0 ? '...' : '') +
    string.substring(start, start + length) +
    (string.length > start + length ? '...' : '');
}

/**
 * Create a slug out of the given string. Non-alphanumeric characters are
 * converted to hyphens.
 *
 * @param {String} string
 * @return {String}
 */
export function slug(string) {
  return string.toLowerCase()
    .replace(/[^a-z0-9]/gi, '-')
    .replace(/-+/g, '-')
    .replace(/-$|^-/g, '') || '-';
}

/**
 * Strip HTML tags and quotes out of the given string, replacing them with
 * meaningful punctuation.
 *
 * @param {String} string
 * @return {String}
 */
export function getPlainContent(string) {
  const html = string
    .replace(/(<\/p>|<br>)/g, '$1 &nbsp;')
    .replace(/<img\b[^>]*>/ig, ' ');

  const dom = $('<div/>').html(html);

  dom.find(getPlainContent.removeSelectors.join(',')).remove();

  return dom.text().replace(/\s+/g, ' ').trim();
}

/**
 * An array of DOM selectors to remove when getting plain content.
 *
 * @type {Array}
 */
getPlainContent.removeSelectors = ['blockquote', 'script'];

/**
 * Make a string's first character uppercase.
 *
 * @param {String} string
 * @return {String}
 */
export function ucfirst(string) {
  return string.substr(0, 1).toUpperCase() + string.substr(1);
}
