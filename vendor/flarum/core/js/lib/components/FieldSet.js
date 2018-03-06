import Component from 'flarum/Component';
import listItems from 'flarum/helpers/listItems';

/**
 * The `FieldSet` component defines a collection of fields, displayed in a list
 * underneath a title. Accepted properties are:
 *
 * - `className` The class name for the fieldset.
 * - `label` The title of this group of fields.
 *
 * The children should be an array of items to show in the fieldset.
 */
export default class FieldSet extends Component {
  view() {
    return (
      <fieldset className={this.props.className}>
        <legend>{this.props.label}</legend>
        <ul>{listItems(this.props.children)}</ul>
      </fieldset>
    );
  }
}
