import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';

/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling. It accepts the following props:
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 */
export default class Select extends Component {
  view() {
    const {options, onchange, value} = this.props;

    return (
      <span className="Select">
        <select className="Select-input FormControl" onchange={onchange ? m.withAttr('value', onchange.bind(this)) : undefined} value={value}>
          {Object.keys(options).map(key => <option value={key}>{options[key]}</option>)}
        </select>
        {icon('sort', {className: 'Select-caret'})}
      </span>
    );
  }
}
