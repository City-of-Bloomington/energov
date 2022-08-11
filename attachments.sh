#!/bin/bash
SITE_HOME=/srv/sites/energov/data

# Rental permit attachments
host=apps.bloomington.in.gov
path=/srv/webapps/rentpro/images/rent
rsync -rlve ssh ${host}:${path}/ ${SITE_HOME}/rental/files/

# Citation attachments
path=/srv/webapps/citation/images/depot
rsync -rlve ssh ${host}:${path}/ ${SITE_HOME}/citation/depot/

