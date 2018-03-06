import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import extract from 'flarum/utils/extract';
import extractText from 'flarum/utils/extractText';
import LoadingIndicator from 'flarum/components/LoadingIndicator';

/**
 * The `Button` component defines an element which, when clicked, performs an
 * action. The button may have the following special props:
 *
 * - `icon` The name of the icon class. If specified, the button will be given a
 *   'has-icon' class name.
 * - `disabled` Whether or not the button is disabled. If truthy, the button
 *   will be given a 'disabled' class name, and any `onclick` handler will be
 *   removed.
 * - `loading` Whether or not the button should be in a disabled loading state.
 *
 * All other props will be assigned as attributes on the button element.
 *
 * Note that a Button has no default class names. This is because a Button can
 * be used to represent any generic clickable control, like a menu item.
 */
export default class Button extends Component {
  view() {
    const attrs = Object.assign({}, this.props);

    delete attrs.children;

    attrs.className = attrs.className || '';
    attrs.type = attrs.type || 'button';

    // If nothing else is provided, we use the textual button content as tooltip
    if (!attrs.title && this.props.children) {
      attrs.title = extractText(this.props.children);
    }

    const iconName = extract(attrs, 'icon');
    if (iconName) attrs.className += ' hasIcon';

    const loading = extract(attrs, 'loading');
    if (attrs.disabled || loading) {
      attrs.className += ' disabled' + (loading ? ' loading' : '');
      delete attrs.onclick;
    }

    return <button {...attrs}>{this.getButtonContent()}</button>;
  }

  /**
   * Get the template for the button's content.
   *
   * @return {*}
   * @protected
   */
  getButtonContent() {
    const iconName = this.props.icon;

    return [
      iconName && iconName !== true ? icon(iconName, {className: 'Button-icon'}) : '',
      this.props.children ? <span className="Button-label">{this.props.children}</span> : '',
      this.props.loading ? LoadingIndicator.component({size: 'tiny', className: 'LoadingIndicator--inline'}) : ''
    ];
  }
}
