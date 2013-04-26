<?php
/*
** This script use a json file to get all needed repositories for a site, and
** check for updates.
**
** @author    : Constantin Guay
** @url       : http://const-g.fr
** @version   : 1.0
** @usage     : php cog_dependance.php argv
** @param     : $argv[1]
**                = install > will install new repo only.
**                = update  > will *check* for new version.
**                = upgrade > will pull all repo.
** @required  : -> a cog_dependance.json with a "repositories" array contening all
**               parameters for each one :
**                  url : url of the git repo
**                  version : A tag or branch, if empty will use "master"
**                  path : The path on the server where the repository should be up.
** @optional  : -> git 1.8 to use the single branch clone option.
** @licence   : MIT
*/

define("DEBUG", true);

if(empty($argv[1]))
  exit("What do you want me to do ?!\n");

$filename = "cog_dependance.json";
if(!file_exists($filename))
  exit($filename . " does not exists.\n");

$dep = file_get_contents($filename);
$repos = json_decode($dep);

// Git 1.8 is mandatory to use --single-branch clone option
$git_version = substr(exec("git --version"), 12, 3);
$option_single_branch = "";
if(floatval($git_version) > 1.8) 
  $option_single_branch = "--single-branch ";


$installed = 0;

foreach ($repos->repositories as $repo) {
  if(empty($repo->version))
    $repo->version  = "master";

  $repo->exists = false;
  $repo->pathinfo = pathinfo($repo->url);
  $repo->dir = $repo->path . '/' . $repo->pathinfo['filename'];
  if(file_exists($repo->dir . "/.git"))
    $repo->exists = true;

  if(!$repo->exists && $argv[1] == "install") {
    // >  git clone -b soda-v1 --single-branch git://github.com/buymeasoda/soda-theme.git /tmp/plugins
    // Will clone the soda-v1 branch directly into /tmp/plugin (instead of soda-theme folder).
    $clone = exec('git clone -b ' . $repo->version . ' ' . $option_single_branch . $repo->url . ' ' . $repo->dir . "\n");
    $installed++;
  }

  if($argv[1] == "update" || $argv[1] == "upgrade") {
    if($repo->exists) {
      $hasUpdate = exec('cd ' . $repo->dir . "\ngit status -sb\n");
    }
    else {
      $hasUpdate = $repo->url . " is not cloned yet, but could be -> Run " . $argv[0] . " install\n";
    }
    echo($repo->pathinfo['filename'] . " : version " . str_replace('## ', "", $hasUpdate) . " is up-to-date\n");
    
    if($repo->exists && $argv[1] == "upgrade") {
      if($hasUpdate != '## ' . $repo->version) {
        exec('cd ' . $repo->dir . "\ngit pull\n");
      }
    }
  }
}

if($argv[1] == "install" && $installed == 0)
  echo("Nothing to install.\n");
?>