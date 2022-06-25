
## Installation

You can add this library as a local dependency to your project using [Composer](https://getcomposer.org/):

```
composer require thomasfielding/version
```

## What is this package?

This package is designed to allow you to log version changes throughout your application development process. A slightly more manual process than what other packages may be offering, this does allow for more coherent logging of major, minor and patch updates to your system while working as a remote team from one codebase.

Upon installation, it will generate a config file inside your config directory `config/version.php`. This file contains a few settings which modify the behaviour of this package:

-  **git**: A boolean value, true if you are using git source control on the project and false if not. This will just change if the git branch is stored in the version log or not.
-  **initial**: A string formatted as **1.13.4**, by default it is **0.0.0** but you can change this to modify what the starting version number of your application is, if adding this package to an existing product without a version history to convert over.
-  **root**: This is the location you want your version logs to be stored, by default it is `./version`.
-  **template**: This is an object that can be added for additional parameters (custom) to be added to the enforced log parameters; `branch_id`, `description`, `id`, `timestamp` and `type`.

All logs are stored as json files which you can edit manually, and will be stored as part of your codebase. Only 1 log can be created per git branch (if git is enabled in your config settings).

## How to use

The main use of this package is to get the **current version number** and the **formatted patch notes**.  These can be accessed by extending `ThomasFielding\Version\Services\VersionService` in the following ways:

```
public function __construct(VersionService $versionService) {
	$this->versionService = $versionService;
}
```

Or alternatively if you cannot inject the dependency into your constructor:

```
public function __construct(VersionService $versionService) {
	$this->versionService = new VersionService();
}
```

Once initialised, you can use the following functions:

 - `$this->versionService->getVersionNumber()`: This will return the current version number (i.e. **1.13.4**)
 - `$this->versionService->getPatchNotes()`: This will return an object of your notes that you can pass to the frontend.

## Commands

You can use 3 different artisan commands to start generating, updating and fetching version numbers for your application.

-  `php artisan version:current`: This command will return the current version number of your application (i.e. **1.13.4**).

-  `php artisan version:log`: This command will create a new log json file inside your version directory. The name of the file is only representative of when the log was created, but does not affect the run order or version number of the application, this is worked out using the `id` and `timestamp` keys inside each log file. You can pass one argument and one option to this command.

-  `php artisan version:log major`: This will create a major version update. Typically used when an epic has completed or a large chunk of work is added which fundamentally changes how the application works.

-  `php artisan version:log minor`: This will create a minor version update. Typically used for feature released.

-  `php artisan version:log patch`: This will create a patch version update. Usually used for hotfixes and bugfixes, this is the default if no argument is given.

-  `php artisan version:log --description="Your log message goes here"`: This can be updated manually after the log is created, however this is meant to mimic how git commit messages work and will display as the description for this new version number.

-  `php artisan version:log:update`: If git is set to false then this will skip all functionality. If git is enabled and a log exists for the current branch, then it will update the `timestamp` of that log to reflect the current timestamp. A good function to run before pull requests are created to ensure the version number more accurately reflects the order of release.

  

## Version Service

The logic for this package is managed by a service called **"Version Service"** available under the namespace `ThomasFielding\Version\Services\VersionService`. As part of this, there are a few public functions made available for your access:

-  `getFileById`: This will allow you to fetch the data from a specific version log by using the unique id that is generated whenever you create a log. This can be found within the json file each log creates. This requires one parameter, a `string $id` which is the id to find by, if nothing is found it will return a null value.

-  `getGitBranchId`: A more generic function, this will fetch the git branch id if you have `git: true` set in the config file. It can throw two exceptions, either `DuplicateLogException` or `UncommittedBranchException` which will both print their message to the terminal.

-  `getLogsByBranchId`: Fetching the data for a log based on the git branch you supply. This will fetch based on a git branch id, and requires a `string $git` parameter, which could be retrieved from the `getGitBranchId` function included in this service.

-  `getMajorVersionNumber`: Get the major version number. This accepts one parameter `?string $version`, if unset it will get the current major version of the application, however it can also accept strings of `major`, `minor`, or `patch` depending on which element of the version number you wish to increment by 1.

-  `getMinorVersionNumber`: Get the minor version number. This accepts one parameter `?string $version`, if unset it will get the current minor version of the application, however it can also accept strings of `major`, `minor`, or `patch` depending on which element of the version number you wish to increment by 1.

-  `getPatchVersionNumber`: Get the patch version number. This accepts one parameter `?string $version`, if unset it will get the current patch version of the application, however it can also accept strings of `major`, `minor`, or `patch` depending on which element of the version number you wish to increment by 1.

-  `getRoot`: A simple function to return the directory location where all version logs will be stored.

-  `getVersionNumber`: Fetches the entire version number as a string to display (i.e. **1.13.4**). This accepts one parameter `?string $version`, if unset it will get the current version of the application, however it can also accept strings of `major`, `minor`, or `patch` depending on which element of the version number you wish to increment by 1.