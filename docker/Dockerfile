FROM php:8.2-apache

# mysqli 확장 설치 (앱이 mysqli 로 DB 접속)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# .htaccess 를 적용 가능하게 (보완 버전의 업로드 폴더 스크립트 차단용)
RUN sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 데모 편의를 위한 업로드 용량 (기본 2M 로도 충분하지만 명시)
RUN { \
      echo 'upload_max_filesize = 8M'; \
      echo 'post_max_size = 10M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# 업로드 폴더는 컨테이너 안에서 쓰기 가능해야 함
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads
