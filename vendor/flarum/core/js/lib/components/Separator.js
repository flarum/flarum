import Component from 'flarum/Component';

/**
 * The `Separator` component defines a menu separator item.
 */
class Separator extends Component {
  view() {
    return <li className="Dropdown-separator"/>;
  }
}

Separator.isListItem = true;

export default Separator;
