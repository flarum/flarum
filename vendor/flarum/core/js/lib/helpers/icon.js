/**
 * The `icon` helper displays a FontAwesome icon. The fa-fw class is applied.
 *
 * @param {String} name The name of the icon class, without the `fa-` prefix.
 * @param {Object} attrs Any other attributes to apply.
 * @return {Object}
 */
export default function icon(name, attrs = {}) {
  attrs.className = 'icon fa fa-' + name + ' ' + (attrs.className || '');

  return <i {...attrs}/>;
}
