import Dropdown from 'flarum/components/Dropdown';
import icon from 'flarum/helpers/icon';

/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 *
 * ### Props
 *
 * - `caretIcon`
 * - `defaultLabel`
 */
export default class SelectDropdown extends Dropdown {
  static initProps(props) {
    props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'sort';

    super.initProps(props);

    props.className += ' Dropdown--select';
  }

  getButtonContent() {
    const activeChild = this.props.children.filter(child => child.props.active)[0];
    let label = activeChild && activeChild.props.children || this.props.defaultLabel;

    if (label instanceof Array) label = label[0];

    return [
      <span className="Button-label">{label}</span>,
      icon(this.props.caretIcon, {className: 'Button-caret'})
    ];
  }
}
