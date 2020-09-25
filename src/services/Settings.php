<?php
/**
 * Socializer plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2019 Enupal LLC
 */

namespace enupal\socializer\services;

use Craft;
use craft\db\Query;
use yii\base\Component;
use enupal\socializer\models\Settings as SettingsModel;
use enupal\socializer\Socializer;

class Settings extends Component
{
    /**
     * Saves Settings
     *
     * @param $scenario
     * @param $settings SettingsModel
     *
     * @return bool
     */
    public function saveSettings(SettingsModel $settings, $scenario = null): bool
    {
        $plugin = $this->getPlugin();

        if (!is_null($scenario)) {
            $settings->setScenario($scenario);
        }

        // Validate them, now that it's a model
        if ($settings->validate() === false) {
            return false;
        }

        $success = Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->getAttributes());

        return $success;
    }

    /**
     * @return bool|string
     */
    public function getPrimarySiteUrl()
    {
        $primarySite = (new Query())
            ->select(['baseUrl'])
            ->from(['{{%sites}}'])
            ->where(['primary' => 1])
            ->one();

        $primarySiteUrl = Craft::getAlias($primarySite['baseUrl']);

        return Craft::parseEnv(Craft::getAlias(rtrim(trim($primarySiteUrl), "/")));
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->getPrimarySiteUrl()."/socializer/login/callback";
    }

    /**
     * @return SettingsModel
     */
    public function getSettings()
    {
        /** @var SettingsModel $settings */
        $settings = $this->getPlugin()->getSettings();

        return $settings;
    }

    /**
     * @return \craft\base\PluginInterface|null
     */
    public function getPlugin()
    {
        return Craft::$app->getPlugins()->getPlugin('enupal-socializer');
    }

    /**
     * @return string|null
     */
    public function getPluginUid()
    {
        $plugin = (new Query())
            ->select(['uid'])
            ->from('{{%plugins}}')
            ->where(["handle" => 'enupal-socializer'])
            ->one();

        return $plugin['uid'] ?? null;
    }

    /**
     * @return array
     */
    public function getGlobalFieldMapping()
    {
        $settings = $this->getSettings();

        return $settings->fieldMapping ?? Socializer::$app->providers->getDefaultFieldMapping();
    }

    /**
     * @return array|\craft\models\UserGroup[]
     */
    public function getUserGroups()
    {
        $userGroups = [
            ["name" => "None", "id" => ""]
        ];
        $craftUserGroups = Craft::$app->getUserGroups()->getAllGroups();

        $userGroups = array_merge($userGroups, $craftUserGroups);

        return $userGroups;
    }

    /**
     * @return array|null
     */
    public function getConfigSettings()
    {
        return Craft::$app->config->getGeneral()->socializer ?? null;
    }

    public function validateAppleSettings()
    {
        $config = $this->getConfigSettings();

        if (!isset($config['socializer']['apple'])) {
            Craft::error('Apple config is not set', __METHOD__);
            return false;
        }

        $apple = $config['socializer']['apple'];

        if (!isset($apple['keys']['id']) || !isset($apple['keys']['team_id']) ||
            !isset($apple['keys']['key_id']) ||
            !isset($apple['keys']['key_file']) || !isset($apple['scope']) ||
            !isset($apple['verifyTokenSignature'])) {
            Craft::error('Missing a required Apple config, please check our docs.', __METHOD__);
            return false;
        }

        if (!file_exists($apple['keys']['key_file'])) {
            Craft::error('Unable to find Apple key file: '.$apple['keys']['key_file'], __METHOD__);
        }

        return true;
    }
}
