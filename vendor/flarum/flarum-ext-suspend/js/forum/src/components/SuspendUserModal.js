import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

export default class SuspendUserModal extends Modal {
  init() {
    super.init();

    let until = this.props.user.suspendUntil();
    let status = null;

    if (new Date() > until) until = null;

    if (until) {
      if (until.getFullYear() === 9999) status = 'indefinitely';
      else status = 'limited';
    }

    this.status = m.prop(status);
    this.daysRemaining = m.prop(status === 'limited' && -moment().diff(until, 'days') + 1);
  }

  className() {
    return 'SuspendUserModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-suspend.forum.suspend_user.title', {user: this.props.user});
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('flarum-suspend.forum.suspend_user.status_heading')}</label>
            <div>
              <label className="checkbox">
                <input type="radio" name="status" checked={!this.status()} onclick={m.withAttr('value', this.status)}/>
                {app.translator.trans('flarum-suspend.forum.suspend_user.not_suspended_label')}
              </label>

              <label className="checkbox">
                <input type="radio" name="status" checked={this.status() === 'indefinitely'} value='indefinitely' onclick={m.withAttr('value', this.status)}/>
                {app.translator.trans('flarum-suspend.forum.suspend_user.indefinitely_label')}
              </label>

              <label className="checkbox SuspendUserModal-days">
                <input type="radio" name="status" checked={this.status() === 'limited'} value='limited' onclick={e => {
                  this.status(e.target.value);
                  m.redraw(true);
                  this.$('.SuspendUserModal-days-input input').select();
                  m.redraw.strategy('none');
                }}/>
                {app.translator.trans('flarum-suspend.forum.suspend_user.limited_time_label')}
                {this.status() === 'limited' ? (
                  <div className="SuspendUserModal-days-input">
                    <input type="number"
                      min="0"
                      value={this.daysRemaining()}
                      oninput={m.withAttr('value', this.daysRemaining)}
                      className="FormControl"/>
                    {app.translator.trans('flarum-suspend.forum.suspend_user.limited_time_days_text')}
                  </div>
                ) : ''}
              </label>
            </div>
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary" loading={this.loading} type="submit">
              {app.translator.trans('flarum-suspend.forum.suspend_user.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    let suspendUntil = null;
    switch (this.status()) {
      case 'indefinitely':
        suspendUntil = new Date('2038-01-01');
        break;

      case 'limited':
        suspendUntil = moment().add(this.daysRemaining(), 'days').toDate();
        break;

      default:
        // no default
    }

    this.props.user.save({suspendUntil}).then(
      () => this.hide(),
      this.loaded.bind(this)
    );
  }
}
