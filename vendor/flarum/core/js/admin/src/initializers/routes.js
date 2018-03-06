import DashboardPage from 'flarum/components/DashboardPage';
import BasicsPage from 'flarum/components/BasicsPage';
import PermissionsPage from 'flarum/components/PermissionsPage';
import AppearancePage from 'flarum/components/AppearancePage';
import ExtensionsPage from 'flarum/components/ExtensionsPage';
import MailPage from 'flarum/components/MailPage';

/**
 * The `routes` initializer defines the admin app's routes.
 *
 * @param {App} app
 */
export default function(app) {
  app.routes = {
    'dashboard': {path: '/', component: DashboardPage.component()},
    'basics': {path: '/basics', component: BasicsPage.component()},
    'permissions': {path: '/permissions', component: PermissionsPage.component()},
    'appearance': {path: '/appearance', component: AppearancePage.component()},
    'extensions': {path: '/extensions', component: ExtensionsPage.component()},
    'mail': {path: '/mail', component: MailPage.component()}
  };
}
