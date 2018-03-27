import PostsUserPage from 'flarum/components/PostsUserPage';

/**
 * The `MentionsUserPage` component shows post which user Mentioned at
 */
export default class MentionsUserPage extends PostsUserPage {
  /**
   * Load a new page of the user's activity feed.
   *
   * @param {Integer} [offset] The position to start getting results from.
   * @return {Promise}
   * @protected
   */
  loadResults(offset) {
    return app.store.find('posts', {
      filter: {
        type: 'comment',
        mentioned: this.user.id()
      },
      page: {offset, limit: this.loadLimit},
      sort: '-time'
    });
  }
}
