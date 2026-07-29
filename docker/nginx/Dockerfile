FROM nginx:stable-alpine

COPY docker/nginx/conf.d/tracker-api.local.conf /etc/nginx/conf.d/default.conf
COPY TrackerApi/public /var/www/tracker-api/public