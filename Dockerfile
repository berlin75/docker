FROM php:7.1-cli
  
WORKDIR /usr/src/swoole
RUN buildDeps='unzip wget' \
# 修改apt源为163的源
&& echo "deb http://mirrors.163.com/debian/ jessie main non-free contrib" > /etc/apt/sources.list \
&& echo "deb http://mirrors.163.com/debian/ jessie-updates main non-free contrib" >> /etc/apt/sources.list \
&& echo "deb http://mirrors.163.com/debian/ jessie-backports main non-free contrib" >> /etc/apt/sources.list \
&& echo "deb-src http://mirrors.163.com/debian/ jessie main non-free contrib" >> /etc/apt/sources.list \
&& echo "deb-src http://mirrors.163.com/debian/ jessie-updates main non-free contrib" >> /etc/apt/sources.list \
&& echo "deb-src http://mirrors.163.com/debian/ jessie-backports main non-free contrib" >> /etc/apt/sources.list \
&& echo "deb http://mirrors.163.com/debian-security/ jessie/updates main non-free contrib" >> /etc/apt/sources.list \
&& apt-get update \
&& apt-get install -y $buildDeps \
#安装igbinary扩展
&& pecl install -o -f igbinary \ 
&& rm -rf /tmp/pear \ 
&& docker-php-ext-enable igbinary \
#安装redis扩展
&& pecl install -o -f redis \ 
&& rm -rf /tmp/pear \ 
&& docker-php-ext-enable redis \
#安装mysql扩展
&& docker-php-ext-install pdo_mysql \
#安装swoole1.10.1
&& git clone https://github.com/swoole/swoole-src.git \
&& cd swoole-src \
&& phpize \
&& ./configure \
&& make \
&& make install \
&& docker-php-ext-enable swoole \
&& rm -rf /var/lib/apt/lists/* \
&& rm -rf /tmp/swoole-src \
&& rm -r /usr/src/swoole \
&& apt-get purge -y --auto-remove $buildDeps
WORKDIR /