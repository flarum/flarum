/**
 * The `Session` class defines the current user session. It stores a reference
 * to the current authenticated user, and provides methods to log in/out.
 */
export default class Session {
  constructor(user, csrfToken) {
    /**
     * The current authenticated user.
     *
     * @type {User|null}
     * @public
     */
    this.user = user;

    /**
     * The CSRF token.
     *
     * @type {String|null}
     * @public
     */
    this.csrfToken = csrfToken;
  }

  /**
   * Attempt to log in a user.
   *
   * @param {String} identification The username/email.
   * @param {String} password
   * @param {Object} [options]
   * @return {Promise}
   * @public
   */
  login(data, options = {}) {
    return app.request(Object.assign({
      method: 'POST',
      url: app.forum.attribute('baseUrl') + '/login',
      data
    }, options));
  }

  /**
   * Log the user out.
   *
   * @public
   */
  logout() {
    window.location = app.forum.attribute('baseUrl') + '/logout?token=' + this.csrfToken;
  }
}
