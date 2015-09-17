<?php

require_once 'lib/AwsSdk/autoload.php';

class Aoe_DynamoDbSession_Model_Session {

    static $dynamoDbClient;
    static $sessionHandler;

    /**
     * Adds session handler via static call
     *
     * Sadly the 'user' case in Mage_Core_Model_Session_Abstract_Varien->start()
     * doesn't convert the Mage_Core_Model_Config_Element to a string.
     * That's why
     * <session_save><![CDATA[user]]></session_save>
     * <session_save_path><![CDATA[Aoe_DynamoDbSession_Model_Session::setSaveHandler]]></session_save_path>
     * does NOT work.
     *
     * Instead we need to rewrite Mage_Core_Model_Resource_Session and use this...
     * <session_save><![CDATA[db]]></session_save>
     */
    public static function setSaveHandler()
    {
        self::getSessionHandler()->register();
    }

    public function getSessionHandler()
    {
        if (is_null(self::$sessionHandler)) {
            $table = (string)Mage::getConfig()->getNode('global/dynamodb_session/table');
            $session_lifetime = (int)Mage::getConfig()->getNode('global/dynamodb_session/session_lifetime');
            self::$sessionHandler = Aws\DynamoDb\SessionHandler::fromClient(self::getDynamoDbClient(), [
                'table_name' => $table,
                'hash_key' => 'id',
                'session_lifetime' => min(Mage_Core_Model_Resource_Session::SEESION_MAX_COOKIE_LIFETIME, max(60, $session_lifetime))
            ]);
        }
        return self::$sessionHandler;
    }

    public static function getDynamoDbClient()
    {
        if (is_null(self::$dynamoDbClient)) {

            $config = Mage::getConfig()->getNode('global/dynamodb_session');
            if (!$config) {
                throw new Exception('DynamoDB Configuration not found.');
            }

            $key = (string)$config->descend('key');
            $secret = (string)$config->descend('secret');
            $region = (string)$config->descend('region');

            // all options: http://docs.aws.amazon.com/aws-sdk-php/v3/guide/service/dynamodb-session-handler.html
            self::$dynamoDbClient = new Aws\DynamoDb\DynamoDbClient([
                'version'     => 'latest',
                'region'      => $region,
                'credentials' => ['key' => $key, 'secret' => $secret]
            ]);

        }
        return self::$dynamoDbClient;
    }

    /**
     * Triggered via cron
     */
    public static function collectGarbage()
    {
        self::getSessionHandler()->garbageCollect();
    }

}
