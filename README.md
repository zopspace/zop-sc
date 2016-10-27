# ZoP Stokes Croft (SC)
========

## Introduction
Stokes Croft (SC) is an initiative of ZoP project at UWE. This aims to enable a user to upload, annotation, discuss and play videos. It also users to create video playlist based on his/her search interests. The discussion enables users to share their opinions on the video and thus socially interact with a larger community. The annotation capability on the video will also users to highlight key points in the video which later can be discussed in detail under discussion/comments section. Currently, this project is in prototype phase with basic set of features implemented. We will keep this space updated as more progress is made in this project.
  
## Configuration
Currently, the SC supports MySQL database. In order to access backend mysql database, the configuration parameters are loaded from php/config.php file. Please set the following parameters according to your settings.

```
$db_host = "IP/Hostname"; 
$db_user = "DB USER NAME";
$db_pass = "DB USER PASSWORD";
$db_name = "DB NAME"
```
