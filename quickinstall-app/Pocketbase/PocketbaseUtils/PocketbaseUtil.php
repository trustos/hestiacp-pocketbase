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

    public function downloadFile(string $url, string $destination)
    {
        $command =
            "curl -L -o " .
            escapeshellarg($destination) .
            " " .
            escapeshellarg($url);
        exec($command, $output, $returnVar);
        return $returnVar === 0;
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
