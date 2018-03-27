/**
 * The `avatar` helper displays a user's avatar.
 *
 * @param {User} user
 * @param {Object} attrs Attributes to apply to the avatar element
 * @return {Object}
 */
export default function avatar(user, attrs = {}) {
  attrs.className = 'Avatar ' + (attrs.className || '');
  let content = '';

  // If the `title` attribute is set to null or false, we don't want to give the
  // avatar a title. On the other hand, if it hasn't been given at all, we can
  // safely default it to the user's username.
  const hasTitle = attrs.title === 'undefined' || attrs.title;
  if (!hasTitle) delete attrs.title;

  // If a user has been passed, then we will set up an avatar using their
  // uploaded image, or the first letter of their username if they haven't
  // uploaded one.
  if (user) {
    const username = user.username() || '?';
    const avatarUrl = user.avatarUrl();

    if (hasTitle) attrs.title = attrs.title || username;

    if (avatarUrl) {
      return <img {...attrs} src={avatarUrl}/>;
    }

    content = username.charAt(0).toUpperCase();
    attrs.style = {background: user.color()};
  }

  return <span {...attrs}>{content}</span>;
}
