import Page from 'flarum/components/Page';
import FieldSet from 'flarum/components/FieldSet';
import Select from 'flarum/components/Select';
import Button from 'flarum/components/Button';
import Alert from 'flarum/components/Alert';
import saveSettings from 'flarum/utils/saveSettings';
import ItemList from 'flarum/utils/ItemList';
import Switch from 'flarum/components/Switch';

export default class BasicsPage extends Page {
  init() {
    super.init();

    this.loading = false;

    this.fields = [
      'forum_title',
      'forum_description',
      'default_locale',
      'show_language_selector',
      'default_route',
      'welcome_title',
      'welcome_message'
    ];
    this.values = {};

    const settings = app.data.settings;
    this.fields.forEach(key => this.values[key] = m.prop(settings[key] || false));

    this.localeOptions = {};
    const locales = app.data.locales;
    for (const i in locales) {
      this.localeOptions[i] = `${locales[i]} (${i})`;
    }

    if (typeof this.values.show_language_selector() !== "number") this.values.show_language_selector(1);
  }

  view() {
    return (
      <div className="BasicsPage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            {FieldSet.component({
              label: app.translator.trans('core.admin.basics.forum_title_heading'),
              children: [
                <input className="FormControl" value={this.values.forum_title()} oninput={m.withAttr('value', this.values.forum_title)}/>
              ]
            })}

            {FieldSet.component({
              label: app.translator.trans('core.admin.basics.forum_description_heading'),
              children: [
                <div className="helpText">
                  {app.translator.trans('core.admin.basics.forum_description_text')}
                </div>,
                <textarea className="FormControl" value={this.values.forum_description()} oninput={m.withAttr('value', this.values.forum_description)}/>
              ]
            })}

            {Object.keys(this.localeOptions).length > 1
              ? FieldSet.component({
                label: app.translator.trans('core.admin.basics.default_language_heading'),
                children: [
                  Select.component({
                    options: this.localeOptions,
                    value: this.values.default_locale(),
                    onchange: this.values.default_locale
                  }),
                  Switch.component({
                    state: this.values.show_language_selector(),
                    onchange: this.values.show_language_selector,
                    children: app.translator.trans('core.admin.basics.show_language_selector_label'),
                  })
                ]
              })
              : ''}

            {FieldSet.component({
              label: app.translator.trans('core.admin.basics.home_page_heading'),
              className: 'BasicsPage-homePage',
              children: [
                <div className="helpText">
                  {app.translator.trans('core.admin.basics.home_page_text')}
                </div>,
                this.homePageItems().toArray().map(({path, label}) =>
                  <label className="checkbox">
                    <input type="radio" name="homePage" value={path} checked={this.values.default_route() === path} onclick={m.withAttr('value', this.values.default_route)}/>
                    {label}
                  </label>
                )
              ]
            })}

            {FieldSet.component({
              label: app.translator.trans('core.admin.basics.welcome_banner_heading'),
              className: 'BasicsPage-welcomeBanner',
              children: [
                <div className="helpText">
                  {app.translator.trans('core.admin.basics.welcome_banner_text')}
                </div>,
                <div className="BasicsPage-welcomeBanner-input">
                  <input className="FormControl" value={this.values.welcome_title()} oninput={m.withAttr('value', this.values.welcome_title)}/>
                  <textarea className="FormControl" value={this.values.welcome_message()} oninput={m.withAttr('value', this.values.welcome_message)}/>
                </div>
              ]
            })}

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: app.translator.trans('core.admin.basics.submit_button'),
              loading: this.loading,
              disabled: !this.changed()
            })}
          </form>
        </div>
      </div>
    );
  }

  changed() {
    return this.fields.some(key => this.values[key]() !== app.data.settings[key]);
  }

  /**
   * Build a list of options for the default homepage. Each option must be an
   * object with `path` and `label` properties.
   *
   * @return {ItemList}
   * @public
   */
  homePageItems() {
    const items = new ItemList();

    items.add('allDiscussions', {
      path: '/all',
      label: app.translator.trans('core.admin.basics.all_discussions_label')
    });

    return items;
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.loading) return;

    this.loading = true;
    app.alerts.dismiss(this.successAlert);

    const settings = {};

    this.fields.forEach(key => settings[key] = this.values[key]());

    saveSettings(settings)
      .then(() => {
        app.alerts.show(this.successAlert = new Alert({type: 'success', children: app.translator.trans('core.admin.basics.saved_message')}));
      })
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
