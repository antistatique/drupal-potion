ARG BASE_IMAGE_TAG=8.9
FROM wengerk/drupal-for-contrib:${BASE_IMAGE_TAG}

# Install gettext library.
# Necessary for many Potion operations.
RUN set -eux; \
	\
	apt-get update; \
	apt-get install -y \
		gettext \
	;
