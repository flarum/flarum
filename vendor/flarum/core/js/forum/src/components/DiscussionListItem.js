import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import listItems from 'flarum/helpers/listItems';
import highlight from 'flarum/helpers/highlight';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/humanTime';
import ItemList from 'flarum/utils/ItemList';
import abbreviateNumber from 'flarum/utils/abbreviateNumber';
import Dropdown from 'flarum/components/Dropdown';
import TerminalPost from 'flarum/components/TerminalPost';
import PostPreview from 'flarum/components/PostPreview';
import SubtreeRetainer from 'flarum/utils/SubtreeRetainer';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import slidable from 'flarum/utils/slidable';
import extractText from 'flarum/utils/extractText';
import classList from 'flarum/utils/classList';

/**
 * The `DiscussionListItem` component shows a single discussion in the
 * discussion list.
 *
 * ### Props
 *
 * - `discussion`
 * - `params`
 */
export default class DiscussionListItem extends Component {
  init() {
    /**
     * Set up a subtree retainer so that the discussion will not be redrawn
     * unless new data comes in.
     *
     * @type {SubtreeRetainer}
     */
    this.subtree = new SubtreeRetainer(
      () => this.props.discussion.freshness,
      () => {
        const time = app.session.user && app.session.user.readTime();
        return time && time.getTime();
      },
      () => this.active()
    );
  }

  attrs() {
    return {
      className: classList([
        'DiscussionListItem',
        this.active() ? 'active' : '',
        this.props.discussion.isHidden() ? 'DiscussionListItem--hidden' : ''
      ])
    };
  }

  view() {
    const retain = this.subtree.retain();

    if (retain) return retain;

    const discussion = this.props.discussion;
    const startUser = discussion.startUser();
    const isUnread = discussion.isUnread();
    const isRead = discussion.isRead();
    const showUnread = !this.showRepliesCount() && isUnread;
    const jumpTo = Math.min(discussion.lastPostNumber(), (discussion.readNumber() || 0) + 1);
    const relevantPosts = this.props.params.q ? discussion.relevantPosts() : [];
    const controls = DiscussionControls.controls(discussion, this).toArray();
    const attrs = this.attrs();

    return (
      <div {...attrs}>

        {controls.length ? Dropdown.component({
          icon: 'ellipsis-v',
          children: controls,
          className: 'DiscussionListItem-controls',
          buttonClassName: 'Button Button--icon Button--flat Slidable-underneath Slidable-underneath--right'
        }) : ''}

        <a className={'Slidable-underneath Slidable-underneath--left Slidable-underneath--elastic' + (isUnread ? '' : ' disabled')}
          onclick={this.markAsRead.bind(this)}>
          {icon('check')}
        </a>

        <div className={'DiscussionListItem-content Slidable-content' + (isUnread ? ' unread' : '') + (isRead ? ' read' : '')}>
          <a href={startUser ? app.route.user(startUser) : '#'}
            className="DiscussionListItem-author"
            title={extractText(app.translator.trans('core.forum.discussion_list.started_text', {user: startUser, ago: humanTime(discussion.startTime())}))}
            config={function(element) {
              $(element).tooltip({placement: 'right'});
              m.route.apply(this, arguments);
            }}>
            {avatar(startUser, {title: ''})}
          </a>

          <ul className="DiscussionListItem-badges badges">
            {listItems(discussion.badges().toArray())}
          </ul>

          <a href={app.route.discussion(discussion, jumpTo)}
            config={m.route}
            className="DiscussionListItem-main">
            <h3 className="DiscussionListItem-title">{highlight(discussion.title(), this.props.params.q)}</h3>
            <ul className="DiscussionListItem-info">{listItems(this.infoItems().toArray())}</ul>
          </a>

          <span className="DiscussionListItem-count"
            onclick={this.markAsRead.bind(this)}
            title={showUnread ? app.translator.trans('core.forum.discussion_list.mark_as_read_tooltip') : ''}>
            {abbreviateNumber(discussion[showUnread ? 'unreadCount' : 'repliesCount']())}
          </span>

          {relevantPosts && relevantPosts.length
            ? <div className="DiscussionListItem-relevantPosts">
                {relevantPosts.map(post => PostPreview.component({post, highlight: this.props.params.q}))}
              </div>
            : ''}

        </div>
      </div>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    // If we're on a touch device, set up the discussion row to be slidable.
    // This allows the user to drag the row to either side of the screen to
    // reveal controls.
    if ('ontouchstart' in window) {
      const slidableInstance = slidable(this.$().addClass('Slidable'));

      this.$('.DiscussionListItem-controls')
        .on('hidden.bs.dropdown', () => slidableInstance.reset());
    }
  }

  /**
   * Determine whether or not the discussion is currently being viewed.
   *
   * @return {Boolean}
   */
  active() {
    const idParam = m.route.param('id');

    return idParam && idParam.split('-')[0] === this.props.discussion.id();
  }

  /**
   * Determine whether or not information about who started the discussion
   * should be displayed instead of information about the most recent reply to
   * the discussion.
   *
   * @return {Boolean}
   */
  showStartPost() {
    return ['newest', 'oldest'].indexOf(this.props.params.sort) !== -1;
  }

  /**
   * Determine whether or not the number of replies should be shown instead of
   * the number of unread posts.
   *
   * @return {Boolean}
   */
  showRepliesCount() {
    return this.props.params.sort === 'replies';
  }

  /**
   * Mark the discussion as read.
   */
  markAsRead() {
    const discussion = this.props.discussion;

    if (discussion.isUnread()) {
      discussion.save({readNumber: discussion.lastPostNumber()});
      m.redraw();
    }
  }

  /**
   * Build an item list of info for a discussion listing. By default this is
   * just the first/last post indicator.
   *
   * @return {ItemList}
   */
  infoItems() {
    const items = new ItemList();

    items.add('terminalPost',
      TerminalPost.component({
        discussion: this.props.discussion,
        lastPost: !this.showStartPost()
      })
    );

    return items;
  }
}
