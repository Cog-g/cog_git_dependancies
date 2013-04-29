<?php
/*
** This script use a json file to get all needed repositories for a site, and
** check for updates.
**
** @author    : Constantin Guay
** @url       : http://const-g.fr
** @version   : 1.5.1
** @usage     : php cog_dependance.php argv
** @param     : $argv[1]
**                = install > will install new repo only.
**                = update  > will *check* for new version.
**                = upgrade > will pull all repo.
** @required  : -> a cog_dependance.json with a "repositories" array contening all
**               parameters for each one :
**                  name : the name of the repo. It will be use as folder name.
**                  url : url of the git repo
**                  version : A tag or branch, if empty will use "master"
**                  path : The path on the server where the repository should be up.
**                  sudo_root : If, to access to the patch, we need root acces, set true.
**                  use_folder : true if you want to use a folder withe the repository name
**                               or the name specified in the Json, or false if you want
**                               the content directly in the path.
** @optional  : -> git 1.8 to use the single branch clone option.
** @licence   : MIT
** @todo      : a "script_to_lauch" parameter to set a script to launch after 
**              the installation of the repo.
** @changelog :
**              1.5.1 : . Added the forgoten params to copy "-ipr"
**                      . Check for writing permission to the needed folder
*/

define("DEBUG", true);

if(empty($argv[1]))
  exit("What do you want me to do ?!\n");

$filename = "/var/www/cog_git_dependancies/cog_dependance.json";
if(!file_exists($filename))
  exit($filename . " does not exists.\n");

// Json
$dep = file_get_contents($filename);
$repos = json_decode($dep);

// Git 1.8 is mandatory to use --single-branch clone option
$git_version = substr(exec("git --version"), 12, 3);
$option_single_branch = "";
if(floatval($git_version) > 1.8) 
  $option_single_branch = "--single-branch ";



$sudo = "sudo -u www-data ";
$sudo_root = "sudo -u root ";
$installed = 0;


foreach ($repos->repositories as $repo) {
  if(empty($repo->version))
    $repo->version  = "master";

  $repo->exists = false;

  if($argv[1] == "update")
    echo("\nChecking : " . $repo->name);

  if(!file_exists($repo->path) || !is_writable($repo->path)) {
    echo($repo->path . " is not writable\n");
    continue;
  }

  
  if(!empty($repo->name)) {
    $repo_filename = $repo->name;
  }
  else {
    $path = $repo->url;
    if(strpos($path, "@") && strpos($path, ":") && strpos($path, ".git")) {
      $repo_filename = substr($path, strpos($path, ":")+1);
      $repo_filename = str_replace(".git", "", $repo_filename);
    }
    else {
      $repo->pathinfo = pathinfo($path);
      $repo_filename = $repo->pathinfo['filename'];
    }
  }

  // This is the dir where all repositories will be stored.
  // content will be copied in the right folder after, without
  // any git folder/file.
  $install_dir = "/usr/local/cog_dependancies/" . $repo_filename . "#" . $repo->version;
  $repo->dir = $repo->path;
  
  if($repo->use_folder == 'true') 
    $repo->dir .= '/' . $repo_filename;

  // Check if the .git folder exists
  if(file_exists($install_dir . "/.git"))
    $repo->exists = true;

  // If the argument is INSTALL
  if(!$repo->exists && $argv[1] == "install") {
    // Will clone the soda-v1 branch directly into /tmp/plugin (instead of soda-theme folder).
    $clone = exec($sudo_root . 'git clone -b ' . $repo->version . ' ' . $option_single_branch . $repo->url . ' ' . $install_dir . "\n");

    // copy the file, without any git folder/file and remove README.*
    echo("Moving to " . $repo->dir . "\n");
    echo exec( $sudo_root . "cp -ipr" . $install_dir . "/* " . $repo->dir . "/\nrm " 
                          . $repo->dir . "/README*");
    $installed++; // one more.
  }


  if($argv[1] == "update" || $argv[1] == "upgrade") {
    if($repo->exists) {
      $hasUpdate = exec('cd ' . $install_dir . "\n" . $sudo_root . "git status -sb\n");
    }
    else {
      $hasUpdate = $repo_filename . " is not cloned yet, but could be\n -> Run : php " . $argv[0] . " install\n";
      echo($hasUpdate);
    }
    
    if($hasUpdate != '## ' . $repo->version) {
      if($repo->exists && $argv[1] == "upgrade") {        
        exec('cd ' . $repo->dir . "\n" . $sudo_root . "git pull\n");
        echo($repo_filename . " #" . str_replace('## ', "", $hasUpdate) . " has been updated\n");
      }
    }
    else {
      echo(" #" . str_replace('## ', "", $hasUpdate) . " is up-to-date.\n");
    }
  }
}

if($argv[1] == "install" && $installed == 0)
  echo("Nothing to install.\n");
?>