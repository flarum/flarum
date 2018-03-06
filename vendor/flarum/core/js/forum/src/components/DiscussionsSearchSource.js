import highlight from 'flarum/helpers/highlight';
import LinkButton from 'flarum/components/LinkButton';

/**
 * The `DiscussionsSearchSource` finds and displays discussion search results in
 * the search dropdown.
 *
 * @implements SearchSource
 */
export default class DiscussionsSearchSource {
  constructor() {
    this.results = {};
  }

  search(query) {
    query = query.toLowerCase();

    this.results[query] = [];

    const params = {
      filter: {q: query},
      page: {limit: 3},
      include: 'relevantPosts,relevantPosts.discussion,relevantPosts.user'
    };

    return app.store.find('discussions', params).then(results => this.results[query] = results);
  }

  view(query) {
    query = query.toLowerCase();

    const results = this.results[query] || [];

    return [
      <li className="Dropdown-header">{app.translator.trans('core.forum.search.discussions_heading')}</li>,
      <li>
        {LinkButton.component({
          icon: 'search',
          children: app.translator.trans('core.forum.search.all_discussions_button', {query}),
          href: app.route('index', {q: query})
        })}
      </li>,
      results.map(discussion => {
        const relevantPosts = discussion.relevantPosts();
        const post = relevantPosts && relevantPosts[0];

        return (
          <li className="DiscussionSearchResult" data-index={'discussions' + discussion.id()}>
            <a href={app.route.discussion(discussion, post && post.number())} config={m.route}>
              <div className="DiscussionSearchResult-title">{highlight(discussion.title(), query)}</div>
              {post ? <div className="DiscussionSearchResult-excerpt">{highlight(post.contentPlain(), query, 100)}</div> : ''}
            </a>
          </li>
        );
      })
    ];
  }
}
