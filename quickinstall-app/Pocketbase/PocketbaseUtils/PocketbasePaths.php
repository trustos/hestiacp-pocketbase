<?php

namespace Hestia\WebApp\Installers\Pocketbase\PocketbaseUtils;

use Hestia\System\HestiaApp;
use Hestia\System\Util;

class PocketbasePaths
{
    private const APP_DIR = "private/pocketbase";
    private const CONFIG_DIR = "hestiacp_pocketbase_config";
    private const APP_CONFIG_FILE_NAME = ".conf";
    private const APP_PROXY_CONFIG_FILE_NAME = "pocketbase-app.conf";
    private const POCKETBASE_PROXY_CONFIG_TEMPLATE =
        __DIR__ . "/../templates/nginx/pocketbase-app.tpl";
    private const POCKETBASE_SYSTEMD_TEMPLATE =
        __DIR__ . "/../templates/systemd/pocketbase.service.tpl";

    protected $appcontext;

    public function __construct(HestiaApp $appcontext)
    {
        $this->appcontext = $appcontext;
    }

    public function getAppDir(
        string $domain,
        string $relativePath = null
    ): string {
        $domainPath = $this->appcontext->getWebDomainPath($domain);

        if (empty($domainPath) || !is_dir($domainPath)) {
            throw new \Exception("Error finding domain folder ($domainPath)");
        }

        return Util::join_paths($domainPath, self::APP_DIR, $relativePath);
    }

    public function getConfigDir(string $relativePath = null): string
    {
        $userHome = $this->appcontext->getUserHomeDir();

        if (empty($userHome) || !is_dir($userHome)) {
            throw new \Exception("Error finding user home ($userHome)");
        }

        return Util::join_paths($userHome, self::CONFIG_DIR, $relativePath);
    }

    public function getDomainConfigDir(
        string $domain,
        string $relativePath = null
    ): string {
        return Util::join_paths(
            $this->getConfigDir("/web/" . $domain),
            $relativePath
        );
    }

    public function getConfigFile(string $domain): string
    {
        return $this->getDomainConfigDir($domain, self::APP_CONFIG_FILE_NAME);
    }

    public function getAppProxyConfig(string $domain): string
    {
        return $this->getDomainConfigDir(
            $domain,
            self::APP_PROXY_CONFIG_FILE_NAME
        );
    }

    public function getPocketbaseProxyTemplate()
    {
        return self::POCKETBASE_PROXY_CONFIG_TEMPLATE;
    }

    public function getPocketbaseSystemdTemplate()
    {
        return self::POCKETBASE_SYSTEMD_TEMPLATE;
    }
}
