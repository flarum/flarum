import SettingsModal from 'flarum/components/SettingsModal';

export default class FlagsSettingsModal extends SettingsModal {
  className() {
    return 'FlagsSettingsModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-flags.admin.settings.title');
  }

  form() {
    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-flags.admin.settings.guidelines_url_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-flags.guidelines_url')}/>
      </div>
    ];
  }
}
