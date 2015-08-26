# web backups viewer

You can create a web page to display downloadable backup files. If put this in a publicly accesible location you should make ensure that the page is secured using basic-auth or something otherwise private data logins etc could be exposed. 

##Usage

the web viewer assumes that all backup files .zip and .sql are located in 
the parent directory (relative to where index.php is places)

```

cp /web/* /path/to/document/root/
cd /path/to/document/root/
/usr/local/bin/composer install

```
