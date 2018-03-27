import Component from 'flarum/Component';
import Checkbox from 'flarum/components/Checkbox';
import icon from 'flarum/helpers/icon';
import ItemList from 'flarum/utils/ItemList';

/**
 * The `NotificationGrid` component displays a table of notification types and
 * methods, allowing the user to toggle each combination.
 *
 * ### Props
 *
 * - `user`
 */
export default class NotificationGrid extends Component {
  init() {
    /**
     * Information about the available notification methods.
     *
     * @type {Array}
     */
    this.methods = [
      {name: 'alert', icon: 'bell', label: app.translator.trans('core.forum.settings.notify_by_web_heading')},
      {name: 'email', icon: 'envelope-o', label: app.translator.trans('core.forum.settings.notify_by_email_heading')}
    ];

    /**
     * A map of notification type-method combinations to the checkbox instances
     * that represent them.
     *
     * @type {Object}
     */
    this.inputs = {};

    /**
     * Information about the available notification types.
     *
     * @type {Object}
     */
    this.types = this.notificationTypes().toArray();

    // For each of the notification type-method combinations, create and store a
    // new checkbox component instance, which we will render in the view.
    this.types.forEach(type => {
      this.methods.forEach(method => {
        const key = this.preferenceKey(type.name, method.name);
        const preference = this.props.user.preferences()[key];

        this.inputs[key] = new Checkbox({
          state: !!preference,
          disabled: typeof preference === 'undefined',
          onchange: () => this.toggle([key])
        });
      });
    });
  }

  view() {
    return (
      <table className="NotificationGrid">
        <thead>
          <tr>
            <td/>
            {this.methods.map(method => (
              <th className="NotificationGrid-groupToggle" onclick={this.toggleMethod.bind(this, method.name)}>
                {icon(method.icon)} {method.label}
              </th>
            ))}
          </tr>
        </thead>

        <tbody>
          {this.types.map(type => (
            <tr>
              <td className="NotificationGrid-groupToggle" onclick={this.toggleType.bind(this, type.name)}>
                {icon(type.icon)} {type.label}
              </td>
              {this.methods.map(method => (
                <td className="NotificationGrid-checkbox">
                  {this.inputs[this.preferenceKey(type.name, method.name)].render()}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    this.$('thead .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function(e) {
      const i = parseInt($(this).index(), 10) + 1;
      $(this).parents('table').find('td:nth-child(' + i + ')').toggleClass('highlighted', e.type === 'mouseenter');
    });

    this.$('tbody .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function(e) {
      $(this).parent().find('td').toggleClass('highlighted', e.type === 'mouseenter');
    });
  }

  /**
   * Toggle the state of the given preferences, based on the value of the first
   * one.
   *
   * @param {Array} keys
   */
  toggle(keys) {
    const user = this.props.user;
    const preferences = user.preferences();
    const enabled = !preferences[keys[0]];

    keys.forEach(key => {
      const control = this.inputs[key];

      control.loading = true;
      preferences[key] = control.props.state = enabled;
    });

    m.redraw();

    user.save({preferences}).then(() => {
      keys.forEach(key => this.inputs[key].loading = false);

      m.redraw();
    });
  }

  /**
   * Toggle all notification types for the given method.
   *
   * @param {String} method
   */
  toggleMethod(method) {
    const keys = this.types
      .map(type => this.preferenceKey(type.name, method))
      .filter(key => !this.inputs[key].props.disabled);

    this.toggle(keys);
  }

  /**
   * Toggle all notification methods for the given type.
   *
   * @param {String} type
   */
  toggleType(type) {
    const keys = this.methods
      .map(method => this.preferenceKey(type, method.name))
      .filter(key => !this.inputs[key].props.disabled);

    this.toggle(keys);
  }

  /**
   * Get the name of the preference key for the given notification type-method
   * combination.
   *
   * @param {String} type
   * @param {String} method
   * @return {String}
   */
  preferenceKey(type, method) {
    return 'notify_' + type + '_' + method;
  }

  /**
   * Build an item list for the notification types to display in the grid.
   *
   * Each notification type is an object which has the following properties:
   *
   * - `name` The name of the notification type.
   * - `label` The label to display in the notification grid row.
   *
   * @return {ItemList}
   */
  notificationTypes() {
    const items = new ItemList();

    items.add('discussionRenamed', {
      name: 'discussionRenamed',
      icon: 'pencil',
      label: app.translator.trans('core.forum.settings.notify_discussion_renamed_label')
    });

    return items;
  }
}
