#!/bin/bash

php artisan migrate --force

php artisan queue:work --queue=default &

apache2-foreground
