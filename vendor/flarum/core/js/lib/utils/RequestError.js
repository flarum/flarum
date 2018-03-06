export default class RequestError {
  constructor(status, responseText, options, xhr) {
    this.status = status;
    this.responseText = responseText;
    this.options = options;
    this.xhr = xhr;

    try {
      this.response = JSON.parse(responseText);
    } catch (e) {
      this.response = null;
    }

    this.alert = null;
  }
}
