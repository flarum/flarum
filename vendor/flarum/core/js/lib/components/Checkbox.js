import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import icon from 'flarum/helpers/icon';

/**
 * The `Checkbox` component defines a checkbox input.
 *
 * ### Props
 *
 * - `state` Whether or not the checkbox is checked.
 * - `className` The class name for the root element.
 * - `disabled` Whether or not the checkbox is disabled.
 * - `onchange` A callback to run when the checkbox is checked/unchecked.
 * - `children` A text label to display next to the checkbox.
 */
export default class Checkbox extends Component {
  init() {
    /**
     * Whether or not the checkbox's value is in the process of being saved.
     *
     * @type {Boolean}
     * @public
     */
    this.loading = false;
  }

  view() {
    let className = 'Checkbox ' + (this.props.state ? 'on' : 'off') + ' ' + (this.props.className || '');
    if (this.loading) className += ' loading';
    if (this.props.disabled) className += ' disabled';

    return (
      <label className={className}>
        <input type="checkbox"
          checked={this.props.state}
          disabled={this.props.disabled}
          onchange={m.withAttr('checked', this.onchange.bind(this))}/>
        <div className="Checkbox-display">
          {this.getDisplay()}
        </div>
        {this.props.children}
      </label>
    );
  }

  /**
   * Get the template for the checkbox's display (tick/cross icon).
   *
   * @return {*}
   * @protected
   */
  getDisplay() {
    return this.loading
      ? LoadingIndicator.component({size: 'tiny'})
      : icon(this.props.state ? 'check' : 'times');
  }

  /**
   * Run a callback when the state of the checkbox is changed.
   *
   * @param {Boolean} checked
   * @protected
   */
  onchange(checked) {
    if (this.props.onchange) this.props.onchange(checked, this);
  }
}
