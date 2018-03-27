import Post from 'flarum/components/Post';
import { ucfirst } from 'flarum/utils/string';
import usernameHelper from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';

/**
 * The `EventPost` component displays a post which indicating a discussion
 * event, like a discussion being renamed or stickied. Subclasses must implement
 * the `icon` and `description` methods.
 *
 * ### Props
 *
 * - All of the props for `Post`
 *
 * @abstract
 */
export default class EventPost extends Post {
  attrs() {
    const attrs = super.attrs();

    attrs.className += ' EventPost ' + ucfirst(this.props.post.contentType()) + 'Post';

    return attrs;
  }

  content() {
    const user = this.props.post.user();
    const username = usernameHelper(user);
    const data = Object.assign(this.descriptionData(), {
      user,
      username: user
        ? <a className="EventPost-user" href={app.route.user(user)} config={m.route}>{username}</a>
        : username
    });

    return super.content().concat([
      icon(this.icon(), {className: 'EventPost-icon'}),
      <div class="EventPost-info">
        {this.description(data)}
      </div>
    ]);
  }

  /**
   * Get the name of the event icon.
   *
   * @return {String}
   */
  icon() {
    return '';
  }

  /**
   * Get the description text for the event.
   *
   * @param {Object} data
   * @return {String|Object} The description to render in the DOM
   */
  description(data) {
    return app.translator.transChoice(this.descriptionKey(), data.count, data);
  }

  /**
   * Get the translation key for the description of the event.
   *
   * @return {String}
   */
  descriptionKey() {
    return '';
  }

  /**
   * Get the translation data for the description of the event.
   *
   * @return {Object}
   */
  descriptionData() {
    return {};
  }
}
