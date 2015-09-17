# Magento Session Handler for AWS DynamoDB

Author: Fabrizio Branca

TODO:
* disable automatic gc
* create cron that does gc
* how does it keep track of lifetimes?
* compress data so we can stay below 1kb
* change format (PHPserialize->JSON)so we can operate on the data without PHP (might not be worth it)
* test script: https://gist.github.com/federicob/11207881

Instructions:
* create DynamoDb table 
* hash: id
* configure iam role

In `app/etc/local.xml`
```
<session_save><![CDATA[db]]></session_save>
<dynamodb_session>
    <key><![CDATA[key]]></key>
    <secret><![CDATA[secret]]></secret>
    <region><![CDATA[region]]></region>
    <table><![CDATA[table]]></table>
</dynamodb_session>
```