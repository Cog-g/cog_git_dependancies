# cog_Git_Dependancies

This script use a json file to get all needed repositories from git, and check for updates.


## Installation

I recommand to install it on /var/www
So, you will have two files :
	/var/www/cog_dependance.php
	/var/www/cog_dependance.json

The Json file contains all the repositories with
- url
- version
- path
- path
- sudo_root
- use_folder

### Json

Here is an example of a Json :

	{
		"repositories": [{
	    "name" : "cog_git_dependancies",
	    "url": "git://github.com/Cog-g/cog_git_dependancies.git",
	    "version": "master",
	    "path" : "/var/www",
	    "sudo_root" : "false",
	    "use_folder" : "true"
	  },{
	    "name" : "soda",
			"url": "git://github.com/buymeasoda/soda-theme.git",
			"version": "soda-v1",
			"path" : "/tmp/plugins",
    	"sudo_root" : "false",
    	"use_folder" : "true"
		},{
	    "name" : "tmpl",
			"url": "git://github.com/buymeasoda/tmpl.git",
			"version": "",
			"path" : "/tmp/plugins",
    	"sudo_root" : "false",
    	"use_folder" : "true"
		]}
	}

### URL

(string) It is the url of the repository.

### Version

(void|string) Could be a branch or a tag. If it is empty, master branch will be used.

### Path

(string) The path where you want to clone your repository.

### Sudo_root (deprecated)

(bool) not used anymore.

### use_folder

(bool) Tells if the folder of the repo should be use or, if the files needs to be directly copied to the specified folder.


## Run

__As root,__ simply run a command like :

	php cog_dependance.php install

Will install all needed repositories in the Json (if the repo is not present yet).

	php cog_dependance.php udpate

Will check for new version __(without install them)__
*You can set a crontask to send you the result of this command each day/week/whatever.*

	(_As root_) php cog_dependance.php upgrade

Will install any update.