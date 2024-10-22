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
            exec("sudo mkdir -p " . escapeshellarg($dir), $output, $returnVar);
            if ($returnVar !== 0) {
                throw new \Exception("Failed to create directory: $dir");
            }
            exec(
                "sudo chown " .
                    escapeshellarg($this->appcontext->getUser()) .
                    ":" .
                    escapeshellarg($this->appcontext->getUser()) .
                    " " .
                    escapeshellarg($dir)
            );
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

    public function downloadFile(string $url, string $destination)
    {
        $ch = curl_init($url);
        $fp = fopen($destination, "wb");

        if ($fp === false) {
            throw new \Exception("Cannot open file for writing: $destination");
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $success = curl_exec($ch);

        if ($success === false) {
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            throw new \Exception(
                "Failed to download file (HTTP $httpCode): $error"
            );
        }

        curl_close($ch);
        fclose($fp);

        if (!file_exists($destination) || filesize($destination) == 0) {
            throw new \Exception(
                "File download appears to have failed. File is missing or empty."
            );
        }

        return true;
    }

    public function unzipFile(string $zipFile, string $destination)
    {
        $command =
            "unzip -o " .
            escapeshellarg($zipFile) .
            " -d " .
            escapeshellarg($destination);
        exec($command, $output, $returnVar);
        return $returnVar === 0;
    }

    public function deleteFile(string $file)
    {
        return unlink($file);
    }

    public function makeExecutable(string $file)
    {
        return chmod($file, 0755);
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
