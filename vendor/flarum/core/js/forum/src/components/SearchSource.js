/**
 * The `SearchSource` interface defines a section of search results in the
 * search dropdown.
 *
 * Search sources should be registered with the `Search` component instance
 * (app.search) by extending the `sourceItems` method. When the user types a
 * query, each search source will be prompted to load search results via the
 * `search` method. When the dropdown is redrawn, it will be constructed by
 * putting together the output from the `view` method of each source.
 *
 * @interface
 */
export default class SearchSource {
  /**
   * Make a request to get results for the given query.
   *
   * @param {String} query
   * @return {Promise}
   */
  search() {
  }

  /**
   * Get an array of virtual <li>s that list the search results for the given
   * query.
   *
   * @param {String} query
   * @return {Object}
   */
  view() {
  }
}
