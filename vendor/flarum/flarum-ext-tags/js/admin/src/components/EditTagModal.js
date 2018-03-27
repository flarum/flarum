import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import { slug } from 'flarum/utils/string';

import tagLabel from 'flarum/tags/helpers/tagLabel';

/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends Modal {
  init() {
    super.init();

    this.tag = this.props.tag || app.store.createRecord('tags');

    this.name = m.prop(this.tag.name() || '');
    this.slug = m.prop(this.tag.slug() || '');
    this.description = m.prop(this.tag.description() || '');
    this.color = m.prop(this.tag.color() || '');
    this.isHidden = m.prop(this.tag.isHidden() || false);
  }

  className() {
    return 'EditTagModal Modal--small';
  }

  title() {
    return this.name()
      ? tagLabel({
        name: this.name,
        color: this.color
      })
      : app.translator.trans('flarum-tags.admin.edit_tag.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('flarum-tags.admin.edit_tag.name_label')}</label>
            <input className="FormControl" placeholder={app.translator.trans('flarum-tags.admin.edit_tag.name_placeholder')} value={this.name()} oninput={e => {
              this.name(e.target.value);
              this.slug(slug(e.target.value));
            }}/>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-tags.admin.edit_tag.slug_label')}</label>
            <input className="FormControl" value={this.slug()} oninput={m.withAttr('value', this.slug)}/>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-tags.admin.edit_tag.description_label')}</label>
            <textarea className="FormControl" value={this.description()} oninput={m.withAttr('value', this.description)}/>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('flarum-tags.admin.edit_tag.color_label')}</label>
            <input className="FormControl" placeholder="#aaaaaa" value={this.color()} oninput={m.withAttr('value', this.color)}/>
          </div>

          <div className="Form-group">
            <div>
              <label className="checkbox">
                <input type="checkbox" value="1" checked={this.isHidden()} onchange={m.withAttr('checked', this.isHidden)}/>
                {app.translator.trans('flarum-tags.admin.edit_tag.hide_label')}
              </label>
            </div>
          </div>

          <div className="Form-group">
            {Button.component({
              type: 'submit',
              className: 'Button Button--primary EditTagModal-save',
              loading: this.loading,
              children: app.translator.trans('flarum-tags.admin.edit_tag.submit_button')
            })}
            {this.tag.exists ? (
              <button type="button" className="Button EditTagModal-delete" onclick={this.delete.bind(this)}>
                {app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_button')}
              </button>
            ) : ''}
          </div>
        </div>
      </div>
    );
  }

  submitData() {
    return {
      name: this.name(),
      slug: this.slug(),
      description: this.description(),
      color: this.color(),
      isHidden: this.isHidden()
    };
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    this.tag.save(this.submitData()).then(
      () => this.hide(),
      response => {
        this.loading = false;
        this.handleErrors(response);
      }
    );
  }

  delete() {
    if (confirm(app.translator.trans('flarum-tags.admin.edit_tag.delete_tag_confirmation'))) {
      const children = app.store.all('tags').filter(tag => tag.parent() === this.tag);

      this.tag.delete().then(() => {
        children.forEach(tag => tag.pushData({
          attributes: {isChild: false},
          relationships: {parent: null}
        }));
        m.redraw();
      });

      this.hide();
    }
  }
}
