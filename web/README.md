# web backup viewer

You can create a web page to display downloadable backup files. If you put this in a publicly accessible location you should make ensure that the page is secured using basic-auth, or something, otherwise private data logins etc could be exposed. 

##Setup

the web viewer assumes that all backup files .zip and .sql are located in 
the parent directory (relative to where index.php is places)

Generally when I create a production apache vhost, it goes in a folder like: /path/to/web/production/vhost-name/http
then if i want to make backups downloadable for a vhost I actually create a second vhost like: /path/to/web/production/vhost-name/backups
and place the web backup viewer there. however the backup files are actually stored in the parent directory and are not
accessible directly.

```

cd /path/to/document/root/
git clone https://github.com/nickolanack/apache-vhost-backups-cron.git

cp apache-vhost-backups-cron/web/* .
cd /path/to/document/root/
/usr/local/bin/composer install
rm apache-vhost-backups-cron -rf

```
