Release process rely on 2 pieces.

The first,  .github/workflows/release_module.yml :
- creates a zipfile ready to deploy
- creates a new tag
- creates a release
- add the created zipfile to the newly created tag
- there is just poor contents in the release file.

if the workflows runs and the tags already exists, it fails.

The second, .github/workflows/release-drafter.yml :
- create a release __draft__ with merged pull requests since last release. So a nice release text.

## Not to do

Edit the release draft made by release drafter and publish the release.
Doing this creates a release and a tag, then the first workflows runs and fails : no zip added to the release

## Todo

- update the version in the module file.
- Edit the release draft : copy the generated content, arrange it a bit if needed : __do NOT publish the release__.
- create a tag -> the first worflow runs : a release with the zip is created.
- Edit the release and past the contents copied at step 1.

We should write a better release action, that do not rely on `marvinpinto/action-automatic-releases@latest`

