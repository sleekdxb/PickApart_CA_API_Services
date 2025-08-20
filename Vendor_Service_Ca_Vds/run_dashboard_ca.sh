#!/bin/bash
cd /home/api-vendor-service-live.pick-a-part.ca/public_html
for i in {1..20}
do
  php artisan get:dashboard
  sleep 2
done
