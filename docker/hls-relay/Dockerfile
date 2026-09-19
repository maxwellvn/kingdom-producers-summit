FROM nginx:1.27-alpine
# The image's own default.conf would make the envsubst step skip our template.
RUN rm /etc/nginx/conf.d/default.conf
COPY default.conf.template /etc/nginx/templates/default.conf.template
