# Contributing

We are more than happy to accept external contributions to the project in the form of feedback, bug reports and even better - pull requests :)

These rules are adopted from the AngularJS and Yeoman project.

## Issue submission

In order for us to help you please check that you've completed the following steps:

* Used the search feature to ensure that the bug hasn't been reported before
* Included as much information about the bug as possible, including any output you've received, what OS and version you're on, etc.

[Submit your issue](https://github.com/flyve-mdm/glpi-plugin/issues/new)

## Style Guide

This project follows the [GLPI development guidelines](https://readthedocs.org/projects/glpi-developer-documentation/).

## Pull Request Guidelines

* Please check to make sure that there aren't existing pull requests attempting to address the issue mentioned. We also recommend checking for issues related to the issue on the tracker, as a team member may be working on the issue in a branch or fork.
* Non-trivial changes should be discussed in an issue first
* Develop in a topic branch, not master
* Add relevant tests to cover the change
* Make sure test-suite passes: `phpuinit`
* Squash your commits
* Write a convincing description of your PR and why we should land it
* Check the individual project to see if there is a **contributing.md** or similar file as some project's have different requirements.
* Make sure that the commit messages match the [AngularJS conventions][commit-message-format] (see below).
* When submitting a bugfix, write a test that exposes the bug and fails before applying your fix. Submit the test alongside the fix.
* When submitting a new feature, add tests that cover the feature.

## Git Commit Guidelines

We use [git-flow](http://git-flow.readthedocs.io/en/latest/presentation.html) as branching model for Git.

### Commit Message Format

Each commit message consists of a **header**, a **body** and a **footer**.  The header has a special
format that includes a **type**, a **scope** and a **subject**:

```TXT
<type>(<scope>): <subject>
<BLANK LINE>
<body>
<BLANK LINE>
<footer>
```

Any line of the commit message cannot be longer 100 characters! This allows the message to be easier
to read on github as well as in various git tools.

### Type

Must be one of the following:

* **feat**: A new feature
* **fix**: A bug fix
* **docs**: Documentation only changes
* **style**: Changes that do not affect the meaning of the code (white-space, formatting, missing
  semi-colons, etc)
* **refactor**: A code change that neither fixes a bug or adds a feature
* **test**: Adding missing tests
* **chore**: Changes to the build process or auxiliary tools and libraries such as documentation
  generation

### Scope

The scope could be anything specifying place of the commit change. For example `app`,
`gen`, `docs`, `gen:view`, `gen:route`, `gen:service`, etc... It is preferred to use ascope matching an available label for issues. If no label match the scope of your PR, please, get in touch with us.

### Subject

The subject contains succinct description of the change:

* use the imperative, present tense: "change" not "changed" nor "changes"
* don't capitalize first letter
* no dot (.) at the end

### Body

Just as in the **subject**, use the imperative, present tense: "change" not "changed" nor "changes"
The body should include the motivation for the change and contrast this with previous behavior.

### Footer

The footer should contain any information about **Breaking Changes** and is also the place to
reference GitHub issues that this commit **Closes**.

A detailed explanation can be found in this [document][commit-message-format].

[commit-message-format]: https://docs.google.com/document/d/1QrDFcIiPjSLDn3EL15IJygNPiHORgU1_OOAqWjiDU5Y
