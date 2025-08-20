#!/bin/bash
cd /home/api-magangment-service.pick-a-part.ca/public_html
for i in {1..20}
do
  php artisan dispatch:admin-event 123
  sleep 2
done
