import Component from 'flarum/Component';

/**
 * The `Placeholder` component displays a muted text with some call to action,
 * usually used as an empty state.
 *
 * ### Props
 *
 * - `text`
 */
export default class Placeholder extends Component {
  view() {
    return (
      <div className="Placeholder">
        <p>{this.props.text}</p>
      </div>
    );
  }
}
