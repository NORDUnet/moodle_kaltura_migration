# Moodle kaltura migration tool

Script for migrating moodle to point to a new Kaltura instance.

### config.php

Create a config file for the script, a template is availible
`src/config.sample.php` it should contain something like

```php
<?php

//Kaltura settings
$KALTURA_API_PATH = realpath('php5/');
$KALTURA_ADMIN_SECRET = 'abc123abc123abc123abc123abc';
$KALTURA_PARTNER_ID = 100;
$KALTURA_PLAYER_ID = '12344';
$KALTURA_SERVICE_URL = 'https://api.kaltura.nordu.net';
$KALTURA_USER_ID = 'no-body@domain.tld';

//Moodle settings
$MOODLE_DB_HOST = "moodle_db_host";
$MOODLE_DB_USER = "moodle_user";
$MOODLE_DB_PASSWORD = "password";
$MOODLE_DB_DATABASE = "moodle_db";

```

### kaltura_entrys.inc.php
Apart from config.php, the script needs a list of entry ID's in the form of an
array called `$FILTERED_IDS`, an example is included in 
`src/kaltura_entrys.inc.sample.php` and looks like this:

```php
<?php

$FILTERED_IDS = array(
  "0_pgmb46py",
  "0_9haxl2x1",
  "0_nyr95zze",
  ....
  );
```



# License

Everything within the external/kaltura-api folder is licensed according to the
contents of the individual files in that folder and its subfolders.

Everything else is liccensed according to the NORDUnet License (3-clause BSD).
See [LICENSE.md](LICENSE.md) for more details.

# Copyright

Unless otherwise noted in the individual files the copyright holder is:
[NORDUnet](http://www.nordu.net) (2017)

