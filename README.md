simpubly
========

I own a webspace where I only can execute php files. I can not install git or other publishing software. Still I want to be able to publish the newest version from github project to my webspace without manually copy and paste the files.

What the script can do:
* Check if you're authorized
* Backup you project
* Ingoring folder and files for backup
* Load a zipped (.zip) project from an url
* Define which folder of the .zip file should be published (common is the master branch)
* Copy the zip contents to you project folder
* Ingoring folder and files for publish


Example folder structure:

```
index.html
do-not-overwrite-me.html
do-not-backup.txt
css
  /style.css
js
  /script.js
_simpubly
  /publish.php
```

Example configuration:

```php
const PROJECT_ZIP_URL = 'https://github.com/user/project/archive/master.zip';
const INNER_ZIP_PATH = 'project-master';
const PROJECT_DIR = '../'; // relative from current dir
const DO_BACKUP = true;
const AUTHORIZATION_KEY = 'test';  // set to null for no authorization

$ignorePathsForCopy = array(
  'do-not-overwrite-me.html'
);

$ignorePathsForBackup = array(
    'do-not-backup.txt'
);
```

You execute the file via a browser or a console (you should be in the same directory).

You can pass the `AUTHORIZATION_KEY` via `key` in GET or POST. In the console the `AUTHORIZATION_KEY` is not needed.

Please make sure, that you set the permissions for the files and folder correctly.

Default backup folder is `_simpubly/backups`.









