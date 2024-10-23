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
            $this->appcontext->runUser("v-add-fs-directory", [$dir], $result);
        }

        return $result;
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

    // public function downloadFile(string $url, string $destination)
    // {
    //     $cmd = sprintf("curl -L -s %s", escapeshellarg($url));

    //     $output = [];
    //     $return_var = 0;
    //     exec($cmd, $output, $return_var);

    //     if ($return_var !== 0) {
    //         $error_message =
    //             "Failed to download file. Command: $cmd\nOutput: " .
    //             implode("\n", $output);
    //         $this->appcontext->runUser("v-log-action", [
    //             "Error",
    //             "Web",
    //             $error_message,
    //         ]);
    //         error_log($error_message);
    //         return false;
    //     }

    //     $content = implode("\n", $output);
    //     if (empty($content)) {
    //         $error_message =
    //             "File download appears to have failed. Content is empty.";
    //         $this->appcontext->runUser("v-log-action", [
    //             "Error",
    //             "Web",
    //             $error_message,
    //         ]);
    //         error_log($error_message);
    //         return false;
    //     }

    //     error_log("File successfully downloaded");
    //     return $content;
    // }
    //

    public function downloadFile(string $url, string $destination)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200 || empty($content)) {
            error_log("Failed to download file. HTTP Code: $httpCode");
            return false;
        }

        return $content;
    }

    // public function unzipFile(string $zipFile, string $destination)
    // {
    //     if (!file_exists($zipFile)) {
    //         error_log("Zip file does not exist: $zipFile");
    //         return false;
    //     }

    //     $result = null;
    //     $this->appcontext->runUser(
    //         "v-run-cli-cmd",
    //         ["tar", "-xzf", $zipFile, "-C", $destination],
    //         $result
    //     );

    //     if ($result->code !== 0) {
    //         error_log("Unzip failed. Output: " . $result->text);
    //         return false;
    //     }

    //     return true;
    // }

    public function unzipFile(string $zipFile, string $destination)
    {
        $result = null;
        $this->appcontext->runUser(
            "v-run-cli-cmd",
            ["unzip", "-o", $zipFile, "-d", $destination],
            $result
        );
        if ($result->code !== 0) {
            error_log("Unzip failed. Output: " . $result->text);
            return false;
        }
        return true;
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

    // public function reloadSystemd()
    // {
    //     exec("sudo systemctl daemon-reload", $output, $returnVar);
    //     return $returnVar === 0;
    // }

    // public function startService(string $serviceName)
    // {
    //     exec("sudo systemctl start $serviceName", $output, $returnVar);
    //     return $returnVar === 0;
    // }

    // public function enableService(string $serviceName)
    // {
    //     exec("sudo systemctl enable $serviceName", $output, $returnVar);
    //     return $returnVar === 0;
    // }
}
