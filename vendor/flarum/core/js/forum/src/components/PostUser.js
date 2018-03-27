import Component from 'flarum/Component';
import UserCard from 'flarum/components/UserCard';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import userOnline from 'flarum/helpers/userOnline';
import listItems from 'flarum/helpers/listItems';

/**
 * The `PostUser` component shows the avatar and username of a post's author.
 *
 * ### Props
 *
 * - `post`
 */
export default class PostUser extends Component {
  init() {
    /**
     * Whether or not the user hover card is visible.
     *
     * @type {Boolean}
     */
    this.cardVisible = false;
  }

  view() {
    const post = this.props.post;
    const user = post.user();

    if (!user) {
      return (
        <div className="PostUser">
          <h3>{avatar(user, {className: 'PostUser-avatar'})} {username(user)}</h3>
        </div>
      );
    }

    let card = '';

    if (!post.isHidden() && this.cardVisible) {
      card = UserCard.component({
        user,
        className: 'UserCard--popover',
        controlsButtonClassName: 'Button Button--icon Button--flat'
      });
    }

    return (
      <div className="PostUser">
        {userOnline(user)}
        <h3>
          <a href={app.route.user(user)} config={m.route}>
            {avatar(user, {className: 'PostUser-avatar'})}{' '}{username(user)}
          </a>
        </h3>
        <ul className="PostUser-badges badges">
          {listItems(user.badges().toArray())}
        </ul>
        {card}
      </div>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    let timeout;

    this.$()
      .on('mouseover', 'h3 a, .UserCard', () => {
        clearTimeout(timeout);
        timeout = setTimeout(this.showCard.bind(this), 500);
      })
      .on('mouseout', 'h3 a, .UserCard', () => {
        clearTimeout(timeout);
        timeout = setTimeout(this.hideCard.bind(this), 250);
      });
  }

  /**
   * Show the user card.
   */
  showCard() {
    this.cardVisible = true;

    m.redraw();

    setTimeout(() => this.$('.UserCard').addClass('in'));
  }

  /**
   * Hide the user card.
   */
  hideCard() {
    this.$('.UserCard').removeClass('in')
      .one('transitionend webkitTransitionEnd oTransitionEnd', () => {
        this.cardVisible = false;
        m.redraw();
      });
  }
}
