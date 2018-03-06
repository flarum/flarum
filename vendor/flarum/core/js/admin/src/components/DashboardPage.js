import Page from 'flarum/components/Page';

export default class DashboardPage extends Page {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          <h2>{app.translator.trans('core.admin.dashboard.welcome_text')}</h2>
          <p>{app.translator.trans('core.admin.dashboard.version_text', {version: <strong>{app.forum.attribute('version')}</strong>})}</p>
          <p>{app.translator.trans('core.admin.dashboard.beta_warning_text', {strong: <strong/>})}</p>
          <ul>
            <li>{app.translator.trans('core.admin.dashboard.contributing_text', {a: <a href="http://flarum.org/docs/contributing" target="_blank"/>})}</li>
            <li>{app.translator.trans('core.admin.dashboard.troubleshooting_text', {a: <a href="http://flarum.org/docs/troubleshooting" target="_blank"/>})}</li>
            <li>{app.translator.trans('core.admin.dashboard.support_text', {a: <a href="http://discuss.flarum.org/t/support" target="_blank"/>})}</li>
            <li>{app.translator.trans('core.admin.dashboard.features_text', {a: <a href="http://discuss.flarum.org/t/features" target="_blank"/>})}</li>
            <li>{app.translator.trans('core.admin.dashboard.extension_text', {a: <a href="http://flarum.org/docs/extend" target="_blank"/>})}</li>
          </ul>
        </div>
      </div>
    );
  }
}
