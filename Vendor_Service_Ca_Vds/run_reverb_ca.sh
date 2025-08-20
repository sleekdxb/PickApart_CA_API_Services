#!/bin/bash

# Change directory to where the artisan command is located
cd /home/api-vendor-service-live.pick-a-part.ca/public_html

# Run the Artisan command
php artisan reverb:start

# Optional: Log the output to a file
echo "$(date): reverb:start executed" >> /home/api-vendor-service-live.pickapart.ae/public_html/reverb.log
