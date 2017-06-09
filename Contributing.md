# Contributing
Thanks for taking the time to contribute! Here are a few
notes for you to make the experience smoother.

## Change Workflow
### Issues
**Proposed changes of any kind must have an associated issue.**
Pull requests without a linked issue may be missed or ignored.
Before opening a pull request, please [open an issue](https://github.com/restrictcontentpro/restrict-content-pro/issues/new).
Describe the issue in detail. Note the issue number, you will need it for your pull request.

### Commit Messages
Commit messages should reference the associated issue number and follow the standard laid out in the git manual; that is,
a one-line summary ()

	Short (50 chars or less) summary of changes. #xxx (where xxx is the issue number)

	More details, if necessary.  Wrap it to about 72 characters or so.
	In some contexts, the first line is treated as the subject of an
	email and the rest of the text as the body.  The blank line separating
	the summary from the body is critical (unless you omit the body entirely);
	tools like rebase can get confused if you run the two together.

### Pull Requests
When making a pull request, please name your branch in the form of `issue/xxx`, where
`xxx` is the issue number. Also, reference your issue number in the pull request title and body
so that Github links it to the issue. This helps us review your proposal quicker.

## Development Workflow

Generally, new features and bug fixes slated for the next major release
are developed in issue branches, and merged to the `release/x.x` branch after review and testing.

Issues milestoned for a point release are also developed in issue branches, but are
merged into `master` after review and testing.

## Questions?
Shoot us an [email](https://restrictcontentpro.com/support/) or ask on the issue you created.