import SettingsModal from 'flarum/components/SettingsModal';

export default class EditCustomHeaderModal extends SettingsModal {
  className() {
    return 'EditCustomHeaderModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.edit_header.title');
  }

  form() {
    return [
      <p>{app.translator.trans('core.admin.edit_header.customize_text')}</p>,
      <div className="Form-group">
        <textarea className="FormControl" rows="30" bidi={this.setting('custom_header')}/>
      </div>
    ];
  }

  onsaved() {
    window.location.reload();
  }
}
