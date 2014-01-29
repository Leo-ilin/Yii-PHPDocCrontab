#This is a fork of Yii PHPDocCrontab extension https://github.com/Yiivgeny/Yii-PHPDocCrontab
Unlike the author's solution is that the tasks are stored in the database.

##Installation
- **Step 1:** Put directory PHPDocCrontab (or only PHPDocCrontab.php) into your framework extensions directory.
- **Step 2:** Add PHPDocCrontab.php and DbCrontab as new console command on framework config:

```php
'commandMap' => array(
    'cron' => 'ext.PHPDocCrontab.PHPDocCrontab',
    'scheduler' => 'ext.DbCrontab'
)
```

- **Step 3:**  Add task to system scheduler (cron on unix, task scheduler on windows) to run every minute:

```sh
* * * * * /path/to/yii/application/protected/yiic cron
* * * * * /path/to/yii/application/protected/yiic scheduler
```

##Resources
https://github.com/Yiivgeny/Yii-PHPDocCrontab
