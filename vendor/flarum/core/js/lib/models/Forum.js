import Model from 'flarum/Model';

export default class Forum extends Model {
  apiEndpoint() {
    return '/forum';
  }
}
