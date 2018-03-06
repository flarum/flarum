import humanTimeUtil from 'flarum/utils/humanTime';

/**
 * The `humanTime` helper displays a time in a human-friendly time-ago format
 * (e.g. '12 days ago'), wrapped in a <time> tag with other information about
 * the time.
 *
 * @param {Date} time
 * @return {Object}
 */
export default function humanTime(time) {
  const mo = moment(time);

  const datetime = mo.format();
  const full = mo.format('LLLL');
  const ago = humanTimeUtil(time);

  return <time pubdate datetime={datetime} title={full} data-humantime>{ago}</time>;
}
