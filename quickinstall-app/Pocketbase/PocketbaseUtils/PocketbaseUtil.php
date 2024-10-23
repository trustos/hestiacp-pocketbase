<?php

namespace Hestia\WebApp\Installers\Pocketbase\PocketbaseUtils;

use Hestia\System\HestiaApp;

class PocketbaseUtil
{
    protected $appcontext;

    public function __construct(HestiaApp $appcontext)
    {
        $this->appcontext = $appcontext;
    }

    public function createDir(string $dir)
    {
        $result = null;

        if (!is_dir($dir)) {
            $this->appcontext->runUser("v-log-action", [
                "Info",
                "System",
                "Attempting to create directory: $dir",
            ]);
            $this->appcontext->runUser("v-add-fs-directory", [$dir], $result);

            // Check if $result is an object and has the necessary properties
            if (
                is_object($result) &&
                property_exists($result, "text") &&
                property_exists($result, "code")
            ) {
                $output = $result->text;
                $return_var = $result->code;
            } else {
                // Handle unexpected result format
                $output = is_string($result) ? $result : print_r($result, true);
                $return_var = 1; // Assume failure if we can't determine the actual return code
            }

            if (
                $return_var !== 0 ||
                (is_string($output) && strpos($output, "Error:") !== false)
            ) {
                $this->appcontext->runUser("v-log-action", [
                    "Error",
                    "System",
                    "Failed to create directory: $dir. Output: $output",
                ]);
                return false;
            } else {
                $this->appcontext->runUser("v-log-action", [
                    "Info",
                    "System",
                    "Successfully created directory: $dir",
                ]);
            }
        } else {
            $this->appcontext->runUser("v-log-action", [
                "Info",
                "System",
                "Directory already exists: $dir",
            ]);
        }

        return true;
    }

    public function moveFile(string $fileA, string $fileB)
    {
        $result = null;

        if (
            !$this->appcontext->runUser(
                "v-move-fs-file",
                [$fileA, $fileB],
                $result
            )
        ) {
            throw new \Exception(
                "Error updating file in: " . $fileA . " " . $result->text
            );
        }

        return $result;
    }

    public function parseTemplate($template, $search, $replace): array
    {
        $data = [];

        $file = fopen($template, "r");
        while ($l = fgets($file)) {
            $data[] = str_replace($search, $replace, $l);
        }
        fclose($file);

        return $data;
    }

    public function downloadFile(string $url, string $destination)
    {
        $cmd = sprintf(
            "curl -L -o %s %s",
            escapeshellarg($destination),
            escapeshellarg($url)
        );

        $output = [];
        $return_var = 0;
        exec($cmd . " 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            $error_message =
                "Failed to download file. Command: $cmd\nOutput: " .
                implode("\n", $output);
            $this->appcontext->runUser("v-log-action", [
                "Error",
                "Web",
                $error_message,
            ]);
            error_log($error_message);
            return false;
        }

        if (!file_exists($destination) || filesize($destination) == 0) {
            $error_message = "File download appears to have failed. File does not exist or is empty: $destination";
            $this->appcontext->runUser("v-log-action", [
                "Error",
                "Web",
                $error_message,
            ]);
            error_log($error_message);
            return false;
        }

        error_log("File successfully downloaded to: $destination");
        return true;
    }

    public function unzipFile(string $zipFile, string $destination)
    {
        $result = null;
        $this->appcontext->runUser(
            "v-run-cli-cmd",
            ["unzip", "-o", $zipFile, "-d", $destination],
            $result
        );
        return $result->code === 0;
    }

    public function deleteFile(string $file)
    {
        $result = null;
        $this->appcontext->runUser("v-delete-fs-file", [$file], $result);
        return $result->code === 0;
    }

    public function makeExecutable(string $file)
    {
        $result = null;
        $this->appcontext->runUser(
            "v-change-fs-file-permission",
            [$file, "0755"],
            $result
        );
        return $result->code === 0;
    }

    public function reloadSystemd()
    {
        exec("sudo systemctl daemon-reload", $output, $returnVar);
        return $returnVar === 0;
    }

    public function startService(string $serviceName)
    {
        exec("sudo systemctl start $serviceName", $output, $returnVar);
        return $returnVar === 0;
    }

    public function enableService(string $serviceName)
    {
        exec("sudo systemctl enable $serviceName", $output, $returnVar);
        return $returnVar === 0;
    }
}
