# 2.1.0

* Add a `SystemFacade` to allow clients to override Whoops behavior.
* Show frame arguments in `PrettyPageHandler`.
* Highlight the line with the error.
* Add icons to search on Google and Stack Overflow.

# 2.0.0

Backwards compatibility breaking changes:

* `Run` class is now `final`. If you inherited from `Run`, please now instead use a custom `SystemFacade` injected into the `Run` constructor, or contribute your changes to our core.
* PHP < 5.5 support dropped.
