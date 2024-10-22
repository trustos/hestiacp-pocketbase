<?php

namespace Hestia\WebApp\Installers\Pocketbase;

use Hestia\WebApp\Installers\BaseSetup as BaseSetup;
use Hestia\WebApp\Installers\Pocketbase\PocketbaseUtils\PocketbasePaths as PocketbasePaths;
use Hestia\WebApp\Installers\Pocketbase\PocketbaseUtils\PocketbaseUtil as PocketbaseUtil;
use Hestia\System\HestiaApp;

class PocketbaseSetup extends BaseSetup
{
    protected const TEMPLATE_PROXY_VARS = ["%pocketbase_port%"];
    protected const TEMPLATE_SYSTEMD_VARS = [
        "%app_name%",
        "%app_dir%",
        "%user%",
    ];

    protected $pocketbasePaths;
    protected $pocketbaseUtils;
    protected $appInfo = [
        "name" => "Pocketbase",
        "group" => "database",
        "enabled" => true,
        "version" => "1.0.0",
        "thumbnail" => "pocketbase.png",
    ];
    protected $appname = "Pocketbase";
    protected $config = [
        "form" => [
            "pocketbase_version" => [
                "type" => "select",
                "options" => [
                    "v0.22.22",
                    "v0.21.3",
                    "v0.20.7",
                    "v0.19.4",
                    "v0.18.10",
                    "v0.17.7",
                ],
                "value" => "v0.22.22",
            ],
            "port" => [
                "type" => "text",
                "placeholder" => "8090",
                "value" => "8090",
            ],
        ],
        "database" => false,
        "server" => [
            "php" => [
                "supported" => ["7.2", "7.3", "7.4", "8.0", "8.1", "8.2"],
            ],
        ],
    ];

    public function __construct($domain, HestiaApp $appcontext)
    {
        parent::__construct($domain, $appcontext);

        $this->pocketbasePaths = new PocketbasePaths($appcontext);
        $this->pocketbaseUtils = new PocketbaseUtil($appcontext);
    }

    public function install(array $options = null)
    {
        if (empty($options)) {
            return $this->config["form"];
        } else {
            $this->performInstallation($options);
        }

        return true;
    }

    private function performInstallation(array $options)
    {
        try {
            $this->createAppDir();
            $this->downloadPocketbase($options);
            $this->createConfDir();
            $this->createSystemdService($options);
            $this->createAppProxyTemplates($options);
            $this->createAppConfig($options);
            $this->startPocketbaseService();
        } catch (\Exception $e) {
            $this->appcontext->runUser("v-log-action", [
                "Error",
                "Web",
                "Failed to perform Pocketbase installation for {$this->domain}: " .
                $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function createAppProxyTemplates(array $options = null)
    {
        $tplReplace = [trim($options["port"])];

        $proxyData = $this->pocketbaseUtils->parseTemplate(
            $this->pocketbasePaths->getPocketbaseProxyTemplate(),
            self::TEMPLATE_PROXY_VARS,
            $tplReplace
        );

        $tmpProxyFile = $this->saveTempFile(implode($proxyData));

        $this->pocketbaseUtils->moveFile(
            $tmpProxyFile,
            $this->pocketbasePaths->getAppProxyConfig($this->domain)
        );
    }

    public function createAppConfig(array $options = null)
    {
        $configContent = [];

        $configContent[] = "PORT=" . trim($options["port"] ?? "8090");
        $configContent[] =
            "VERSION=" . trim($options["pocketbase_version"] ?? "v0.22.22");

        $config = implode("|", $configContent);

        $file = $this->saveTempFile($config);

        return $this->pocketbaseUtils->moveFile(
            $file,
            $this->pocketbasePaths->getConfigFile($this->domain)
        );
    }

    public function createAppDir()
    {
        $this->pocketbaseUtils->createDir(
            $this->pocketbasePaths->getAppDir($this->domain)
        );
    }

    public function createConfDir()
    {
        $this->pocketbaseUtils->createDir(
            $this->pocketbasePaths->getConfigDir()
        );
        $this->pocketbaseUtils->createDir(
            $this->pocketbasePaths->getConfigDir("/web")
        );
        $this->pocketbaseUtils->createDir(
            $this->pocketbasePaths->getDomainConfigDir($this->domain)
        );
    }

    private function downloadPocketbase(array $options)
    {
        $version = $options["pocketbase_version"] ?? "v0.22.22";

        // Determine system architecture
        $arch = php_uname("m");
        $osArch = "amd64"; // Default to amd64

        if (
            strpos($arch, "arm") !== false ||
            strpos($arch, "aarch64") !== false
        ) {
            $osArch = "arm64";
        }

        $url =
            "https://github.com/pocketbase/pocketbase/releases/download/{$version}/pocketbase_" .
            substr($version, 1) .
            "_linux_{$osArch}.zip";
        error_log("Attempting to download PocketBase from: " . $url);

        $zipFile = $this->pocketbasePaths->getAppDir(
            $this->domain,
            "pocketbase.zip"
        );
        $executable = $this->pocketbasePaths->getAppDir(
            $this->domain,
            "pocketbase"
        );

        if (!$this->pocketbaseUtils->downloadFile($url, $zipFile)) {
            throw new \Exception("Failed to download Pocketbase");
        }

        if (
            !$this->pocketbaseUtils->unzipFile(
                $zipFile,
                $this->pocketbasePaths->getAppDir($this->domain)
            )
        ) {
            throw new \Exception("Failed to unzip Pocketbase");
        }

        $this->pocketbaseUtils->deleteFile($zipFile);
        $this->pocketbaseUtils->makeExecutable($executable);
    }

    private function createSystemdService(array $options)
    {
        $templateReplaceVars = [
            $this->domain,
            $this->pocketbasePaths->getAppDir($this->domain),
            $this->appcontext->getUser(),
        ];

        $data = $this->pocketbaseUtils->parseTemplate(
            $this->pocketbasePaths->getPocketbaseSystemdTemplate(),
            self::TEMPLATE_SYSTEMD_VARS,
            $templateReplaceVars
        );
        $tmpFile = $this->saveTempFile(implode($data));

        $serviceName = "pocketbase-{$this->domain}.service";
        $serviceFile = "/etc/systemd/system/{$serviceName}";

        $this->pocketbaseUtils->moveFile($tmpFile, $serviceFile);
        $this->pocketbaseUtils->reloadSystemd();
    }

    private function startPocketbaseService()
    {
        $serviceName = "pocketbase-{$this->domain}";
        $this->pocketbaseUtils->startService($serviceName);
        $this->pocketbaseUtils->enableService($serviceName);
    }
}
