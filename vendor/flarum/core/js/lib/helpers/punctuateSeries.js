/**
 * The `punctuateSeries` helper formats a list of strings (e.g. names) to read
 * fluently in the application's locale.
 *
 * ```js
 * punctuateSeries(['Toby', 'Franz', 'Dominion']) // Toby, Franz, and Dominion
 * ```
 *
 * @param {Array} items
 * @return {VirtualElement}
 */
export default function punctuateSeries(items) {
  if (items.length === 2) {
    return app.translator.trans('core.lib.series.two_text', {
      first: items[0],
      second: items[1]
    });
  } else if (items.length >= 3) {
    // If there are three or more items, we will join all but the first and
    // last items with the equivalent of a comma, and then we will feed that
    // into the translator along with the first and last item.
    const second = items
      .slice(1, items.length - 1)
      .reduce((list, item) => list.concat([item, app.translator.trans('core.lib.series.glue_text')]), [])
      .slice(0, -1);

    return app.translator.trans('core.lib.series.three_text', {
      first: items[0],
      second,
      third: items[items.length - 1]
    });
  }

  return items;
}
