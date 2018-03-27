import Modal from 'flarum/components/Modal';

export default class RequestErrorModal extends Modal {
  className() {
    return 'RequestErrorModal Modal--large';
  }

  title() {
    return this.props.error.xhr
      ? this.props.error.xhr.status+' '+this.props.error.xhr.statusText
      : '';
  }

  content() {
    let responseText;

    try {
      responseText = JSON.stringify(JSON.parse(this.props.error.responseText), null, 2);
    } catch (e) {
      responseText = this.props.error.responseText;
    }

    return <div className="Modal-body">
      <pre>
        {this.props.error.options.method} {this.props.error.options.url}<br/><br/>
        {responseText}
      </pre>
    </div>;
  }
}
