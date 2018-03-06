/**
 * The `Store` class defines a local data store, and provides methods to
 * retrieve data from the API.
 */
export default class Store {
  constructor(models) {
    /**
     * The local data store. A tree of resource types to IDs, such that
     * accessing data[type][id] will return the model for that type/ID.
     *
     * @type {Object}
     * @protected
     */
    this.data = {};

    /**
     * The model registry. A map of resource types to the model class that
     * should be used to represent resources of that type.
     *
     * @type {Object}
     * @public
     */
    this.models = models;
  }

  /**
   * Push resources contained within an API payload into the store.
   *
   * @param {Object} payload
   * @return {Model|Model[]} The model(s) representing the resource(s) contained
   *     within the 'data' key of the payload.
   * @public
   */
  pushPayload(payload) {
    if (payload.included) payload.included.map(this.pushObject.bind(this));

    const result = payload.data instanceof Array
      ? payload.data.map(this.pushObject.bind(this))
      : this.pushObject(payload.data);

    // Attach the original payload to the model that we give back. This is
    // useful to consumers as it allows them to access meta information
    // associated with their request.
    result.payload = payload;

    return result;
  }

  /**
   * Create a model to represent a resource object (or update an existing one),
   * and push it into the store.
   *
   * @param {Object} data The resource object
   * @return {Model|null} The model, or null if no model class has been
   *     registered for this resource type.
   * @public
   */
  pushObject(data) {
    if (!this.models[data.type]) return null;

    const type = this.data[data.type] = this.data[data.type] || {};

    if (type[data.id]) {
      type[data.id].pushData(data);
    } else {
      type[data.id] = this.createRecord(data.type, data);
    }

    type[data.id].exists = true;

    return type[data.id];
  }

  /**
   * Make a request to the API to find record(s) of a specific type.
   *
   * @param {String} type The resource type.
   * @param {Integer|Integer[]|Object} [id] The ID(s) of the model(s) to retreive.
   *     Alternatively, if an object is passed, it will be handled as the
   *     `query` parameter.
   * @param {Object} [query]
   * @param {Object} [options]
   * @return {Promise}
   * @public
   */
  find(type, id, query = {}, options = {}) {
    let data = query;
    let url = app.forum.attribute('apiUrl') + '/' + type;

    if (id instanceof Array) {
      url += '?filter[id]=' + id.join(',');
    } else if (typeof id === 'object') {
      data = id;
    } else if (id) {
      url += '/' + id;
    }

    return app.request(Object.assign({
      method: 'GET',
      url,
      data
    }, options)).then(this.pushPayload.bind(this));
  }

  /**
   * Get a record from the store by ID.
   *
   * @param {String} type The resource type.
   * @param {Integer} id The resource ID.
   * @return {Model}
   * @public
   */
  getById(type, id) {
    return this.data[type] && this.data[type][id];
  }

  /**
   * Get a record from the store by the value of a model attribute.
   *
   * @param {String} type The resource type.
   * @param {String} key The name of the method on the model.
   * @param {*} value The value of the model attribute.
   * @return {Model}
   * @public
   */
  getBy(type, key, value) {
    return this.all(type).filter(model => model[key]() === value)[0];
  }

  /**
   * Get all loaded records of a specific type.
   *
   * @param {String} type
   * @return {Model[]}
   * @public
   */
  all(type) {
    const records = this.data[type];

    return records ? Object.keys(records).map(id => records[id]) : [];
  }

  /**
   * Remove the given model from the store.
   *
   * @param {Model} model
   */
  remove(model) {
    delete this.data[model.data.type][model.id()];
  }

  /**
   * Create a new record of the given type.
   *
   * @param {String} type The resource type
   * @param {Object} [data] Any data to initialize the model with
   * @return {Model}
   * @public
   */
  createRecord(type, data = {}) {
    data.type = data.type || type;

    return new (this.models[type])(data, this);
  }
}
