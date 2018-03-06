import Page from 'flarum/components/Page';
import ItemList from 'flarum/utils/ItemList';
import DiscussionHero from 'flarum/components/DiscussionHero';
import PostStream from 'flarum/components/PostStream';
import PostStreamScrubber from 'flarum/components/PostStreamScrubber';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import SplitDropdown from 'flarum/components/SplitDropdown';
import listItems from 'flarum/helpers/listItems';
import DiscussionControls from 'flarum/utils/DiscussionControls';

/**
 * The `DiscussionPage` component displays a whole discussion page, including
 * the discussion list pane, the hero, the posts, and the sidebar.
 */
export default class DiscussionPage extends Page {
  init() {
    super.init();

    /**
     * The discussion that is being viewed.
     *
     * @type {Discussion}
     */
    this.discussion = null;

    /**
     * The number of the first post that is currently visible in the viewport.
     *
     * @type {Integer}
     */
    this.near = null;

    this.refresh();

    // If the discussion list has been loaded, then we'll enable the pane (and
    // hide it by default). Also, if we've just come from another discussion
    // page, then we don't want Mithril to redraw the whole page – if it did,
    // then the pane would which would be slow and would cause problems with
    // event handlers.
    if (app.cache.discussionList) {
      app.pane.enable();
      app.pane.hide();

      if (app.previous instanceof DiscussionPage) {
        m.redraw.strategy('diff');
      }
    }

    app.history.push('discussion');

    this.bodyClass = 'App--discussion';
  }

  onunload(e) {
    // If we have routed to the same discussion as we were viewing previously,
    // cancel the unloading of this controller and instead prompt the post
    // stream to jump to the new 'near' param.
    if (this.discussion) {
      const idParam = m.route.param('id');

      if (idParam && idParam.split('-')[0] === this.discussion.id()) {
        e.preventDefault();

        const near = m.route.param('near') || '1';

        if (near !== String(this.near)) {
          this.stream.goToNumber(near);
        }

        this.near = null;
        return;
      }
    }

    // If we are indeed navigating away from this discussion, then disable the
    // discussion list pane. Also, if we're composing a reply to this
    // discussion, minimize the composer – unless it's empty, in which case
    // we'll just close it.
    app.pane.disable();

    if (app.composingReplyTo(this.discussion) && !app.composer.component.content()) {
      app.composer.hide();
    } else {
      app.composer.minimize();
    }
  }

  view() {
    const discussion = this.discussion;

    return (
      <div className="DiscussionPage">
        {app.cache.discussionList
          ? <div className="DiscussionPage-list" config={this.configPane.bind(this)}>
              {!$('.App-navigation').is(':visible') ? app.cache.discussionList.render() : ''}
            </div>
          : ''}

        <div className="DiscussionPage-discussion">
          {discussion
            ? [
              DiscussionHero.component({discussion}),
              <div className="container">
                <nav className="DiscussionPage-nav">
                  <ul>{listItems(this.sidebarItems().toArray())}</ul>
                </nav>
                <div className="DiscussionPage-stream">
                  {this.stream.render()}
                </div>
              </div>
            ]
            : LoadingIndicator.component({className: 'LoadingIndicator--block'})}
        </div>
      </div>
    );
  }

  /**
   * Clear and reload the discussion.
   */
  refresh() {
    this.near = m.route.param('near') || 0;
    this.discussion = null;

    const preloadedDiscussion = app.preloadedDocument();
    if (preloadedDiscussion) {
      // We must wrap this in a setTimeout because if we are mounting this
      // component for the first time on page load, then any calls to m.redraw
      // will be ineffective and thus any configs (scroll code) will be run
      // before stuff is drawn to the page.
      setTimeout(this.show.bind(this, preloadedDiscussion), 0);
    } else {
      const params = this.requestParams();

      app.store.find('discussions', m.route.param('id').split('-')[0], params)
        .then(this.show.bind(this));
    }

    m.lazyRedraw();
  }

  /**
   * Get the parameters that should be passed in the API request to get the
   * discussion.
   *
   * @return {Object}
   */
  requestParams() {
    return {
      page: {near: this.near}
    };
  }

  /**
   * Initialize the component to display the given discussion.
   *
   * @param {Discussion} discussion
   */
  show(discussion) {
    this.discussion = discussion;

    app.history.push('discussion', discussion.title());
    app.setTitle(discussion.title());
    app.setTitleCount(0);

    // When the API responds with a discussion, it will also include a number of
    // posts. Some of these posts are included because they are on the first
    // page of posts we want to display (determined by the `near` parameter) –
    // others may be included because due to other relationships introduced by
    // extensions. We need to distinguish the two so we don't end up displaying
    // the wrong posts. We do so by filtering out the posts that don't have
    // the 'discussion' relationship linked, then sorting and splicing.
    let includedPosts = [];
    if (discussion.payload && discussion.payload.included) {
      includedPosts = discussion.payload.included
        .filter(record => record.type === 'posts' && record.relationships && record.relationships.discussion)
        .map(record => app.store.getById('posts', record.id))
        .sort((a, b) => a.id() - b.id())
        .slice(0, 20);
    }

    // Set up the post stream for this discussion, along with the first page of
    // posts we want to display. Tell the stream to scroll down and highlight
    // the specific post that was routed to.
    this.stream = new PostStream({discussion, includedPosts});
    this.stream.on('positionChanged', this.positionChanged.bind(this));
    this.stream.goToNumber(m.route.param('near') || (includedPosts[0] && includedPosts[0].number()), true);
  }

  /**
   * Configure the discussion list pane.
   *
   * @param {DOMElement} element
   * @param {Boolean} isInitialized
   * @param {Object} context
   */
  configPane(element, isInitialized, context) {
    if (isInitialized) return;

    context.retain = true;

    const $list = $(element);

    // When the mouse enters and leaves the discussions pane, we want to show
    // and hide the pane respectively. We also create a 10px 'hot edge' on the
    // left of the screen to activate the pane.
    const pane = app.pane;
    $list.hover(pane.show.bind(pane), pane.onmouseleave.bind(pane));

    const hotEdge = e => {
      if (e.pageX < 10) pane.show();
    };
    $(document).on('mousemove', hotEdge);
    context.onunload = () => $(document).off('mousemove', hotEdge);

    // If the discussion we are viewing is listed in the discussion list, then
    // we will make sure it is visible in the viewport – if it is not we will
    // scroll the list down to it.
    const $discussion = $list.find('.DiscussionListItem.active');
    if ($discussion.length) {
      const listTop = $list.offset().top;
      const listBottom = listTop + $list.outerHeight();
      const discussionTop = $discussion.offset().top;
      const discussionBottom = discussionTop + $discussion.outerHeight();

      if (discussionTop < listTop || discussionBottom > listBottom) {
        $list.scrollTop($list.scrollTop() - listTop + discussionTop);
      }
    }
  }

  /**
   * Build an item list for the contents of the sidebar.
   *
   * @return {ItemList}
   */
  sidebarItems() {
    const items = new ItemList();

    items.add('controls',
      SplitDropdown.component({
        children: DiscussionControls.controls(this.discussion, this).toArray(),
        icon: 'ellipsis-v',
        className: 'App-primaryControl',
        buttonClassName: 'Button--primary'
      })
    );

    items.add('scrubber',
      PostStreamScrubber.component({
        stream: this.stream,
        className: 'App-titleControl'
      }),
      -100
    );

    return items;
  }

  /**
   * When the posts that are visible in the post stream change (i.e. the user
   * scrolls up or down), then we update the URL and mark the posts as read.
   *
   * @param {Integer} startNumber
   * @param {Integer} endNumber
   */
  positionChanged(startNumber, endNumber) {
    const discussion = this.discussion;

    // Construct a URL to this discussion with the updated position, then
    // replace it into the window's history and our own history stack.
    const url = app.route.discussion(discussion, this.near = startNumber);

    m.route(url, true);
    window.history.replaceState(null, document.title, url);

    app.history.push('discussion', discussion.title());

    // If the user hasn't read past here before, then we'll update their read
    // state and redraw.
    if (app.session.user && endNumber > (discussion.readNumber() || 0)) {
      discussion.save({readNumber: endNumber});
      m.redraw();
    }
  }
}
