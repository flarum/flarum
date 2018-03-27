import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';

export default class SubscriptionMenuItem extends Component {
  view() {
    return (
      <button className="SubscriptionMenuItem hasIcon" onclick={this.props.onclick}>
        {this.props.active ? icon('check', {className: 'Button-icon'}) : ''}
        <span className="SubscriptionMenuItem-label">
          {icon(this.props.icon, {className: 'Button-icon'})}
          <strong>{this.props.label}</strong>
          <span className="SubscriptionMenuItem-description">{this.props.description}</span>
        </span>
      </button>
    );
  }
}
