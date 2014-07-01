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
