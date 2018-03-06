import Component from 'flarum/Component';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import Placeholder from 'flarum/components/Placeholder';

/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Props
 *
 * - `params` A map of parameters used to construct a refined parameter object
 *   to send along in the API request to get discussion results.
 */
export default class DiscussionList extends Component {
  init() {
    /**
     * Whether or not discussion results are loading.
     *
     * @type {Boolean}
     */
    this.loading = true;

    /**
     * Whether or not there are more results that can be loaded.
     *
     * @type {Boolean}
     */
    this.moreResults = false;

    /**
     * The discussions in the discussion list.
     *
     * @type {Discussion[]}
     */
    this.discussions = [];

    this.refresh();
  }

  view() {
    const params = this.props.params;
    let loading;

    if (this.loading) {
      loading = LoadingIndicator.component();
    } else if (this.moreResults) {
      loading = Button.component({
        children: app.translator.trans('core.forum.discussion_list.load_more_button'),
        className: 'Button',
        onclick: this.loadMore.bind(this)
      });
    }

    if (this.discussions.length === 0 && !this.loading) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return (
        <div className="DiscussionList">
          {Placeholder.component({text})}
        </div>
      );
    }

    return (
      <div className="DiscussionList">
        <ul className="DiscussionList-discussions">
          {this.discussions.map(discussion => {
            return (
              <li key={discussion.id()} data-id={discussion.id()}>
                {DiscussionListItem.component({discussion, params})}
              </li>
            );
          })}
        </ul>
        <div className="DiscussionList-loadMore">
          {loading}
        </div>
      </div>
    );
  }

  /**
   * Get the parameters that should be passed in the API request to get
   * discussion results.
   *
   * @return {Object}
   * @api
   */
  requestParams() {
    const params = {include: ['startUser', 'lastUser'], filter: {}};

    params.sort = this.sortMap()[this.props.params.sort];

    if (this.props.params.q) {
      params.filter.q = this.props.params.q;

      params.include.push('relevantPosts', 'relevantPosts.discussion', 'relevantPosts.user');
    }

    return params;
  }

  /**
   * Get a map of sort keys (which appear in the URL, and are used for
   * translation) to the API sort value that they represent.
   *
   * @return {Object}
   */
  sortMap() {
    const map = {};

    if (this.props.params.q) {
      map.relevance = '';
    }
    map.latest = '-lastTime';
    map.top = '-commentsCount';
    map.newest = '-startTime';
    map.oldest = 'startTime';

    return map;
  }

  /**
   * Clear and reload the discussion list.
   *
   * @public
   */
  refresh(clear = true) {
    if (clear) {
      this.loading = true;
      this.discussions = [];
    }

    return this.loadResults().then(
      results => {
        this.discussions = [];
        this.parseResults(results);
      },
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }

  /**
   * Load a new page of discussion results.
   *
   * @param {Integer} offset The index to start the page at.
   * @return {Promise}
   */
  loadResults(offset) {
    const preloadedDiscussions = app.preloadedDocument();

    if (preloadedDiscussions) {
      return m.deferred().resolve(preloadedDiscussions).promise;
    }

    const params = this.requestParams();
    params.page = {offset};
    params.include = params.include.join(',');

    return app.store.find('discussions', params);
  }

  /**
   * Load the next page of discussion results.
   *
   * @public
   */
  loadMore() {
    this.loading = true;

    this.loadResults(this.discussions.length)
      .then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the discussion list.
   *
   * @param {Discussion[]} results
   * @return {Discussion[]}
   */
  parseResults(results) {
    [].push.apply(this.discussions, results);

    this.loading = false;
    this.moreResults = !!results.payload.links.next;

    m.lazyRedraw();

    return results;
  }

  /**
   * Remove a discussion from the list if it is present.
   *
   * @param {Discussion} discussion
   * @public
   */
  removeDiscussion(discussion) {
    const index = this.discussions.indexOf(discussion);

    if (index !== -1) {
      this.discussions.splice(index, 1);
    }
  }

  /**
   * Add a discussion to the top of the list.
   *
   * @param {Discussion} discussion
   * @public
   */
  addDiscussion(discussion) {
    this.discussions.unshift(discussion);
  }
}
