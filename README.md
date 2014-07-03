toggl-tam-report
================
Personal TAM monthly hours report to run under an LAMP stack.

Installation
------------
To install you'll need a LAMP install, composer and PHP 5.4 or greater.
First install composer dependancies
    
    cd toggl-tam-report
    composer install

Copy the *.yml.example files to actual YAML files and fill out accordingly.
    
    cp api.yml.example api.yml
    cp accounts.yml.example accounts.yml

Once these two files are in place you should be able to visit the directory in a web browser to see the report.
Report relies on Toggl API, Bootstrap CDN and Google Charts to all be up to work.

Optional Configuration
----------------------
By default, the report will report on all billable client hours. If you want to only see the billable ours
that you've logged against that client, then you'll need to provide an additional parameter to api.yml
to filter the toggl data by hours logged by you. Do to that you'll need to find your toggl ID. This can
be found by using the toggl reports UI to build a report filtered on your account. You should see your ID
in the URL for that report.

In the api.yml file add the following equivalent line:
    user_ids: <insert user id here>
