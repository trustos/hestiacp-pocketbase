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
            "installation_type" => [
                "type" => "select",
                "options" => [
                    "pocketbase" => "PocketBase",
                    "pocket_admin" => "Pocket Admin",
                ],
                "value" => "pocketbase",
            ],
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
            "pocket_admin_version" => [
                "type" => "select",
                "options" => [
                    "v0.0.1",
                    // Add more versions as they become available
                ],
                "value" => "v0.0.1",
                "visible" => "installation_type === 'pocket_admin'",
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
            // $this->createSystemdService($options);
            // Example usage
            $domain = $this->domain;
            $user = $this->appcontext->user();
            $appPath = $this->pocketbasePaths->getAppDir($this->domain);

            if ($this->createServiceFile($options, $domain, $user, $appPath)) {
                echo "Service file creation successful.\n";
            } else {
                echo "Service file creation failed.\n";
            }
            $this->createAppProxyTemplates($options);
            $this->createAppConfig($options);
            // $this->startPocketbaseService();
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
        $tplReplace = [trim($options["port"] ?? "8090")];

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
        $installationType = $options["installation_type"] ?? "pocketbase";

        if ($installationType === "pocketbase") {
            $version = $options["pocketbase_version"] ?? "v0.22.22";
            $baseUrl = "https://github.com/pocketbase/pocketbase/releases/download/{$version}/pocketbase_";
            $versionNumber = substr($version, 1); // Remove 'v' prefix
        } else {
            $version = $options["pocket_admin_version"] ?? "v0.0.1";
            $baseUrl = "https://github.com/trustos/pocket-admin/releases/download/{$version}/pocket-admin_";
            $versionNumber = substr($version, 1); // Remove 'v' prefix
        }

        // Determine system architecture
        $arch = php_uname("m");
        $osArch = "amd64"; // Default to amd64

        if (
            strpos($arch, "arm") !== false ||
            strpos($arch, "aarch64") !== false
        ) {
            $osArch = "arm64";
        }

        $url = $baseUrl . $versionNumber . "_linux_{$osArch}.zip";
        error_log(
            "Attempting to download " .
                ($installationType === "pocketbase"
                    ? "PocketBase"
                    : "Pocket Admin") .
                " from: " .
                $url
        );

        $appDir = $this->pocketbasePaths->getAppDir($this->domain);
        $finalZipFile =
            $appDir .
            ($installationType === "pocketbase"
                ? "pocketbase.zip"
                : "pocket-admin.zip");
        $executable =
            $appDir .
            ($installationType === "pocketbase"
                ? "pocketbase"
                : "pocket-admin");

        // Ensure the app directory exists
        if (!is_dir($appDir)) {
            if (!$this->pocketbaseUtils->createDir($appDir)) {
                throw new \Exception(
                    "Failed to create application directory: $appDir"
                );
            }
        }

        // Download the file content
        $fileContent = $this->pocketbaseUtils->downloadFile($url, "");
        if ($fileContent === false) {
            throw new \Exception("Failed to download Pocketbase");
        }

        // Check if the content is actually a zip file
        if (substr($fileContent, 0, 4) !== "\x50\x4b\x03\x04") {
            error_log("Downloaded content is not a valid ZIP file");
            throw new \Exception("Invalid download: not a ZIP file");
        }

        // Save the content to a temporary file
        $tempZipFile = $this->saveTempFile($fileContent);

        try {
            $moveResult = $this->pocketbaseUtils->moveFile(
                $tempZipFile,
                $finalZipFile
            );
            if ($moveResult === false) {
                throw new \Exception("Move operation failed");
            }
        } catch (\Exception $e) {
            error_log(
                "Failed to move Pocketbase zip file. Error: " . $e->getMessage()
            );
            $this->pocketbaseUtils->deleteFile($tempZipFile);
            throw new \Exception(
                "Failed to move Pocketbase zip file: " . $e->getMessage()
            );
        }

        if (!file_exists($finalZipFile)) {
            throw new \Exception(
                "Zip file not found at destination after move: $finalZipFile"
            );
        }

        // Check zip file size
        $zipSize = filesize($finalZipFile);
        error_log("Zip file size: $zipSize bytes");

        // Unzip the file
        if (!$this->pocketbaseUtils->unzipFile($finalZipFile, $appDir)) {
            error_log("Failed to unzip file: $finalZipFile");
            throw new \Exception("Failed to unzip Pocketbase");
        }

        if (!file_exists($executable)) {
            throw new \Exception(
                "Pocketbase executable not found after extraction"
            );
        }

        $this->pocketbaseUtils->makeExecutable($executable);

        // Clean up
        $this->pocketbaseUtils->deleteFile($finalZipFile);
    }

    // Function to create the service file
    private function createServiceFile($options, $domain, $user, $appPath)
    {
        $port = trim($options["port"] ?? "8090");
        $installationType = $options["installation_type"] ?? "pocketbase";
        $executableName =
            $installationType === "pocketbase" ? "pocketbase" : "pocket-admin";

        $result = null;
        $this->appcontext->runUser(
            "v-add-pocketbase-service",
            [$domain, $port, $executableName],
            $result
        );

        if ($result && $result->code === 0) {
            echo "Service file creation successful.\n";
            return true;
        } else {
            echo "Service file creation failed.\n";
            return false;
        }
    }

    // private function createSystemdService(array $options)
    // {
    //     $templateReplaceVars = [
    //         $this->domain,
    //         $this->pocketbasePaths->getAppDir($this->domain),
    //         $this->appcontext->user(),
    //     ];

    //     $data = $this->pocketbaseUtils->parseTemplate(
    //         $this->pocketbasePaths->getPocketbaseSystemdTemplate(),
    //         self::TEMPLATE_SYSTEMD_VARS,
    //         $templateReplaceVars
    //     );
    //     $tmpFile = $this->saveTempFile(implode($data));

    //     $serviceName = "pocketbase-{$this->domain}.service";
    //     $serviceFile = "/etc/systemd/system/{$serviceName}";

    //     $this->pocketbaseUtils->moveFile($tmpFile, $serviceFile);
    //     $this->pocketbaseUtils->reloadSystemd();
    // }

    // private function startPocketbaseService()
    // {
    //     $serviceName = "pocketbase-{$this->domain}";
    //     $this->pocketbaseUtils->startService($serviceName);
    //     $this->pocketbaseUtils->enableService($serviceName);
    // }
}
