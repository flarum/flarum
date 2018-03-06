import Page from 'flarum/components/Page';
import Button from 'flarum/components/Button';
import Switch from 'flarum/components/Switch';
import EditCustomCssModal from 'flarum/components/EditCustomCssModal';
import EditCustomHeaderModal from 'flarum/components/EditCustomHeaderModal';
import UploadImageButton from 'flarum/components/UploadImageButton';
import saveSettings from 'flarum/utils/saveSettings';

export default class AppearancePage extends Page {
  init() {
    super.init();

    this.primaryColor = m.prop(app.data.settings.theme_primary_color);
    this.secondaryColor = m.prop(app.data.settings.theme_secondary_color);
    this.darkMode = m.prop(app.data.settings.theme_dark_mode === '1');
    this.coloredHeader = m.prop(app.data.settings.theme_colored_header === '1');
  }

  view() {
    return (
      <div className="AppearancePage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            <fieldset className="AppearancePage-colors">
              <legend>{app.translator.trans('core.admin.appearance.colors_heading')}</legend>
              <div className="helpText">
                {app.translator.trans('core.admin.appearance.colors_text')}
              </div>

              <div className="AppearancePage-colors-input">
                <input className="FormControl" type="color" placeholder="#aaaaaa" value={this.primaryColor()} onchange={m.withAttr('value', this.primaryColor)}/>
                <input className="FormControl" type="color" placeholder="#aaaaaa" value={this.secondaryColor()} onchange={m.withAttr('value', this.secondaryColor)}/>
              </div>

              {Switch.component({
                state: this.darkMode(),
                children: app.translator.trans('core.admin.appearance.dark_mode_label'),
                onchange: this.darkMode
              })}

              {Switch.component({
                state: this.coloredHeader(),
                children: app.translator.trans('core.admin.appearance.colored_header_label'),
                onchange: this.coloredHeader
              })}

              {Button.component({
                className: 'Button Button--primary',
                type: 'submit',
                children: app.translator.trans('core.admin.appearance.submit_button'),
                loading: this.loading
              })}
            </fieldset>
          </form>

          <fieldset>
            <legend>{app.translator.trans('core.admin.appearance.logo_heading')}</legend>
            <div className="helpText">
              {app.translator.trans('core.admin.appearance.logo_text')}
            </div>
            <UploadImageButton name="logo"/>
          </fieldset>

          <fieldset>
            <legend>{app.translator.trans('core.admin.appearance.favicon_heading')}</legend>
            <div className="helpText">
              {app.translator.trans('core.admin.appearance.favicon_text')}
            </div>
            <UploadImageButton name="favicon"/>
          </fieldset>

          <fieldset>
            <legend>{app.translator.trans('core.admin.appearance.custom_header_heading')}</legend>
            <div className="helpText">
              {app.translator.trans('core.admin.appearance.custom_header_text')}
            </div>
            {Button.component({
              className: 'Button',
              children: app.translator.trans('core.admin.appearance.edit_header_button'),
              onclick: () => app.modal.show(new EditCustomHeaderModal())
            })}
          </fieldset>

          <fieldset>
            <legend>{app.translator.trans('core.admin.appearance.custom_styles_heading')}</legend>
            <div className="helpText">
              {app.translator.trans('core.admin.appearance.custom_styles_text')}
            </div>
            {Button.component({
              className: 'Button',
              children: app.translator.trans('core.admin.appearance.edit_css_button'),
              onclick: () => app.modal.show(new EditCustomCssModal())
            })}
          </fieldset>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    const hex = /^#[0-9a-f]{3}([0-9a-f]{3})?$/i;

    if (!hex.test(this.primaryColor()) || !hex.test(this.secondaryColor())) {
      alert(app.translator.trans('core.admin.appearance.enter_hex_message'));
      return;
    }

    this.loading = true;

    saveSettings({
      theme_primary_color: this.primaryColor(),
      theme_secondary_color: this.secondaryColor(),
      theme_dark_mode: this.darkMode(),
      theme_colored_header: this.coloredHeader()
    }).then(() => window.location.reload());
  }
}
