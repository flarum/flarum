/**
 * The `fullTime` helper displays a formatted time string wrapped in a <time>
 * tag.
 *
 * @param {Date} time
 * @return {Object}
 */
export default function fullTime(time) {
  const mo = moment(time);

  const datetime = mo.format();
  const full = mo.format('LLLL');

  return <time pubdate datetime={datetime}>{full}</time>;
}
